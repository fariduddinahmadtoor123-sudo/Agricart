<?php

namespace App\Services\Purchasing;

use App\Models\CompanyProfile;
use App\Models\Product;
use App\Models\Purchase;
use App\Services\Printing\LabelRenderService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PriceTagService
{
    public function __construct(
        private readonly LabelRenderService $labelRender,
        private readonly PurchaseService $purchaseService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function tagData(
        Product $product,
        string $locale = 'en',
        ?string $costPrice = null,
        ?string $salePrice = null,
    ): array {
        $locale = in_array($locale, ['en', 'ur', 'both'], true) ? $locale : 'en';
        $company = CompanyProfile::current();
        $scanCode = $this->scanCode($product);

        $cost = $costPrice ?? $this->purchaseService->previousPurchasePrice($product->id);
        $sale = $salePrice ?? (string) ($product->retail_price ?? '');

        return [
            'product_id' => $product->id,
            'store_name_en' => (string) ($company->business_name_en ?? ''),
            'store_name_ur' => (string) ($company->business_name_ur ?? ''),
            'store_name' => (string) ($company->localized('business_name') ?? ''),
            'product_name_en' => (string) $product->name_en,
            'product_name_ur' => (string) ($product->name_ur ?? ''),
            'product_name' => $this->productDisplayName($product, $locale),
            'cost_price' => $this->formatPrice($cost),
            'sale_price' => $this->formatPrice($sale),
            'cost_raw' => $cost,
            'sale_raw' => $sale,
            'scan_code' => $scanCode,
            'barcode_svg' => $scanCode !== '' ? $this->labelRender->barcodeSvg($scanCode, 2, 32) : '',
            'qr_svg' => $scanCode !== '' ? $this->labelRender->qrSvg($scanCode, 3) : '',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function linesFromInvoice(string $purchaseNumber): array
    {
        $purchaseNumber = trim($purchaseNumber);

        if ($purchaseNumber === '') {
            throw ValidationException::withMessages([
                'invoice' => __('price_tags.messages.invoice_required'),
            ]);
        }

        $purchase = Purchase::query()
            ->with(['items.product'])
            ->where('purchase_number', $purchaseNumber)
            ->first();

        if ($purchase === null) {
            throw ValidationException::withMessages([
                'invoice' => __('price_tags.messages.invoice_not_found'),
            ]);
        }

        $lines = [];

        foreach ($purchase->items as $item) {
            if ($item->product_id === null || $item->product === null) {
                continue;
            }

            $qty = max(1, (int) round((float) str_replace(',', '', (string) $item->purchase_qty)));

            $lines[] = $this->makeLine(
                $item->product,
                $qty,
                (string) ($item->cost_price ?? ''),
                (string) ($item->sale_price ?? ''),
            );
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'invoice' => __('price_tags.messages.invoice_empty'),
            ]);
        }

        return $lines;
    }

    /**
     * @return array<string, mixed>
     */
    public function makeLine(
        Product $product,
        int $qty = 1,
        ?string $costPrice = null,
        ?string $salePrice = null,
    ): array {
        return [
            'line_id' => 'pt-' . $product->id . '-' . uniqid(),
            'product_id' => $product->id,
            'qty' => max(1, $qty),
            'cost_price' => $costPrice ?? $this->purchaseService->previousPurchasePrice($product->id),
            'sale_price' => $salePrice ?? (string) ($product->retail_price ?? ''),
            'product' => $product,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    public function expandPrintJobs(array $lines, array $options): array
    {
        $locale = in_array($options['locale'] ?? 'en', ['en', 'ur', 'both'], true)
            ? $options['locale']
            : 'en';

        $jobs = [];

        foreach ($lines as $line) {
            $product = ($line['product'] ?? null) instanceof Product
                ? $line['product']
                : Product::query()->find($line['product_id'] ?? null);

            if (! $product instanceof Product) {
                continue;
            }

            $tag = $this->tagData(
                $product,
                $locale,
                (string) ($line['cost_price'] ?? ''),
                (string) ($line['sale_price'] ?? ''),
            );

            $jobs[] = [
                'qty' => max(1, (int) ($line['qty'] ?? 1)),
                'tag' => $tag,
            ];
        }

        return $jobs;
    }

    public function formatPrice(mixed $amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        $profile = CompanyProfile::current();
        $value = (float) str_replace(',', '', (string) $amount);

        if (! $profile->price_use_decimals) {
            return number_format((int) round($value));
        }

        return number_format($value, (int) $profile->price_decimal_places, '.', ',');
    }

    public function productDisplayName(Product $product, string $locale): string
    {
        if ($locale === 'both') {
            $parts = array_values(array_filter([
                filled($product->name_en) ? (string) $product->name_en : null,
                filled($product->name_ur) ? (string) $product->name_ur : null,
            ]));

            return $parts !== [] ? implode(' / ', $parts) : '—';
        }

        if ($locale === 'ur' && filled($product->name_ur)) {
            return (string) $product->name_ur;
        }

        return (string) $product->name_en;
    }

    public function scanCode(Product $product): string
    {
        foreach ([$product->label_code, $product->sku, $product->code, (string) $product->id] as $candidate) {
            if (filled($candidate)) {
                return (string) $candidate;
            }
        }

        return '';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $lines
     */
    public function mergeLine(Collection $lines, array $newLine): Collection
    {
        $existingIndex = $lines->search(
            fn (array $line): bool => (int) ($line['product_id'] ?? 0) === (int) ($newLine['product_id'] ?? 0),
        );

        if ($existingIndex === false) {
            return $lines->push($newLine)->values();
        }

        $existing = $lines[$existingIndex];
        $existing['qty'] = (int) ($existing['qty'] ?? 1) + (int) ($newLine['qty'] ?? 1);
        $existing['cost_price'] = $newLine['cost_price'] ?? $existing['cost_price'] ?? '';
        $existing['sale_price'] = $newLine['sale_price'] ?? $existing['sale_price'] ?? '';

        return $lines->put($existingIndex, $existing)->values();
    }
}
