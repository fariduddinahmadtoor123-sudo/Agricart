<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Purchasing\PurchasesPage;
use App\Filament\Clusters\Sales\PosSalesPage;
use App\Services\Ai\AiConfigurationService;
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

    /**
     * @return array{connected: bool, status: string, model: string|null}
     */
    public static function aiConnectionState(): array
    {
        $configuration = app(AiConfigurationService::class)->active();

        if ($configuration === null) {
            return [
                'connected' => false,
                'status' => 'none',
                'model' => null,
            ];
        }

        return [
            'connected' => $configuration->isConnected(),
            'status' => (string) ($configuration->connection_status ?? 'untested'),
            'model' => $configuration->displayName(),
        ];
    }
}
