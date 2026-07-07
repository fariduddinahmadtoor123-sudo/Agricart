<?php

namespace App\Services\Purchasing;

use App\Filament\Clusters\Purchasing\PriceTagsPage;
use App\Filament\Clusters\Purchasing\PurchasePlanningPage;
use App\Filament\Clusters\Purchasing\PurchaseQuotationsPage;
use App\Filament\Clusters\Purchasing\PurchasesPage;
use App\Filament\Clusters\Purchasing\ReOrderCenterPage;
use App\Filament\Clusters\Settings\PrintingPage;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchasePlanningSheet;
use App\Models\PurchaseQuotation;
use App\Models\ReorderQueueItem;
use Illuminate\Support\Facades\Cache;

class PurchasingOverviewService
{
    public function __construct(
        private readonly ReorderService $reorderService,
    ) {}

    /**
     * @return list<array{key: string, label: string, count: int, active: int|null, url: string}>
     */
    public function statCards(): array
    {
        return Cache::remember(PurchasingCache::OVERVIEW_STATS_KEY, now()->addMinutes(10), function (): array {
            return $this->buildStatCards();
        });
    }

    public function totalRecords(): int
    {
        return array_sum(array_column($this->statCards(), 'count'));
    }

    /**
     * @return list<array{key: string, label: string, count: int, hint: string|null, url: string}>
     */
    public function labelingInsights(): array
    {
        return Cache::remember(PurchasingCache::OVERVIEW_STATS_KEY . '.labeling', now()->addMinutes(10), function (): array {
            return $this->buildLabelingInsights();
        });
    }

    /**
     * @return list<array{key: string, label: string, count: int, active: int|null, url: string}>
     */
    private function buildStatCards(): array
    {
        $activeProducts = Product::query()->where('status', 'active');

        return [
            [
                'key' => 'purchase_planning',
                'label' => (string) __('agricart.purchasing.overview.cards.purchase_planning'),
                'count' => PurchasePlanningSheet::query()->count(),
                'active' => null,
                'url' => PurchasePlanningPage::getUrl(),
            ],
            [
                'key' => 'purchase_quotations',
                'label' => (string) __('agricart.purchasing.overview.cards.purchase_quotations'),
                'count' => PurchaseQuotation::query()->count(),
                'active' => null,
                'url' => PurchaseQuotationsPage::getUrl(),
            ],
            [
                'key' => 'purchases',
                'label' => (string) __('agricart.purchasing.overview.cards.purchases'),
                'count' => Purchase::query()->count(),
                'active' => Purchase::query()
                    ->where('delivery_status', Purchase::DELIVERY_PENDING)
                    ->count(),
                'url' => PurchasesPage::getUrl(),
            ],
            [
                'key' => 're_orders',
                'label' => (string) __('agricart.purchasing.overview.cards.re_orders'),
                'count' => $this->reorderService->reorderCandidates()->count(),
                'active' => ReorderQueueItem::query()
                    ->where('status', ReorderQueueItem::STATUS_PENDING)
                    ->count(),
                'url' => ReOrderCenterPage::getUrl(),
            ],
            [
                'key' => 'price_tags',
                'label' => (string) __('agricart.purchasing.overview.cards.price_tags'),
                'count' => (clone $activeProducts)->count(),
                'active' => (clone $activeProducts)
                    ->where(function ($query): void {
                        $query->whereNull('retail_price')
                            ->orWhere('retail_price', '');
                    })
                    ->count(),
                'url' => PriceTagsPage::getUrl(),
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, count: int, hint: string|null, url: string}>
     */
    private function buildLabelingInsights(): array
    {
        $activeProducts = Product::query()->where('status', 'active');
        $ready = (clone $activeProducts)
            ->whereNotNull('retail_price')
            ->where('retail_price', '!=', '')
            ->where(function ($query): void {
                $query->whereNotNull('label_code')->where('label_code', '!=', '')
                    ->orWhereNotNull('sku')->where('sku', '!=', '');
            })
            ->count();

        $missingBarcode = (clone $activeProducts)
            ->where(function ($query): void {
                $query->where(function ($inner): void {
                    $inner->whereNull('label_code')->orWhere('label_code', '');
                })->where(function ($inner): void {
                    $inner->whereNull('sku')->orWhere('sku', '');
                });
            })
            ->count();

        $recentReceipts = Purchase::query()
            ->where('delivery_status', Purchase::DELIVERY_RECEIVED)
            ->where('purchase_date', '>=', now()->subDays(7)->toDateString())
            ->count();

        return [
            [
                'key' => 'ready_to_label',
                'label' => (string) __('agricart.purchasing.overview.insights.ready_to_label'),
                'count' => $ready,
                'hint' => (string) __('agricart.purchasing.overview.insights.ready_to_label_hint'),
                'url' => PriceTagsPage::getUrl(),
            ],
            [
                'key' => 'missing_sale_price',
                'label' => (string) __('agricart.purchasing.overview.insights.missing_sale_price'),
                'count' => (clone $activeProducts)
                    ->where(function ($query): void {
                        $query->whereNull('retail_price')->orWhere('retail_price', '');
                    })
                    ->count(),
                'hint' => (string) __('agricart.purchasing.overview.insights.missing_sale_price_hint'),
                'url' => PriceTagsPage::getUrl(),
            ],
            [
                'key' => 'missing_barcode',
                'label' => (string) __('agricart.purchasing.overview.insights.missing_barcode'),
                'count' => $missingBarcode,
                'hint' => (string) __('agricart.purchasing.overview.insights.missing_barcode_hint'),
                'url' => PriceTagsPage::getUrl(),
            ],
            [
                'key' => 'recent_receipts',
                'label' => (string) __('agricart.purchasing.overview.insights.recent_receipts'),
                'count' => $recentReceipts,
                'hint' => (string) __('agricart.purchasing.overview.insights.recent_receipts_hint'),
                'url' => PurchasesPage::getUrl(),
            ],
            [
                'key' => 'label_profiles',
                'label' => (string) __('agricart.purchasing.overview.insights.label_profiles'),
                'count' => app(\App\Services\Printing\PrintProfileService::class)->priceLabels()->count(),
                'hint' => (string) __('agricart.purchasing.overview.insights.label_profiles_hint'),
                'url' => PrintingPage::getUrl(),
            ],
        ];
    }
}
