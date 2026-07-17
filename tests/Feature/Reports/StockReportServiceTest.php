<?php

namespace Tests\Feature\Reports;

use App\Models\Category;
use App\Models\Product;
use App\Services\Reports\StockReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_returns_kpis_and_status_filters(): void
    {
        $category = Category::query()->create([
            'slug' => 'seeds',
            'status' => 'active',
            'name_en' => 'Seeds',
            'name_ur' => 'بیج',
        ]);

        $this->makeProduct('IN-STOCK', 'Urea 50kg', $category->id, 40, null, null);
        $this->makeProduct('LOW-STOCK', 'DAP Bag', $category->id, 3, 5, null);
        $this->makeProduct('OUT-STOCK', 'Empty Bin', $category->id, 0, 2, null);
        $this->makeProduct('REORDER', 'NPK Mix', $category->id, 2, null, 10);

        $service = app(StockReportService::class);
        $all = $service->build();

        $this->assertSame(4, $all['kpis']['total_skus']);
        $this->assertSame(3, $all['kpis']['in_stock']);
        $this->assertSame(1, $all['kpis']['low_stock']);
        $this->assertSame(1, $all['kpis']['out_of_stock']);
        $this->assertSame(1, $all['kpis']['on_reorder']);
        $this->assertSame('45', $all['kpis']['total_on_hand']);

        $out = $service->build(stockFilter: 'out');
        $this->assertCount(1, $out['rows']);
        $this->assertSame('out', $out['rows'][0]['stock_status']);

        $low = $service->build(stockFilter: 'low');
        $this->assertCount(1, $low['rows']);
        $this->assertSame('LOW-STOCK', $low['rows'][0]['sku']);

        $reorder = $service->build(stockFilter: 'reorder');
        $this->assertCount(1, $reorder['rows']);
        $this->assertSame('REORDER', $reorder['rows'][0]['sku']);

        $search = $service->build(search: 'Urea');
        $this->assertCount(1, $search['rows']);
        $this->assertSame('IN-STOCK', $search['rows'][0]['sku']);

        $byCategory = $service->build(categoryFilter: (string) $category->id);
        $this->assertCount(4, $byCategory['rows']);

        $csv = $service->csvMatrix($all['rows'], ['product', 'sku', 'stock_status']);
        $this->assertSame(['Product', 'SKU / Code', 'Status'], $csv[0]);
        $this->assertGreaterThan(1, count($csv));
    }

    private function makeProduct(
        string $sku,
        string $name,
        int $categoryId,
        float $stock,
        ?string $alert,
        ?string $required,
    ): Product {
        return Product::query()->create([
            'code' => $sku,
            'slug' => strtolower($sku),
            'status' => 'active',
            'name_en' => $name,
            'name_ur' => $name,
            'category_id' => $categoryId,
            'sku' => $sku,
            'stock_qty' => $stock,
            'alert_qty' => $alert,
            'required_qty' => $required,
        ]);
    }
}
