<?php

namespace Tests\Feature\Catalog;

use App\Livewire\Categories\Form;
use App\Livewire\Categories\ListCategories;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Catalog\CategoryVisualMappingService;
use App\Services\Catalog\ProductLabelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryFormIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);
        $this->actingAs(User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]));
    }

    public function test_catalog_viewer_cannot_create_or_delete_categories(): void
    {
        $role = Role::query()->create([
            'name' => 'Catalog Viewer',
            'slug' => 'catalog-viewer',
        ]);
        $permission = Permission::query()->create([
            'key' => 'catalog.view',
            'group_key' => 'catalog',
            'label_en' => 'View catalog',
            'sort_order' => 1,
        ]);
        $role->permissions()->attach($permission);
        $this->actingAs(User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]));

        $category = $this->category('protected', 'Protected');

        Livewire::test(Form::class)
            ->call('openForm')
            ->assertForbidden();

        Livewire::test(ListCategories::class)
            ->call('deleteCategory', $category->id)
            ->assertForbidden();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_admin_without_catalog_permission_cannot_query_category_picker(): void
    {
        $role = Role::query()->create([
            'name' => 'Unrelated Admin',
            'slug' => 'unrelated-admin',
        ]);
        $this->actingAs(User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]));
        $this->category('secret-category', 'Secret Category');

        Livewire::test(Form::class)
            ->set('parent_search', 'Secret')
            ->assertDontSee('Secret Category');
    }

    public function test_invalid_search_terms_json_does_not_erase_existing_terms(): void
    {
        $category = $this->category('seeds', 'Seeds', [
            'search_terms' => ['seed', 'farm'],
        ]);

        Livewire::test(Form::class)
            ->call('openForm', $category->id, 'edit')
            ->set('search_terms_json', '["seed",}')
            ->call('saveAndClose')
            ->assertSet('statusType', 'danger');

        $this->assertSame(['seed', 'farm'], $category->fresh()->search_terms);
    }

    public function test_valid_scalar_json_does_not_erase_existing_search_terms(): void
    {
        $category = $this->category('scalar-json', 'Scalar JSON', [
            'search_terms' => ['existing'],
        ]);

        Livewire::test(Form::class)
            ->call('openForm', $category->id, 'edit')
            ->set('search_terms_json', '"text"')
            ->call('saveAndClose')
            ->assertSet('statusType', 'danger');

        $this->assertSame(['existing'], $category->fresh()->search_terms);
    }

    public function test_category_cannot_select_its_descendant_as_parent(): void
    {
        $parent = $this->category('parent', 'Parent');
        $child = $this->category('child', 'Child', [
            'parent_id' => $parent->id,
        ]);

        Livewire::test(Form::class)
            ->call('openForm', $parent->id, 'edit')
            ->call('selectParent', $child->id)
            ->assertSet('parent_id', null)
            ->assertSet('statusType', 'danger');
    }

    public function test_deleting_category_removes_its_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('categories/category.jpg', 'image');
        $category = $this->category('media', 'Media', [
            'image_path' => 'categories/category.jpg',
        ]);

        Livewire::test(ListCategories::class)
            ->call('deleteCategory', $category->id);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        Storage::disk('public')->assertMissing('categories/category.jpg');
    }

    public function test_editing_category_preserves_its_existing_visual_map_code(): void
    {
        $category = $this->category('mapped', 'Mapped', [
            'visual_mapping' => ['map_code' => 'R7'],
        ]);

        Livewire::test(Form::class)
            ->call('openForm', $category->id, 'edit')
            ->set('name_en', 'Mapped Updated')
            ->call('saveAndClose');

        $this->assertSame('R7', $category->fresh()->visual_mapping['map_code']);
        $this->assertSame('R7', $category->fresh()->visual_map_code);
    }

    public function test_visual_map_allocation_uses_highest_existing_number_not_sibling_count(): void
    {
        $this->category('root-one', 'Root One', ['visual_mapping' => ['map_code' => 'R1']]);
        $this->category('root-three', 'Root Three', ['visual_mapping' => ['map_code' => 'R3']]);

        $this->assertSame(
            'R4',
            app(CategoryVisualMappingService::class)->generateMapCode(null),
        );
    }

    public function test_category_with_children_cannot_be_reparented(): void
    {
        $root = $this->category('root', 'Root');
        $otherRoot = $this->category('other-root', 'Other Root');
        $this->category('child', 'Child', ['parent_id' => $root->id]);

        Livewire::test(Form::class)
            ->call('openForm', $root->id, 'edit')
            ->call('selectParent', $otherRoot->id)
            ->call('saveAndClose')
            ->assertSet('statusType', 'danger');

        $this->assertNull($root->fresh()->parent_id);
    }

    public function test_category_with_products_cannot_be_reparented(): void
    {
        $root = $this->category('product-root', 'Product Root');
        $otherRoot = $this->category('product-other-root', 'Product Other Root');
        Product::query()->create([
            'category_id' => $root->id,
            'slug' => 'linked-product',
            'code' => 'PRD-LINKED',
            'status' => 'active',
            'name_en' => 'Linked Product',
            'name_ur' => 'منسلک پراڈکٹ',
        ]);

        Livewire::test(Form::class)
            ->call('openForm', $root->id, 'edit')
            ->call('selectParent', $otherRoot->id)
            ->call('saveAndClose')
            ->assertSet('statusType', 'danger');

        $this->assertNull($root->fresh()->parent_id);
    }

    public function test_category_name_change_refreshes_stored_product_labels(): void
    {
        $category = $this->category('label-category', 'Old Category', [
            'name_ur' => 'پرانا زمرہ',
            'image_path' => 'categories/category.jpg',
            'visual_mapping' => ['map_code' => 'R1'],
            'visual_map_code' => 'R1',
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'label-product',
            'code' => 'PRD-LABEL',
            'status' => 'active',
            'name_en' => 'Label Product',
            'name_ur' => 'لیبل پراڈکٹ',
            'label_code' => 'R1-P1',
            'category_visual_map_code' => 'R1',
            'label_sequence' => 1,
        ]);
        app(ProductLabelService::class)->generateForProduct($product);

        Livewire::test(Form::class)
            ->call('openForm', $category->id, 'edit')
            ->set('name_en', 'New Category')
            ->set('name_ur', 'نیا زمرہ')
            ->call('saveAndClose');

        $englishLines = collect($product->fresh()->label_qr['en']['lines']);
        $urduLines = collect($product->fresh()->label_qr['ur']['lines']);

        $this->assertTrue($englishLines->contains(
            fn (array $line): bool => $line['label'] === 'Category' && $line['value'] === 'New Category',
        ));
        $this->assertTrue($urduLines->contains(
            fn (array $line): bool => $line['label'] === 'زمرہ' && $line['value'] === 'نیا زمرہ',
        ));
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
            'image_path' => 'categories/test.jpg',
        ], $overrides));
    }
}
