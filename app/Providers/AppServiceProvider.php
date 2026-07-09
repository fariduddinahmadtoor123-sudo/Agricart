<?php

namespace App\Providers;

use App\Models\Customer;
use App\Services\Store\StoreCartService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            if (! $notifiable instanceof Customer) {
                return url('/');
            }

            return route('store.customer.password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
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
                app(\App\Services\SalesChannels\SalesChannelService::class)->onlineStoreViewState(),
            );
        });
    }
}
