<?php

namespace Tests\Feature\Contacts;

use App\Livewire\Suppliers\ListSuppliers;
use App\Models\Category;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierListCategorySearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_filters_suppliers_by_assigned_category_name(): void
    {
        $admin = $this->superAdmin();

        $pumps = Category::query()->create([
            'slug' => 'special-pumps',
            'code' => 'CAT-PUMP',
            'status' => 'active',
            'name_en' => 'Special Pumps',
            'name_ur' => 'سپیشل پمپ',
        ]);

        $seeds = Category::query()->create([
            'slug' => 'seeds',
            'code' => 'CAT-SEED',
            'status' => 'active',
            'name_en' => 'Seeds',
            'name_ur' => 'بیج',
        ]);

        $pumpSupplier = $this->makeSupplier('Pump Traders');
        $pumpSupplier->categories()->sync([$pumps->id]);

        $seedSupplier = $this->makeSupplier('Seed House');
        $seedSupplier->categories()->sync([$seeds->id]);

        $unassigned = $this->makeSupplier('No Category Co');

        Livewire::actingAs($admin)
            ->test(ListSuppliers::class)
            ->dispatch('suppliers-search', query: 'Special')
            ->assertSee('Pump Traders')
            ->assertDontSee('Seed House')
            ->assertDontSee('No Category Co');

        Livewire::actingAs($admin)
            ->test(ListSuppliers::class)
            ->dispatch('suppliers-search', query: 'بیج')
            ->assertSee('Seed House')
            ->assertDontSee('Pump Traders')
            ->assertDontSee('No Category Co');

        $this->assertNotNull($unassigned->id);
    }

    private function makeSupplier(string $businessName): Supplier
    {
        return Supplier::query()->create([
            'supplier_code' => 'SUP-'.strtoupper(substr(md5($businessName), 0, 6)),
            'supplier_type' => 'wholesaler',
            'status' => Supplier::STATUS_ACTIVE,
            'source' => Supplier::SOURCE_ADMIN,
            'business_name_en' => $businessName,
            'country_code' => 'PK',
            'city_en' => 'Lahore',
            'address_en' => 'Warehouse',
            'contacts' => [[
                'contact_name_en' => 'Contact',
                'contact_name_ur' => '',
                'mobile_number' => '03001234567',
            ]],
            'bank_accounts' => [],
            'opening_balance' => 0,
            'portal_enabled' => false,
        ]);
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_system' => true],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
