<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use App\Services\Store\StoreCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StoreCartPriceGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('agricart.inventory.allow_overselling', true);
    }

    public function test_unpriced_product_cannot_be_added_to_cart(): void
    {
        $product = $this->product('unpriced-item', retail: null, wholesale: null);

        $cart = app(StoreCartService::class);

        try {
            $cart->add($product->id, 1);
            $this->fail('Expected a ValidationException for an unpriced product.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                __('agricart.storefront.cart.price_pending'),
                $exception->validator->errors()->first('product'),
            );
        }

        $this->assertTrue($cart->isEmpty());
    }

    public function test_zero_price_is_treated_as_unpriced(): void
    {
        $product = $this->product('zero-priced-item', retail: '0', wholesale: '0');

        $this->assertFalse($product->isPriced());

        $this->expectException(ValidationException::class);
        app(StoreCartService::class)->add($product->id, 1);
    }

    public function test_priced_product_can_be_added_to_cart(): void
    {
        $product = $this->product('priced-item', retail: '250', wholesale: '200');

        $this->assertTrue($product->isPriced());
        $this->assertSame(200.0, $product->storeEntryPriceNumeric());

        $cart = app(StoreCartService::class);
        $cart->add($product->id, 2);

        $this->assertFalse($cart->isEmpty());
        $this->assertSame(2, $cart->totalQuantity());
    }

    private function product(string $slug, ?string $retail = '100', ?string $wholesale = null): Product
    {
        $category = Category::query()->create([
            'slug' => $slug.'-cat',
            'code' => strtoupper(substr($slug, 0, 8)),
            'status' => 'active',
            'name_en' => 'Cat '.$slug,
            'name_ur' => 'Cat '.$slug,
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'slug' => $slug,
            'code' => strtoupper($slug),
            'sku' => strtoupper($slug),
            'status' => 'active',
            'name_en' => 'Product '.$slug,
            'name_ur' => 'Product '.$slug,
            'stock_qty' => 10,
            'retail_price' => $retail,
            'wholesale_price' => $wholesale,
        ]);
    }
}
