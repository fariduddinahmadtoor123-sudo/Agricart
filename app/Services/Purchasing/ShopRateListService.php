<?php

namespace App\Services\Purchasing;

use App\Models\CompanyProfile;
use App\Models\Product;
use App\Services\PricingControls\ChannelPricingEngine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ShopRateListService
{
    public const ROWS_PER_PAGE = 30;

    /** @var list<string> */
    public const LOCALES = ['en', 'ur', 'both'];

    public function __construct(
        private readonly ChannelPricingEngine $priceEngine,
    ) {}

    public function normalizeLocale(string $locale): string
    {
        return in_array($locale, self::LOCALES, true) ? $locale : 'en';
    }

    /**
     * Active products that already have a retail rate, A–Z by name.
     *
     * @return Builder<Product>
     */
    public function eligibleQuery(?string $search = null, string $locale = 'en'): Builder
    {
        $locale = $this->normalizeLocale($locale);
        $orderColumn = $locale === 'ur' ? 'name_ur' : 'name_en';

        $query = Product::query()
            ->where('status', 'active')
            ->whereNotNull('retail_price')
            ->where('retail_price', '!=', '')
            ->where('retail_price', '>', 0)
            ->orderBy($orderColumn)
            ->orderBy('name_en');

        if (filled($search)) {
            $needle = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($search)).'%';
            $query->where(function (Builder $inner) use ($needle): void {
                $inner->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle)
                    ->orWhere('code', 'like', $needle)
                    ->orWhere('sku', 'like', $needle)
                    ->orWhere('label_code', 'like', $needle);
            });
        }

        return $query;
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(?string $search = null, string $locale = 'en', int $perPage = 30): LengthAwarePaginator
    {
        return $this->eligibleQuery($search, $locale)->paginate($perPage);
    }

    /**
     * @return list<array{
     *     sr: int,
     *     product_id: int,
     *     name: string,
     *     name_en: string,
     *     name_ur: string,
     *     rate: string
     * }>
     */
    public function rowsForPrint(?string $search, string $locale): array
    {
        $locale = $this->normalizeLocale($locale);
        $rows = [];

        foreach ($this->eligibleQuery($search, $locale)->get() as $index => $product) {
            $rows[] = $this->rowFromProduct($product, $index + 1, $locale);
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<list<array<string, mixed>>>
     */
    public function chunkForPages(array $rows): array
    {
        return array_values(array_chunk($rows, self::ROWS_PER_PAGE));
    }

    /**
     * @return array{store_name: string, store_name_en: string, store_name_ur: string, title: string, printed_at: string}
     */
    public function printMeta(string $locale): array
    {
        $locale = $this->normalizeLocale($locale);
        $company = CompanyProfile::current();
        $nameEn = (string) ($company->business_name_en ?? '');
        $nameUr = (string) ($company->business_name_ur ?? '');

        $storeName = match ($locale) {
            'ur' => filled($nameUr) ? $nameUr : $nameEn,
            'both' => filled($nameUr) && filled($nameEn) && $nameUr !== $nameEn
                ? $nameEn.' / '.$nameUr
                : ($nameEn !== '' ? $nameEn : $nameUr),
            default => filled($nameEn) ? $nameEn : $nameUr,
        };

        return [
            'store_name' => $storeName,
            'store_name_en' => $nameEn,
            'store_name_ur' => $nameUr,
            'title' => (string) __('rate_lists.print.heading'),
            'printed_at' => now()->format('d M Y'),
        ];
    }

    /**
     * @return array{sr: int, product_id: int, name: string, name_en: string, name_ur: string, rate: string}
     */
    public function rowFromProduct(Product $product, int $serial, string $locale): array
    {
        $locale = $this->normalizeLocale($locale);
        $nameEn = (string) ($product->name_en ?? '');
        $nameUr = (string) ($product->name_ur ?? '');

        $name = match ($locale) {
            'ur' => filled($nameUr) ? $nameUr : $nameEn,
            default => filled($nameEn) ? $nameEn : $nameUr,
        };

        return [
            'sr' => $serial,
            'product_id' => (int) $product->id,
            'name' => $name !== '' ? $name : '—',
            'name_en' => $nameEn !== '' ? $nameEn : '—',
            'name_ur' => $nameUr !== '' ? $nameUr : '—',
            'rate' => $this->formatRate($product->retail_price),
        ];
    }

    public function formatRate(mixed $amount): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        return $this->priceEngine->formatPrice((string) $amount);
    }
}
