<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use App\Models\StorePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StorefrontSitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_home_categories_products_and_pages(): void
    {
        Cache::forget('storefront.sitemap.xml');

        $category = Category::query()->create([
            'slug' => 'sitemap-category',
            'status' => 'active',
            'name_en' => 'Sitemap Category',
            'name_ur' => 'سائٹ میپ زمرہ',
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'sitemap-product',
            'code' => 'SM-1',
            'status' => 'active',
            'name_en' => 'Sitemap Product',
            'name_ur' => 'سائٹ میپ پروڈکٹ',
        ]);
        $page = StorePage::query()->create([
            'slug' => 'about-sitemap',
            'title_en' => 'About',
            'title_ur' => 'بارے میں',
            'content_en' => 'About us',
            'content_ur' => 'ہمارے بارے',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get(route('store.sitemap'))
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8');

        $body = $response->getContent();
        $this->assertStringContainsString(route('store.home'), $body);
        $this->assertStringContainsString(route('store.category', $category->slug), $body);
        $this->assertStringContainsString(route('store.product', $product->slug), $body);
        $this->assertStringContainsString(route('store.page', $page->slug), $body);
    }

    public function test_robots_txt_points_at_sitemap_and_disallows_private_paths(): void
    {
        $body = $this->get(route('store.robots'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Sitemap: '.rtrim((string) config('app.url'), '/').'/sitemap.xml', $body);
        $this->assertStringContainsString('Disallow: /admin', $body);
        $this->assertStringContainsString('Disallow: /checkout', $body);
        $this->assertStringContainsString('Disallow: /search', $body);
    }
}
