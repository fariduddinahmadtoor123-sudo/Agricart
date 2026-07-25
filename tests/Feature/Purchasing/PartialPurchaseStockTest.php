<?php

namespace Tests\Feature\Purchasing;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Purchasing\PurchaseService;
use App\Services\Purchasing\ReorderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PartialPurchaseStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_delivery_credits_only_received_qty(): void
    {
        $product = $this->product('A', 0);
        $purchase = $this->makePurchase($product, Purchase::DELIVERY_PARTIAL, '100', '40');

        $this->assertSame(40.0, (float) $product->fresh()->stock_qty);
        $this->assertSame('40', (string) $purchase->items->first()->received_qty);
    }

    public function test_received_delivery_credits_full_purchase_qty(): void
    {
        $product = $this->product('B', 5);
        $this->makePurchase($product, Purchase::DELIVERY_RECEIVED, '12', '');

        $this->assertSame(17.0, (float) $product->fresh()->stock_qty);
    }

    public function test_pending_delivery_does_not_change_stock(): void
    {
        $product = $this->product('C', 3);
        $this->makePurchase($product, Purchase::DELIVERY_PENDING, '50', '');

        $this->assertSame(3.0, (float) $product->fresh()->stock_qty);
    }

    public function test_partial_requires_received_qty(): void
    {
        $product = $this->product('D', 0);

        $this->expectException(ValidationException::class);

        $this->makePurchase($product, Purchase::DELIVERY_PARTIAL, '100', '');
    }

    public function test_rebuild_uses_received_qty_for_partial(): void
    {
        $product = $this->product('E', 999);
        $this->makePurchase($product, Purchase::DELIVERY_PARTIAL, '100', '25');

        $result = app(ReorderService::class)->rebuildProductStock($product->fresh());

        $this->assertSame(25.0, $result['stock_qty']);
        $this->assertSame(25.0, (float) $product->fresh()->stock_qty);
    }

    private function makePurchase(Product $product, string $delivery, string $purchaseQty, string $receivedQty): Purchase
    {
        $user = User::factory()->create();
        $supplier = Supplier::query()->create([
            'supplier_code' => 'SUP-'.strtoupper($product->code),
            'status' => 'active',
            'business_name_en' => 'Supplier '.$product->code,
            'business_name_ur' => 'Supplier '.$product->code,
            'address_en' => '1 Test Street',
            'city_en' => 'Lahore',
            'opening_balance' => 0,
            'contacts' => [
                ['mobile_number' => '03001112233', 'contact_name_en' => 'Contact'],
            ],
        ]);

        return app(PurchaseService::class)->create([
            'supplier_id' => $supplier->id,
            'purchaser_id' => $user->id,
            'locale' => 'en',
            'purchase_date' => now()->toDateString(),
            'delivery_status' => $delivery,
            'payment_status' => Purchase::PAYMENT_PENDING,
            'dispute_status' => Purchase::DISPUTE_OK,
            'amount_paid' => '0',
            'created_by' => $user->id,
            'items' => [[
                'product_id' => $product->id,
                'product_name_en' => $product->name_en,
                'product_name_ur' => $product->name_ur,
                'purchase_qty' => $purchaseQty,
                'received_qty' => $receivedQty,
                'current_rate' => '10',
                'sale_price' => '20',
            ]],
        ]);
    }

    private function product(string $suffix, float $stock): Product
    {
        $category = Category::query()->create([
            'slug' => 'partial-stock-'.strtolower($suffix),
            'status' => 'active',
            'name_en' => 'Partial Stock',
            'name_ur' => 'Partial Stock',
        ]);

        return Product::query()->create([
            'code' => 'PS-'.$suffix,
            'slug' => 'ps-'.strtolower($suffix),
            'status' => 'active',
            'name_en' => 'Partial Stock '.$suffix,
            'name_ur' => 'Partial Stock '.$suffix,
            'category_id' => $category->id,
            'sku' => 'PS-'.$suffix,
            'stock_qty' => $stock,
            'retail_price' => 100,
        ]);
    }
}
