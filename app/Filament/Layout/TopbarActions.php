<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Purchasing\PurchasesPage;
use App\Filament\Clusters\Sales\PosSalesPage;
use Filament\Facades\Filament;

class TopbarActions
{
    public static function purchaseUrl(): string
    {
        return PurchasesPage::getUrl();
    }

    public static function posUrl(): string
    {
        return PosSalesPage::getUrl();
    }

    public static function timezone(): string
    {
        return (string) config('app.timezone', 'UTC');
    }

    public static function user(): ?\Illuminate\Contracts\Auth\Authenticatable
    {
        return Filament::auth()->user();
    }
}
