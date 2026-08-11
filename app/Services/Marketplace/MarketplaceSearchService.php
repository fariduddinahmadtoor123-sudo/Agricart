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
use App\Support\Search\SearchHit;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceSearchService
{
    /**
     * @return array<string, list<array{label: string, meta: string|null, url: string}>>
     */
    public function search(string $term, int $limitPerGroup = 6): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $needle = '%' . $term . '%';
        $lowerNeedle = mb_strtolower($term);

        return array_filter([
            'sales_channels' => SalesChannelPage::canAccess()
                ? $this->mapSalesChannels($lowerNeedle, $limitPerGroup)
                : [],
            'price_control' => PriceControlPage::canAccess()
                ? $this->mapPriceTiers($lowerNeedle, $limitPerGroup)
                : [],
            'quantity_control' => QuantityControlPage::canAccess()
                ? $this->mapQuantityTiers($lowerNeedle, $limitPerGroup)
                : [],
            'secret_coding' => SecretCodingPage::canAccess()
                ? $this->mapSecretCoding($needle, $limitPerGroup)
                : [],
        ], fn (array $items): bool => $items !== []);
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapSalesChannels(string $lowerNeedle, int $limit): array
    {
        $url = SalesChannelPage::getUrl();

        return SalesChannel::query()
            ->whereIn('slug', SalesChannelCatalog::implementedSlugs())
            ->orderBy('sort_order')
            ->get()
            ->filter(function (SalesChannel $channel) use ($lowerNeedle): bool {
                $haystack = mb_strtolower($channel->name_en . ' ' . ($channel->name_ur ?? ''));

                return str_contains($haystack, $lowerNeedle);
            })
            ->take($limit)
            ->map(function (SalesChannel $channel) use ($url): array {
                $label = $channel->localizedName();

                return SearchHit::make(
                    $label,
                    $channel->is_enabled
                        ? __('agricart.marketplace.overview.channel_on')
                        : __('agricart.marketplace.overview.channel_off'),
                    $url,
                    null,
                    [
                        'formKey' => 'sales-channel',
                        'salesChannelId' => $channel->id,
                        'channelSlug' => $channel->slug,
                        'title' => $label,
                        'resource' => __('agricart.pages.marketplace.sales-channels'),
                    ],
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapPriceTiers(string $lowerNeedle, int $limit): array
    {
        $url = PriceControlPage::getUrl();

        return PriceChannelTier::query()
            ->orderBy('sort_order')
            ->get()
            ->filter(function (PriceChannelTier $tier) use ($lowerNeedle): bool {
                return str_contains(mb_strtolower($tier->name_en), $lowerNeedle)
                    || str_contains(mb_strtolower((string) $tier->name_ur), $lowerNeedle)
                    || str_contains(mb_strtolower($tier->tier_key), $lowerNeedle);
            })
            ->take($limit)
            ->map(function (PriceChannelTier $tier) use ($url): array {
                $label = $tier->name_en ?: $tier->tier_key;

                return SearchHit::make(
                    $label,
                    $tier->tier_key,
                    $url,
                    null,
                    [
                        'formKey' => 'price-controls',
                        'tierId' => $tier->id,
                        'title' => $label,
                        'resource' => __('agricart.pages.marketplace.price-control'),
                    ],
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapQuantityTiers(string $lowerNeedle, int $limit): array
    {
        $url = QuantityControlPage::getUrl();

        return QuantityChannelTier::query()
            ->orderBy('sort_order')
            ->get()
            ->filter(function (QuantityChannelTier $tier) use ($lowerNeedle): bool {
                return str_contains(mb_strtolower($tier->name_en), $lowerNeedle)
                    || str_contains(mb_strtolower((string) $tier->name_ur), $lowerNeedle)
                    || str_contains(mb_strtolower($tier->tier_key), $lowerNeedle);
            })
            ->take($limit)
            ->map(function (QuantityChannelTier $tier) use ($url): array {
                $label = $tier->name_en ?: $tier->tier_key;

                return SearchHit::make(
                    $label,
                    $tier->tier_key,
                    $url,
                    null,
                    [
                        'formKey' => 'quantity-controls',
                        'tierId' => $tier->id,
                        'title' => $label,
                        'resource' => __('agricart.pages.marketplace.quantity-control'),
                    ],
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function mapSecretCoding(string $needle, int $limit): array
    {
        $url = SecretCodingPage::getUrl();

        return SecretCodingProfile::query()
            ->where(function (Builder $query) use ($needle): void {
                $query->where('profile_number', 'like', $needle)
                    ->orWhere('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle);
            })
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(function (SecretCodingProfile $profile) use ($url): array {
                $label = filled($profile->name_en) ? (string) $profile->name_en : (string) $profile->profile_number;

                return SearchHit::make(
                    $label,
                    filled($profile->profile_number) ? (string) $profile->profile_number : null,
                    $url,
                    null,
                    [
                        'formKey' => 'secret-coding',
                        'secretCodingId' => $profile->id,
                        'title' => $label,
                        'resource' => __('agricart.pages.marketplace.secret-coding'),
                    ],
                );
            })
            ->all();
    }
}
