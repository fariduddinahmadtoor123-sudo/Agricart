<?php

namespace Tests\Feature\Store;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\Catalog\BrandAgricartReviewCriteria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAgricartRatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_incomplete_product_agricart_rating_is_hidden_on_storefront(): void
    {
        $product = $this->createProduct();
        $product->update([
            'agricart_rating' => 8,
            'agricart_rating_criteria' => [
                'product_quality' => ['rating' => 8, 'reason_en' => 'Good', 'reason_ur' => 'اچھا'],
            ],
        ]);

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertDontSee('data-store-pdp-tab="product-review"', false)
            ->assertDontSee(__('agricart.storefront.product.tabs.agricart_product_review'), false);
    }

    public function test_complete_product_agricart_rating_shows_on_pdp(): void
    {
        $product = $this->createProduct();
        $criteria = $this->completeCriteria();
        $score = BrandAgricartReviewCriteria::score($criteria);

        $product->update([
            'agricart_rating' => $score,
            'agricart_rating_criteria' => BrandAgricartReviewCriteria::forStorage($criteria),
            'agricart_rating_reason_en' => 'Solid product for Pakistani farms.',
            'agricart_rating_reason_ur' => 'پاکستانی کھیتوں کے لیے مضبوط پروڈکٹ۔',
        ]);

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertSee('data-store-pdp-tab="product-review"', false)
            ->assertSee(__('agricart.storefront.product.tabs.agricart_product_review'), false)
            ->assertSee('Solid product for Pakistani farms.', false)
            ->assertSee(__('agricart.storefront.product.facts.agricart_product_rating'), false);
    }

    private function createProduct(): Product
    {
        $category = Category::query()->create([
            'slug' => 'cat-product-rating',
            'code' => 'CAT-PRAT',
            'status' => 'active',
            'name_en' => 'Tools',
            'name_ur' => 'اوزار',
        ]);

        $brand = Brand::query()->create([
            'code' => 'BRD-PRAT',
            'slug' => 'brand-prat',
            'status' => 'active',
            'name_en' => 'Prat Brand',
            'name_ur' => 'پرات برانڈ',
            'image_path' => 'brands/prat.webp',
            'ai_status' => 'none',
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'status' => 'active',
            'code' => 'PRD-PRAT-1',
            'sku' => 'PRAT-1',
            'name_en' => 'Prat Sprayer',
            'name_ur' => 'پرات سپرے',
            'slug' => 'prat-sprayer',
            'full_description_en' => 'A sprayer.',
        ]);
    }

    /**
     * @return array<string, array{rating: int, reason_en: string, reason_ur: string}>
     */
    private function completeCriteria(): array
    {
        $criteria = BrandAgricartReviewCriteria::normalizeDetailed(null);

        foreach (BrandAgricartReviewCriteria::keys() as $key) {
            $criteria[$key] = [
                'rating' => 8,
                'reason_en' => 'Strong '.$key,
                'reason_ur' => 'مضبوط '.$key,
            ];
        }

        return $criteria;
    }
}
