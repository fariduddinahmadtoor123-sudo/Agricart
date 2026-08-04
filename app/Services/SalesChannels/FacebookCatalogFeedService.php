<?php

namespace App\Services\SalesChannels;

use App\Models\Product;
use App\Models\SalesChannel;
use App\Services\Store\StoreProductService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class FacebookCatalogFeedService
{
    public const CACHE_KEY = 'sales_channels.facebook.catalog.xml.v1';

    public function __construct(
        private readonly StoreProductService $products,
    ) {}

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function channel(): ?SalesChannel
    {
        return SalesChannel::query()->where('slug', SalesChannel::SLUG_FACEBOOK)->first();
    }

    public function isFeedEnabled(): bool
    {
        $channel = $this->channel();

        return $channel !== null && $channel->is_enabled;
    }

    public function expectedToken(): ?string
    {
        $token = $this->channel()?->setting('feed_token');

        return filled($token) ? (string) $token : null;
    }

    public function tokenMatches(?string $provided): bool
    {
        $expected = $this->expectedToken();

        if ($expected === null || $expected === '') {
            return false;
        }

        return hash_equals($expected, (string) $provided);
    }

    public function feedUrl(): ?string
    {
        $token = $this->expectedToken();

        if ($token === null) {
            return null;
        }

        return route('store.facebook_catalog_feed', ['token' => $token]);
    }

    public function priceField(): string
    {
        $field = (string) ($this->channel()?->setting('price_field') ?? 'wholesale_price');

        return in_array($field, ['wholesale_price', 'retail_price'], true)
            ? $field
            : 'wholesale_price';
    }

    /**
     * @return array{
     *     active: int,
     *     with_image: int,
     *     with_price: int,
     *     eligible: int,
     *     missing_price: int,
     *     missing_image: int,
     *     channel_enabled: bool
     * }
     */
    public function eligibilityStats(): array
    {
        $active = Product::query()->where('status', 'active');
        $activeCount = (clone $active)->count();
        $withImage = (clone $active)->whereNotNull('image_path')->where('image_path', '!=', '')->count();
        $withPrice = (clone $active)
            ->where(function ($query): void {
                $query->where(function ($inner): void {
                    $inner->whereNotNull('wholesale_price')->where('wholesale_price', '!=', '')->where('wholesale_price', '>', 0);
                })->orWhere(function ($inner): void {
                    $inner->whereNotNull('retail_price')->where('retail_price', '!=', '')->where('retail_price', '>', 0);
                });
            })
            ->count();
        $eligible = (clone $active)
            ->whereNotNull('image_path')->where('image_path', '!=', '')
            ->where(function ($query): void {
                $query->where(function ($inner): void {
                    $inner->whereNotNull('wholesale_price')->where('wholesale_price', '!=', '')->where('wholesale_price', '>', 0);
                })->orWhere(function ($inner): void {
                    $inner->whereNotNull('retail_price')->where('retail_price', '!=', '')->where('retail_price', '>', 0);
                });
            })
            ->count();

        return [
            'active' => $activeCount,
            'with_image' => $withImage,
            'with_price' => $withPrice,
            'eligible' => $eligible,
            'missing_price' => max(0, $withImage - $eligible),
            'missing_image' => max(0, $activeCount - $withImage),
            'channel_enabled' => $this->isFeedEnabled(),
        ];
    }

    public function buildXml(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(30), function (): string {
            $siteName = e(config('app.name'));
            $home = e(route('store.home'));
            $items = '';
            $priceField = $this->priceField();

            Product::query()
                ->where('status', 'active')
                ->with(['brand'])
                ->orderBy('id')
                ->chunkById(100, function ($chunk) use (&$items, $priceField): void {
                    foreach ($chunk as $product) {
                        $items .= $this->itemXml($product, $priceField);
                    }
                });

            return '<?xml version="1.0" encoding="UTF-8"?>'
                ."\n"
                .'<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">'
                ."\n"
                .'<channel>'
                ."\n"
                .'<title>'.$siteName.' Facebook Catalog</title>'
                ."\n"
                .'<link>'.$home.'</link>'
                ."\n"
                .'<description>'.$siteName.' product feed for Meta Commerce Manager (website checkout)</description>'
                ."\n"
                .$items
                .'</channel>'
                ."\n"
                .'</rss>';
        });
    }

    private function itemXml(Product $product, string $priceField): string
    {
        $title = $this->products->localizedName($product);
        $description = strip_tags((string) (
            $this->products->localizedField($product, 'seo_description')
            ?: $this->products->localizedField($product, 'short_description')
            ?: $this->products->localizedField($product, 'usage_guidelines')
            ?: $title
        ));
        $link = route('store.product', $product->slug);
        $image = $this->products->imageUrl($product);
        $id = $product->sku ?: $product->code ?: (string) $product->id;
        $rawPrice = $product->{$priceField} ?? null;

        if (($rawPrice === null || $rawPrice === '') && $priceField !== 'retail_price') {
            $rawPrice = $product->retail_price;
        }

        $price = $rawPrice !== null && $rawPrice !== ''
            ? number_format((float) $rawPrice, 2, '.', '').' PKR'
            : null;
        $availability = (float) ($product->stock_qty ?? 0) > 0 ? 'in_stock' : 'out_of_stock';
        $brand = $this->products->localizedBrandName($product);

        if ($image === null && filled($product->image_path)) {
            $image = Storage::disk('public')->url($product->image_path);
        }

        if ($price === null || $image === null) {
            return '';
        }

        $lines = [
            '<item>',
            '<g:id>'.e($id).'</g:id>',
            '<g:title>'.e(mb_substr($title, 0, 150)).'</g:title>',
            '<g:description>'.e(mb_substr($description, 0, 5000)).'</g:description>',
            '<g:link>'.e($link).'</g:link>',
            '<g:image_link>'.e($image).'</g:image_link>',
            '<g:availability>'.$availability.'</g:availability>',
            '<g:price>'.e($price).'</g:price>',
            '<g:condition>new</g:condition>',
        ];

        if (filled($brand)) {
            $lines[] = '<g:brand>'.e($brand).'</g:brand>';
        }

        $lines[] = '</item>';

        return implode("\n", $lines)."\n";
    }
}
