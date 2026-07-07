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
        if (! static::userCan('purchasing', 'view')) {
            return null;
        }

        $url = PurchasesPage::getUrl();

        if (static::userCan('purchasing', 'create')) {
            return static::withQuery($url, ['open' => 'purchase']);
        }

        return $url;
    }

    public static function posUrl(): ?string
    {
        if (! static::userCan('sales', 'view')) {
            return null;
        }

        $url = PosSalesPage::getUrl();

        if (static::userCan('sales', 'create')) {
            return static::withQuery($url, ['open' => 'sale']);
        }

        return $url;
    }

    public static function showPurchaseButton(): bool
    {
        return static::purchaseUrl() !== null;
    }

    public static function showPosButton(): bool
    {
        return static::posUrl() !== null;
    }

    public static function userCanQuickOpenPos(): bool
    {
        return static::userCan('sales', 'create');
    }

    public static function userCanQuickOpenPurchase(): bool
    {
        return static::userCan('purchasing', 'create');
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

    /**
     * @param  array<string, scalar|null>  $params
     */
    protected static function withQuery(string $url, array $params): string
    {
        $params = array_filter($params, fn (mixed $value): bool => $value !== null && $value !== '');

        if ($params === []) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query($params);
    }
}
