<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Purchasing\PurchasesPage;
use App\Filament\Clusters\Sales\PosSalesPage;
use App\Models\User;
use App\Services\Ai\AiConfigurationService;
use Filament\Facades\Filament;

class TopbarActions
{
    public static function purchaseUrl(): ?string
    {
        return static::userCan('purchasing', 'view') ? PurchasesPage::getUrl() : null;
    }

    public static function posUrl(): ?string
    {
        return static::userCan('sales', 'view') ? PosSalesPage::getUrl() : null;
    }

    public static function showPurchaseButton(): bool
    {
        return static::purchaseUrl() !== null;
    }

    public static function showPosButton(): bool
    {
        return static::posUrl() !== null;
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

    protected static function userCan(string $module, string $action): bool
    {
        $user = static::user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission("{$module}.{$action}");
    }
}
