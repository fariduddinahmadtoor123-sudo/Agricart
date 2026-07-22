<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use App\Models\StorePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_suggest_omits_missing_category_images_and_falls_back_to_parent(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('categories/parent.webp', 'parent-image');

        $parent = $this->category('drill-bits-parent', 'Drill Bits Parent', [
            'image_path' => 'categories/parent.webp',
        ]);
        $childMissing = $this->category('diamond-hole-saw-child', 'Diamond Hole Saw Child', [
            'parent_id' => $parent->id,
            'image_path' => 'categories/missing-child.webp',
        ]);

        $response = $this->getJson(route('store.search.suggest', ['q' => 'Diamond']))
            ->assertOk();

        $child = collect($response->json('results'))->firstWhere('id', $childMissing->id);
        $this->assertIsArray($child);
        $this->assertSame(
            \Illuminate\Support\Facades\Storage::disk('public')->url('categories/parent.webp'),
            $child['image'],
        );
    }

    public function test_search_returns_active_categories_subcategories_and_related_products(): void
    {
        $parent = $this->category('agriculture', 'Agriculture');
        $child = $this->category('crop-protection', 'Crop Protection', [
            'parent_id' => $parent->id,
            'seo_keywords_en' => 'agriculture inputs, crop care',
        ]);
        $inactive = $this->category('hidden-agriculture', 'Hidden Agriculture', [
            'status' => 'inactive',
        ]);

        $product = $this->product('field-spray', 'Field Spray', $child);
        $inactiveProduct = $this->product('hidden-spray', 'Hidden Spray', $parent, [
            'status' => 'inactive',
            'seo_keywords_en' => 'agriculture',
        ]);
        $this->product('hidden-category-product', 'Hidden Category Product', $inactive);

        $response = $this->get(route('store.search', ['q' => 'agriculture']));

        $response
            ->assertOk()
            ->assertSee('Agriculture')
            ->assertSee('Crop Protection')
            ->assertSee(__('agricart.storefront.search.category'))
            ->assertSee(__('agricart.storefront.search.subcategory'))
            ->assertSee(route('store.category', $parent->slug))
            ->assertSee(route('store.category', $child->slug))
            ->assertSee($product->name_en)
            ->assertSee(route('store.product', $product->slug))
            ->assertDontSee('Hidden Agriculture')
            ->assertDontSee($inactiveProduct->name_en)
            ->assertDontSee('Hidden Category Product');
    }

    public function test_product_search_covers_descriptions_brands_categories_units_and_search_terms(): void
    {
        $category = $this->category('irrigation', 'Irrigation');
        $product = $this->product('drip-kit', 'Drip Kit', $category, [
            'short_description_en' => 'Water-efficient precision system',
            'search_terms' => ['farm water saver', 'micro irrigation'],
        ]);

        $this->get(route('store.search', ['q' => 'precision']))
            ->assertOk()
            ->assertSee($product->name_en);

        $this->get(route('store.search', ['q' => 'water saver']))
            ->assertOk()
            ->assertSee($product->name_en);

        $this->get(route('store.search', ['q' => 'Irrigation']))
            ->assertOk()
            ->assertSee($product->name_en);
    }

    public function test_search_returns_only_active_information_pages(): void
    {
        $activePage = StorePage::query()->create([
            'title_en' => 'Agriculture Delivery Guide',
            'title_ur' => 'زرعی ترسیل رہنما',
            'slug' => 'agriculture-delivery',
            'content_en' => '<p>Learn about nationwide farm delivery.</p>',
            'is_active' => true,
        ]);
        StorePage::query()->create([
            'title_en' => 'Hidden Agriculture Page',
            'title_ur' => 'مخفی زرعی صفحہ',
            'slug' => 'hidden-agriculture-page',
            'content_en' => 'Agriculture',
            'is_active' => false,
        ]);

        $this->get(route('store.search', ['q' => 'Agriculture']))
            ->assertOk()
            ->assertSee($activePage->title_en)
            ->assertSee($activePage->publicUrl())
            ->assertSee(__('agricart.storefront.search.pages'))
            ->assertDontSee('Hidden Agriculture Page');
    }

    public function test_fragment_and_suggestions_share_unified_results(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('categories/seeds.png', 'seed-image');

        $category = $this->category('seeds', 'Seeds', [
            'image_path' => 'categories/seeds.png',
        ]);
        $product = $this->product('hybrid-seed', 'Hybrid Seed', $category);
        $page = StorePage::query()->create([
            'title_en' => 'Seed Buying Help',
            'title_ur' => 'بیج خریداری مدد',
            'slug' => 'seed-buying-help',
            'content_en' => 'Seed selection information',
            'is_active' => true,
        ]);

        $this->get(route('store.search.fragment', ['q' => 'seed']))
            ->assertOk()
            ->assertSee($category->name_en)
            ->assertSee($product->name_en)
            ->assertSee($page->title_en);

        $results = $this->getJson(route('store.search.suggest', ['q' => 'seed']))
            ->assertOk()
            ->assertJsonPath('query', 'seed')
            ->json('results');

        $this->assertContains('category', array_column($results, 'type'));
        $this->assertContains('product', array_column($results, 'type'));
        $this->assertContains('page', array_column($results, 'type'));
        $this->assertArrayHasKey('image', $results[0]);

        $categoryResult = collect($results)->firstWhere('type', 'category');
        $this->assertNotNull($categoryResult);
        $this->assertNotEmpty($categoryResult['image']);
    }

    public function test_all_results_page_returns_more_than_twelve_matching_categories(): void
    {
        $parent = $this->category('boom-parent', 'Boom Parent');

        for ($i = 1; $i <= 15; $i++) {
            $this->category('boom-part-'.$i, 'Boom Part '.$i, [
                'parent_id' => $parent->id,
            ]);
        }

        $response = $this->get(route('store.search', ['q' => 'Boom']));

        $response->assertOk();

        for ($i = 1; $i <= 15; $i++) {
            $response->assertSee('Boom Part '.$i, false);
        }
    }

    public function test_search_requires_two_characters_and_escapes_like_wildcards(): void
    {
        $category = $this->category('percentage', '100% Organic');

        $this->get(route('store.search', ['q' => '1']))
            ->assertOk()
            ->assertDontSee($category->name_en);

        $this->get(route('store.search', ['q' => '100%']))
            ->assertOk()
            ->assertSee($category->name_en);

        $this->getJson(route('store.search.suggest', ['q' => '1']))
            ->assertOk()
            ->assertJsonCount(0, 'results');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function category(string $slug, string $name, array $overrides = []): Category
    {
        return Category::query()->create(array_merge([
            'slug' => $slug,
            'code' => 'CAT-'.strtoupper($slug),
            'status' => 'active',
            'name_en' => $name,
            'name_ur' => $name.' اردو',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function product(string $slug, string $name, Category $category, array $overrides = []): Product
    {
        return Product::query()->create(array_merge([
            'category_id' => $category->id,
            'slug' => $slug,
            'code' => 'PRD-'.strtoupper($slug),
            'status' => 'active',
            'name_en' => $name,
            'name_ur' => $name.' اردو',
        ], $overrides));
    }
}
