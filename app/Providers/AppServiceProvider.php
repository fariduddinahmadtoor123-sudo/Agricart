<?php

namespace App\Providers;

use App\Services\Store\StoreCartService;
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
