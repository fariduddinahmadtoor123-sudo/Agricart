<?php

namespace App\Services\Purchasing;

use App\Filament\Clusters\Purchasing\PriceTagsPage;
use App\Filament\Clusters\Purchasing\PurchasePlanningPage;
use App\Filament\Clusters\Purchasing\PurchaseQuotationsPage;
use App\Filament\Clusters\Purchasing\PurchasesPage;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchasePlanningSheet;
use App\Models\PurchaseQuotation;
use App\Support\Search\SearchHit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PurchasingSearchService
{
    /**
     * @return array<string, list<array{label: string, meta: string|null, url: string, image: string|null, panel: array<string, mixed>|null}>>
     */
    public function search(string $term, int $limitPerGroup = 6): array
    {
        $term = trim($term);

        if ($term === '') {
            return [];
        }

        $needle = '%' . $term . '%';

        return array_filter([
            'purchases' => $this->mapResults(
                Purchase::query()
                    ->where('purchase_number', 'like', $needle)
                    ->orderByDesc('purchase_date')
                    ->limit($limitPerGroup)
                    ->get(),
                PurchasesPage::getUrl(),
                fn (Purchase $purchase): ?string => $purchase->purchase_date?->format('Y-m-d'),
            ),
            'purchase_planning' => $this->mapResults(
                PurchasePlanningSheet::query()
                    ->where('planning_number', 'like', $needle)
                    ->orderByDesc('created_at')
                    ->limit($limitPerGroup)
                    ->get(),
                PurchasePlanningPage::getUrl(),
                fn (PurchasePlanningSheet $sheet): ?string => $sheet->localeLabel(),
            ),
            'purchase_quotations' => $this->mapResults(
                PurchaseQuotation::query()
                    ->where('quotation_number', 'like', $needle)
                    ->orderByDesc('quotation_date')
                    ->limit($limitPerGroup)
                    ->get(),
                PurchaseQuotationsPage::getUrl(),
                fn (PurchaseQuotation $quotation): ?string => $quotation->quotation_date?->format('Y-m-d'),
            ),
            'price_tags' => $this->mapResults(
                Product::query()
                    ->where('status', 'active')
                    ->where(function (Builder $query) use ($needle): void {
                        $query->where('name_en', 'like', $needle)
                            ->orWhere('name_ur', 'like', $needle)
                            ->orWhere('code', 'like', $needle)
                            ->orWhere('sku', 'like', $needle)
                            ->orWhere('label_code', 'like', $needle);
                    })
                    ->orderBy('name_en')
                    ->limit($limitPerGroup)
                    ->get(),
                PriceTagsPage::getUrl(),
                fn (Product $product): ?string => $product->sku ?: $product->code,
            ),
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Model>  $records
     * @param  callable(Model): ?string  $metaResolver
     * @return list<array{label: string, meta: string|null, url: string, image: string|null, panel: array<string, mixed>|null}>
     */
    private function mapResults($records, string $url, callable $metaResolver): array
    {
        return $records
            ->map(function (Model $record) use ($url, $metaResolver): array {
                $label = $this->recordLabel($record);

                return SearchHit::make(
                    $label,
                    $metaResolver($record),
                    $url,
                    $record instanceof Product ? SearchHit::image($record->image_path) : null,
                    $this->panelFor($record, $label),
                );
            })
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function panelFor(Model $record, string $label): ?array
    {
        return match (true) {
            $record instanceof Purchase => [
                'formKey' => 'purchases',
                'purchaseId' => $record->id,
                'title' => $label,
                'resource' => __('agricart.nav.purchasing.purchases'),
            ],
            $record instanceof PurchasePlanningSheet => [
                'formKey' => 'purchase-planning',
                'sheetId' => $record->id,
                'title' => $label,
                'resource' => __('agricart.nav.purchasing.purchase-planning'),
            ],
            $record instanceof PurchaseQuotation => [
                'formKey' => 'purchase-quotations',
                'quotationId' => $record->id,
                'title' => $label,
                'resource' => __('agricart.nav.purchasing.purchase-quotations'),
            ],
            default => null,
        };
    }

    private function recordLabel(Model $record): string
    {
        return match (true) {
            $record instanceof Purchase => (string) $record->purchase_number,
            $record instanceof PurchasePlanningSheet => (string) $record->planning_number,
            $record instanceof PurchaseQuotation => (string) $record->quotation_number,
            $record instanceof Product => filled($record->name_en)
                ? (string) $record->name_en
                : (string) __('agricart.purchasing.overview.search_untitled'),
            default => (string) __('agricart.purchasing.overview.search_untitled'),
        };
    }
}
