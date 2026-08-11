<?php

namespace Tests\Feature\Search;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminGlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_guest_cannot_search(): void
    {
        $response = $this->getJson(route('admin.search.suggest', ['q' => 'pipe']));

        $this->assertContains($response->status(), [401, 403, 302]);
    }

    public function test_super_admin_finds_products_and_brands(): void
    {
        $this->actingAs($this->superAdmin());

        $category = Category::query()->create([
            'slug' => 'cat-search',
            'code' => 'CAT-SRCH',
            'status' => 'active',
            'name_en' => 'Irrigation',
            'name_ur' => 'آبپاشی',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-PIPE',
            'sku' => 'SKU-PIPE',
            'name_en' => 'Aqua Pipe',
            'name_ur' => 'پائپ',
            'slug' => 'aqua-pipe',
        ]);

        $brand = Brand::query()->create([
            'code' => 'BR-SRCH',
            'slug' => 'search-brand',
            'status' => 'active',
            'name_en' => 'SearchBrand',
            'name_ur' => 'تلاش برانڈ',
            'image_path' => 'brands/search-brand.webp',
        ]);

        $pipe = $this->getJson(route('admin.search.suggest', ['q' => 'pipe']));
        $pipe->assertOk();
        $productHit = collect($pipe->json('groups'))->firstWhere('key', 'products')['items'][0] ?? [];
        $this->assertSame('Aqua Pipe', $productHit['label'] ?? null);
        $this->assertArrayHasKey('image', $productHit);
        $this->assertNull($productHit['image']);
        $this->assertSame('products', $productHit['panel']['formKey'] ?? null);
        $this->assertSame($product->id, $productHit['panel']['productId'] ?? null);
        $this->assertSame('edit', $productHit['panel']['mode'] ?? null);

        $brandSearch = $this->getJson(route('admin.search.suggest', ['q' => 'SearchBrand']));
        $brandSearch->assertOk();
        $brandHit = collect($brandSearch->json('groups'))->firstWhere('key', 'brands')['items'][0] ?? [];
        $this->assertSame('SearchBrand', $brandHit['label'] ?? null);
        $this->assertIsString($brandHit['image'] ?? null);
        $this->assertStringContainsString('search-brand.webp', (string) $brandHit['image']);
        $this->assertSame('brands', $brandHit['panel']['formKey'] ?? null);
        $this->assertSame($brand->id, $brandHit['panel']['brandId'] ?? null);
    }

    public function test_role_without_catalog_view_does_not_see_products(): void
    {
        $this->actingAs($this->staffWithPermissions(['contacts.view', 'docs.view']));

        $category = Category::query()->create([
            'slug' => 'cat-hidden',
            'code' => 'CAT-HIDE',
            'status' => 'active',
            'name_en' => 'Hidden Cat',
            'name_ur' => 'چھپا',
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-HIDE',
            'sku' => 'SKU-HIDE',
            'name_en' => 'Secret Pump',
            'name_ur' => 'پمپ',
            'slug' => 'secret-pump',
        ]);

        $response = $this->getJson(route('admin.search.suggest', ['q' => 'pump']));
        $response->assertOk();

        $keys = collect($response->json('groups'))->pluck('key');
        $this->assertFalse($keys->contains('products'));
    }

    public function test_role_with_contacts_view_sees_customers_but_not_catalog(): void
    {
        $this->actingAs($this->staffWithPermissions(['contacts.view']));

        $customer = Customer::query()->create([
            'customer_number' => 'CUS-SRCH',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Searchable',
            'last_name' => 'Buyer',
            'display_name' => 'Searchable Buyer',
            'email' => 'searchable-buyer@test.test',
            'phone' => '03009998877',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'slug' => 'cat-hidden-contacts',
            'code' => 'CAT-HIDE2',
            'status' => 'active',
            'name_en' => 'Hidden Pump Cat',
            'name_ur' => 'چھپا',
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-HIDE2',
            'sku' => 'SKU-HIDE2',
            'name_en' => 'Hidden Pump Two',
            'name_ur' => 'پمپ',
            'slug' => 'hidden-pump-two',
        ]);

        $customers = $this->getJson(route('admin.search.suggest', ['q' => 'Searchable']));
        $customers->assertOk();
        $customerHit = collect($customers->json('groups'))->firstWhere('key', 'customers')['items'][0] ?? [];
        $this->assertTrue(str_contains((string) ($customerHit['label'] ?? ''), 'Searchable'));
        $this->assertArrayHasKey('image', $customerHit);
        $this->assertNull($customerHit['image']);
        $this->assertSame('customers', $customerHit['panel']['formKey'] ?? null);
        $this->assertSame($customer->id, $customerHit['panel']['customerId'] ?? null);
        $this->assertSame('view', $customerHit['panel']['mode'] ?? null);

        $catalog = $this->getJson(route('admin.search.suggest', ['q' => 'pump']));
        $catalog->assertOk();
        $this->assertFalse(collect($catalog->json('groups'))->pluck('key')->contains('products'));
    }

    private function superAdmin(): User
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  list<string>  $keys
     */
    private function staffWithPermissions(array $keys): User
    {
        $role = Role::query()->create([
            'name' => 'Limited',
            'slug' => 'limited-search',
            'is_system' => false,
        ]);

        $permissionIds = [];

        foreach ($keys as $key) {
            $group = explode('.', $key)[0];
            $permissionIds[] = Permission::query()->firstOrCreate(
                ['key' => $key],
                [
                    'group_key' => $group,
                    'label_en' => $key,
                    'label_ur' => $key,
                ],
            )->id;
        }

        $role->permissions()->sync($permissionIds);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
