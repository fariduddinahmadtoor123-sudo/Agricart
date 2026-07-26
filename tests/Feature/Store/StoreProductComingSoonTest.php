<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreProductComingSoonTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_product_shows_on_category_as_coming_soon_without_price(): void
    {
        $category = Category::query()->create([
            'slug' => 'tools-soon',
            'code' => 'CAT-TOOLS-SOON',
            'status' => 'active',
            'name_en' => 'Tools Soon',
            'name_ur' => 'اوزار',
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'soon-spade',
            'code' => 'PRD-SOON-SPADE',
            'status' => 'inactive',
            'name_en' => 'Soon Spade',
            'name_ur' => 'بیلچہ',
            'retail_price' => 500,
        ]);

        $this->get(route('store.category', $category->slug))
            ->assertOk()
            ->assertSee('Soon Spade', false)
            ->assertSee(__('agricart.storefront.products.coming_soon'), false)
            ->assertDontSee('Rs 500', false);
    }

    public function test_inactive_product_page_is_viewable_without_add_to_cart(): void
    {
        $category = Category::query()->create([
            'slug' => 'tools-page',
            'code' => 'CAT-TOOLS-PAGE',
            'status' => 'active',
            'name_en' => 'Tools Page',
            'name_ur' => 'اوزار',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'view-only-rake',
            'code' => 'PRD-VIEW-RAKE',
            'status' => 'inactive',
            'name_en' => 'View Only Rake',
            'name_ur' => 'ریک',
            'retail_price' => 750,
        ]);

        $this->get(route('store.product', $product->slug))
            ->assertOk()
            ->assertSee('View Only Rake', false)
            ->assertSee(__('agricart.storefront.product.coming_soon'), false)
            ->assertDontSee(__('agricart.storefront.product.add_to_cart'), false)
            ->assertDontSee(route('store.cart.add'), false);
    }
}
