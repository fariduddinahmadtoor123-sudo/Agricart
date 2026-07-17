<?php

namespace Tests\Feature\Store;

use App\Livewire\Store\ListStoreItems;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListStoreItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_shows_active_products_with_display_categories(): void
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $category = Category::query()->create([
            'slug' => 'seeds',
            'code' => 'CAT-SEEDS',
            'status' => 'active',
            'name_en' => 'Seeds',
            'name_ur' => 'بیج',
        ]);

        $display = Category::query()->create([
            'slug' => 'featured',
            'code' => 'CAT-FEAT',
            'status' => 'active',
            'name_en' => 'Featured',
            'name_ur' => 'نمایاں',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-000001',
            'sku' => 'WHEAT-1',
            'name_en' => 'Wheat Seed Pack',
            'name_ur' => 'گندم بیج پیک',
            'slug' => 'wheat-seed-pack',
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'status' => 'draft',
            'code' => 'PRD-000002',
            'sku' => 'DRAFT-1',
            'name_en' => 'Draft Product',
            'name_ur' => 'ڈرافٹ',
            'slug' => 'draft-product',
        ]);

        $product->displayCategories()->attach($display->id);

        $this->actingAs($user);

        Livewire::test(ListStoreItems::class)
            ->assertSee('Wheat Seed Pack')
            ->assertSee('Seeds')
            ->assertSee('Featured')
            ->assertDontSee('Draft Product');
    }
}
