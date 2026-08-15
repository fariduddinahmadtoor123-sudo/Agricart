<?php

namespace App\Providers;

use App\Contracts\Store\SmsSender;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\OnlineSaleOrderItem;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\PurchasePlanningItem;
use App\Models\PurchaseQuotationItem;
use App\Models\QuantityChannelTier;
use App\Models\QuantityControl;
use App\Models\SaleItem;
use App\Models\SaleQuotationItem;
use App\Models\SaleReturnItem;
use App\Models\StoreSetting;
use App\Models\StoreFooterLink;
use App\Models\StoreMenuItem;
use App\Models\StorePage;
use App\Models\StoreTickerMessage;
use App\Models\Supplier;
use App\Models\User;
use App\Listeners\RecordAuthActivity;
use App\Observers\AuditableModelObserver;
use App\Observers\MediaUsageObserver;
use App\Services\Analytics\AnalyticsContext;
use App\Services\SalesChannels\SalesChannelService;
use App\Services\PricingControls\QuantityPricingEngine;
use App\Services\Store\LogSmsSender;
use App\Services\Store\StoreCartService;
use App\Services\Store\StoreLayoutService;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Failed;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AnalyticsContext::class);
        $this->app->singleton(\App\Services\Analytics\GeoLocationService::class);

        $this->app->bind(SmsSender::class, function (): SmsSender {
            $driver = (string) config('agricart.sms.driver', 'log');

            return match ($driver) {
                'log' => new LogSmsSender,
                default => new LogSmsSender,
            };
        });

        $this->app->singleton(\App\Services\Communication\ChannelRegistry::class);
        $this->app->bind(\App\Contracts\Communication\NotifierPort::class, \App\Services\Communication\Ports\NullNotifier::class);
        $this->app->bind(\App\Contracts\Communication\PresencePort::class, \App\Services\Communication\Ports\NullPresence::class);
        $this->app->bind(\App\Contracts\Communication\AiAssistPort::class, \App\Services\Communication\Ports\NullAiAssist::class);
        $this->app->bind(\App\Contracts\Communication\CallSessionPort::class, \App\Services\Communication\Ports\NullCallSession::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(static fn () => Password::min(8));

        PreventRequestsDuringMaintenance::except([
            'up',
            'admin/backups/restore-progress',
        ]);

        RateLimiter::for('customer-login', fn (Request $request) => [
            Limit::perMinute(10)->by(strtolower((string) $request->input('login', $request->input('email'))).'|'.$request->ip()),
            Limit::perHour(50)->by($request->ip()),
        ]);
        RateLimiter::for('customer-register', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perHour(20)->by($request->ip()),
        ]);
        RateLimiter::for('customer-password-reset', fn (Request $request) => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perHour(20)->by($request->ip()),
        ]);
        RateLimiter::for('store-checkout', fn (Request $request) => [
            Limit::perMinute(10)->by((string) ($request->user('customer')?->id ?? $request->ip())),
        ]);
        RateLimiter::for('customer-payment-proof', fn (Request $request) => [
            Limit::perMinute(5)->by((string) ($request->user('customer')?->id ?? $request->ip())),
        ]);
        RateLimiter::for('customer-order-return', fn (Request $request) => [
            Limit::perMinute(5)->by((string) ($request->user('customer')?->id ?? $request->ip())),
        ]);

        foreach ([
            Category::class,
            Brand::class,
            Product::class,
            CompanyProfile::class,
            StoreSetting::class,
            Customer::class,
            Supplier::class,
            User::class,
            PurchaseItem::class,
            PurchasePlanningItem::class,
            PurchaseQuotationItem::class,
            SaleItem::class,
            SaleQuotationItem::class,
            SaleReturnItem::class,
            OnlineSaleOrderItem::class,
        ] as $mediaOwner) {
            $mediaOwner::observe(MediaUsageObserver::class);
        }

        foreach (config('activity.models', []) as $auditable) {
            if (is_string($auditable) && is_subclass_of($auditable, Model::class)) {
                $auditable::observe(AuditableModelObserver::class);
            }
        }

        Event::listen(Login::class, [RecordAuthActivity::class, 'onLogin']);
        Event::listen(Logout::class, [RecordAuthActivity::class, 'onLogout']);
        Event::listen(Failed::class, [RecordAuthActivity::class, 'onFailed']);

        foreach ([
            StoreSetting::class,
            StoreFooterLink::class,
            StoreMenuItem::class,
            StorePage::class,
            StoreTickerMessage::class,
        ] as $layoutModel) {
            $layoutModel::saved(static fn (): mixed => app(StoreLayoutService::class)->clearCache());
            $layoutModel::deleted(static fn (): mixed => app(StoreLayoutService::class)->clearCache());
        }

        foreach ([QuantityControl::class, QuantityChannelTier::class] as $pricingModel) {
            $pricingModel::saved(static fn (): mixed => app(QuantityPricingEngine::class)->clearCache());
            $pricingModel::deleted(static fn (): mixed => app(QuantityPricingEngine::class)->clearCache());
        }

        if (parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https') {
            URL::forceScheme('https');
        }

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            // Online store customers → storefront reset form.
            if ($notifiable instanceof Customer) {
                return route('store.customer.password.reset', [
                    'token' => $token,
                    'email' => $notifiable->getEmailForPasswordReset(),
                ]);
            }

            // Staff / admin users → Filament signed reset URL (never the storefront homepage).
            if ($notifiable instanceof User) {
                return Filament::getResetPasswordUrl($token, $notifiable);
            }

            return url('/');
        });

        View::composer('store.components.header', function ($view): void {
            $view->with('cartItemCount', app(StoreCartService::class)->totalQuantity());
        });

        View::composer('*', function ($view): void {
            $name = $view->getName();

            if (! is_string($name) || ! str_starts_with($name, 'store.')) {
                return;
            }

            if ($view->offsetExists('onlineStoreChannel')) {
                return;
            }

            $view->with(
                'onlineStoreChannel',
                app(SalesChannelService::class)->onlineStoreViewState(),
            );
        });
    }
}
