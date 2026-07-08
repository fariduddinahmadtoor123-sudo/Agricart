<?php

namespace App\Services\Marketplace;

use App\Filament\Clusters\Marketplace\PriceControlPage;
use App\Filament\Clusters\Marketplace\QuantityControlPage;
use App\Filament\Clusters\Marketplace\SalesChannelPage;
use App\Filament\Clusters\Marketplace\SecretCodingPage;
use App\Models\PriceChannelTier;
use App\Models\QuantityChannelTier;
use App\Models\SalesChannel;
use App\Models\SecretCodingProfile;
use App\Services\SalesChannels\SalesChannelCatalog;

class MarketplaceOverviewService
{
    /**
     * @return list<array{key: string, label: string, count: int, active: int|null, url: string}>
     */
    public function statCards(): array
    {
        $cards = [
            $this->salesChannelsCard(),
            $this->priceControlCard(),
            $this->quantityControlCard(),
            $this->secretCodingCard(),
        ];

        $visible = array_filter($cards, function (array $card): bool {
            $pageClass = $card['page'];

            return $pageClass::canAccess();
        });

        return array_values(array_map(function (array $card): array {
            unset($card['page']);

            return $card;
        }, $visible));
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function salesChannelsCard(): array
    {
        $slugs = SalesChannelCatalog::implementedSlugs();
        $query = SalesChannel::query()->whereIn('slug', $slugs);
        $count = (clone $query)->count();
        $enabled = (clone $query)->where('is_enabled', true)->count();

        return $this->card('sales_channels', $count, $enabled, SalesChannelPage::class);
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function priceControlCard(): array
    {
        $query = PriceChannelTier::query();
        $count = (clone $query)->count();
        $active = (clone $query)->where('status', 'active')->count();

        return $this->card('price_control', $count, $active, PriceControlPage::class);
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function quantityControlCard(): array
    {
        $query = QuantityChannelTier::query();
        $count = (clone $query)->count();
        $active = (clone $query)->where('status', 'active')->count();

        return $this->card('quantity_control', $count, $active, QuantityControlPage::class);
    }

    /**
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function secretCodingCard(): array
    {
        $query = SecretCodingProfile::query();
        $count = (clone $query)->count();
        $active = (clone $query)->where('status', 'active')->count();

        return $this->card('secret_coding', $count, $active, SecretCodingPage::class);
    }

    /**
     * @param  class-string  $pageClass
     * @return array{key: string, label: string, count: int, active: int|null, url: string, page: class-string}
     */
    private function card(string $key, int $count, ?int $active, string $pageClass): array
    {
        return [
            'key' => $key,
            'label' => (string) __('agricart.marketplace.overview.cards.' . $key),
            'count' => $count,
            'active' => $active,
            'url' => $pageClass::getUrl(),
            'page' => $pageClass,
        ];
    }
}
