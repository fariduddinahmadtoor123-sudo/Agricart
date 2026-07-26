<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Product;
use App\Services\Store\StoreCategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreCategoryDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_child_categories_include_also_show_in_placements(): void
    {
        $parent = Category::query()->create([
            'slug' => 'seeds',
            'code' => 'CAT-SEEDS',
            'status' => 'active',
            'name_en' => 'Seeds',
            'name_ur' => 'بیج',
        ]);

        $directChild = Category::query()->create([
            'parent_id' => $parent->id,
            'slug' => 'wheat-seed',
            'code' => 'CAT-WHEAT',
            'status' => 'active',
            'name_en' => 'Wheat Seed',
            'name_ur' => 'گندم بیج',
        ]);

        $elsewhere = Category::query()->create([
            'slug' => 'fertilizer',
            'code' => 'CAT-FERT',
            'status' => 'active',
            'name_en' => 'Fertilizer',
            'name_ur' => 'کھاد',
        ]);

        $alsoShown = Category::query()->create([
            'parent_id' => $elsewhere->id,
            'slug' => 'organic-blend',
            'code' => 'CAT-ORG',
            'status' => 'active',
            'name_en' => 'Organic Blend',
            'name_ur' => 'نامیاتی مرکب',
        ]);

        $alsoShown->displayInCategories()->attach($parent->id);

        $children = app(StoreCategoryService::class)->childCategories($parent);

        $this->assertSame(
            [$alsoShown->id, $directChild->id],
            $children->pluck('id')->all(),
        );
    }

    public function test_inactive_also_show_in_categories_remain_as_coming_soon(): void
    {
        $parent = Category::query()->create([
            'slug' => 'tools',
            'code' => 'CAT-TOOLS',
            'status' => 'active',
            'name_en' => 'Tools',
            'name_ur' => 'اوزار',
        ]);

        $inactive = Category::query()->create([
            'slug' => 'old-tools',
            'code' => 'CAT-OLD',
            'status' => 'inactive',
            'name_en' => 'Old Tools',
            'name_ur' => 'پرانے اوزار',
        ]);

        $inactive->displayInCategories()->attach($parent->id);

        $service = app(StoreCategoryService::class);
        $children = $service->childCategories($parent);

        $this->assertSame([$inactive->id], $children->pluck('id')->all());
        $this->assertTrue($service->isComingSoon($inactive));

        $this->get(route('store.category', $parent->slug))
            ->assertOk()
            ->assertSee('Old Tools', false)
            ->assertSee(__('agricart.storefront.categories.coming_soon'), false);
    }

    public function test_inactive_root_category_stays_on_home_as_coming_soon(): void
    {
        Category::query()->create([
            'slug' => 'soon-root',
            'code' => 'CAT-SOON-ROOT',
            'status' => 'inactive',
            'name_en' => 'Soon Root',
            'name_ur' => 'جلد روٹ',
        ]);

        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('Soon Root', false)
            ->assertSee(__('agricart.storefront.categories.coming_soon'), false);
    }

    public function test_inactive_category_page_shows_coming_soon_without_products(): void
    {
        $category = Category::query()->create([
            'slug' => 'inactive-page',
            'code' => 'CAT-INACTIVE-PAGE',
            'status' => 'inactive',
            'name_en' => 'Inactive Page',
            'name_ur' => 'غیر فعال صفحہ',
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'hidden-when-inactive',
            'code' => 'PRD-HIDDEN-INACTIVE',
            'status' => 'active',
            'name_en' => 'Hidden When Inactive',
            'name_ur' => 'چھپی پروڈکٹ',
        ]);

        $this->get(route('store.category', $category->slug))
            ->assertOk()
            ->assertSee('Inactive Page', false)
            ->assertSee(__('agricart.storefront.category.coming_soon'), false)
            ->assertDontSee('Hidden When Inactive', false);
    }

    public function test_root_categories_do_not_pull_display_placements(): void
    {
        $root = Category::query()->create([
            'slug' => 'root-a',
            'code' => 'CAT-ROOT-A',
            'status' => 'active',
            'name_en' => 'Root A',
            'name_ur' => 'روٹ اے',
        ]);

        $child = Category::query()->create([
            'parent_id' => $root->id,
            'slug' => 'child-a',
            'code' => 'CAT-CHILD-A',
            'status' => 'active',
            'name_en' => 'Child A',
            'name_ur' => 'چائلڈ اے',
        ]);

        $orphanRoot = Category::query()->create([
            'slug' => 'root-b',
            'code' => 'CAT-ROOT-B',
            'status' => 'active',
            'name_en' => 'Root B',
            'name_ur' => 'روٹ بی',
        ]);

        $child->displayInCategories()->attach($orphanRoot->id);

        $roots = app(StoreCategoryService::class)->rootCategories();

        $this->assertSame(
            [$root->id, $orphanRoot->id],
            $roots->pluck('id')->sort()->values()->all(),
        );
        $this->assertFalse($roots->contains(fn (Category $category): bool => $category->id === $child->id));
    }

    public function test_category_content_sections_render_as_individual_accessible_tabs(): void
    {
        $category = Category::query()->create([
            'slug' => 'crop-care',
            'code' => 'CAT-CROP-CARE',
            'status' => 'active',
            'name_en' => 'Crop Care',
            'name_ur' => 'فصل کی دیکھ بھال',
            'full_description_en' => 'Detailed category information.',
            'usage_guidelines_en' => 'Choose products according to the crop.',
            'hs_code' => '3808.99',
        ]);

        $response = $this->get(route('store.category', $category->slug));

        $response
            ->assertOk()
            ->assertSeeInOrder(['Details', 'Usage', 'HS Code'])
            ->assertSee('data-store-pdp-tab="category-content-0"', false)
            ->assertSee('aria-controls="store-category-panel-category-content-0"', false)
            ->assertSee('data-store-pdp-panel="category-hs-code"', false);
    }

    public function test_storefront_product_counts_include_descendant_products(): void
    {
        $root = Category::query()->create([
            'slug' => 'farm',
            'code' => 'CAT-FARM',
            'status' => 'active',
            'name_en' => 'Farm',
            'name_ur' => 'فارم',
        ]);

        $child = Category::query()->create([
            'parent_id' => $root->id,
            'slug' => 'tools-child',
            'code' => 'CAT-TOOLS-CHILD',
            'status' => 'active',
            'name_en' => 'Tools Child',
            'name_ur' => 'اوزار',
        ]);

        Product::query()->create([
            'category_id' => $child->id,
            'slug' => 'spade',
            'code' => 'PRD-SPADE',
            'status' => 'active',
            'name_en' => 'Spade',
            'name_ur' => 'بیلچہ',
        ]);

        Product::query()->create([
            'category_id' => $root->id,
            'slug' => 'seed-bag',
            'code' => 'PRD-SEED',
            'status' => 'active',
            'name_en' => 'Seed Bag',
            'name_ur' => 'بیج',
        ]);

        $counts = app(StoreCategoryService::class)->storefrontProductCounts(collect([$root, $child]));

        $this->assertSame(2, $counts[$root->id]);
        $this->assertSame(1, $counts[$child->id]);

        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('2 products', false)
            ->assertDontSee('Seed Bag', false)
            ->assertDontSee('Spade', false);

        $this->get(route('store.category', $root->slug))
            ->assertOk()
            ->assertSee('Tools Child', false)
            ->assertSee('1 product', false)
            ->assertSee(__('agricart.storefront.category.subcategories'), false)
            ->assertDontSee('>Farm</a>', false);
    }
}
