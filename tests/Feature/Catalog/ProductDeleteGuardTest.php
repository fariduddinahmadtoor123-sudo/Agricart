<?php

namespace Tests\Feature\Catalog;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Catalog\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductDeleteGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_with_sale_item_cannot_be_deleted(): void
    {
        $product = Product::query()->create([
            'slug' => 'guarded-product',
            'code' => 'PRD-GUARD',
            'status' => 'active',
            'name_en' => 'Guarded Product',
            'name_ur' => 'محفوظ',
            'stock_qty' => 0,
        ]);

        $sale = Sale::query()->create([
            'sale_number' => 'SAL-GUARD1',
            'status' => Sale::STATUS_COMPLETED,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'sale_date' => now()->toDateString(),
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 100,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => 'Guarded Product',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        $this->expectException(ValidationException::class);
        app(ProductService::class)->delete($product);
    }

    public function test_unreferenced_product_can_be_deleted(): void
    {
        $product = Product::query()->create([
            'slug' => 'free-product',
            'code' => 'PRD-FREE',
            'status' => 'active',
            'name_en' => 'Free Product',
            'name_ur' => 'آزاد',
            'stock_qty' => 0,
        ]);

        app(ProductService::class)->delete($product);

        $this->assertDatabaseMissing('products', ['code' => 'PRD-FREE']);
    }
}
