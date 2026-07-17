<?php

namespace Tests\Feature\Inventory;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Services\Purchasing\ReorderService;
use App\Services\Sales\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_after_oversell_preserves_negative_balance(): void
    {
        $product = $this->makeProduct();
        $supplier = $this->makeSupplier();

        app(SaleService::class)->complete([
            'sale_date' => '2026-07-17',
            'locale' => 'en',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'price_display' => 'plain',
            'amount_paid' => 50,
            'rows' => [[
                'product_id' => $product->id,
                'product_name_en' => 'Demo',
                'quantity' => '50',
                'unit_price' => '1',
                'line_total' => '50',
            ]],
        ]);

        $this->assertSame(-50.0, (float) $product->fresh()->stock_qty);

        $purchase = Purchase::query()->create([
            'purchase_number' => 'PO-STOCK',
            'supplier_id' => $supplier->id,
            'purchase_date' => '2026-07-17',
            'payment_status' => Purchase::PAYMENT_PAID,
            'delivery_status' => Purchase::DELIVERY_RECEIVED,
            'subtotal' => 200,
            'total_amount' => 200,
            'amount_paid' => 200,
            'balance_due' => 0,
        ]);

        PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => 'Demo',
            'purchase_qty' => '200',
            'current_rate' => '100',
            'landing_cost' => '10',
            'cost_price' => '110',
            'line_total' => '20000',
        ]);

        app(ReorderService::class)->applyPurchaseStock($purchase->fresh(['items']));

        $this->assertSame(150.0, (float) $product->fresh()->stock_qty);

        app(SaleService::class)->complete([
            'sale_date' => '2026-07-17',
            'locale' => 'en',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'price_display' => 'plain',
            'amount_paid' => 100,
            'rows' => [[
                'product_id' => $product->id,
                'product_name_en' => 'Demo',
                'quantity' => '100',
                'unit_price' => '1',
                'line_total' => '100',
            ]],
        ]);

        $this->assertSame(50.0, (float) $product->fresh()->stock_qty);

        $rebuilt = app(ReorderService::class)->rebuildProductStock($product->fresh());
        $this->assertSame(50.0, $rebuilt['stock_qty']);
    }

    private function makeProduct(): Product
    {
        $category = Category::query()->create([
            'slug' => 'demo',
            'status' => 'active',
            'name_en' => 'Demo',
            'name_ur' => 'Demo',
        ]);

        return Product::query()->create([
            'code' => 'DEMO-1',
            'slug' => 'demo-1',
            'status' => 'active',
            'name_en' => 'Demo',
            'name_ur' => 'Demo',
            'category_id' => $category->id,
            'sku' => 'DEMO-1',
            'stock_qty' => 0,
        ]);
    }

    private function makeSupplier(): Supplier
    {
        return Supplier::query()->create([
            'supplier_code' => 'SUP-ST01',
            'status' => 'active',
            'business_name_en' => 'Stock Supplier',
            'business_name_ur' => 'Stock Supplier',
            'address_en' => '1 Test Street',
            'city_en' => 'Lahore',
            'opening_balance' => 0,
            'contacts' => [
                ['mobile_number' => '03001112233', 'contact_name_en' => 'Contact'],
            ],
        ]);
    }
}
