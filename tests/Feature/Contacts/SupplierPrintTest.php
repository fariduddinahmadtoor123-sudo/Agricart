<?php

namespace Tests\Feature\Contacts;

use App\Livewire\Suppliers\ListSuppliers;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_print_suppliers(): void
    {
        $this->get(route('admin.suppliers.print'))->assertRedirect();
    }

    public function test_staff_can_print_one_supplier_with_filled_fields(): void
    {
        $admin = $this->superAdmin();
        $supplier = $this->makeSupplier('Print One Traders', '03001110001');

        $this->actingAs($admin)
            ->get(route('admin.suppliers.print', ['ids' => (string) $supplier->id, 'autoprint' => '0']))
            ->assertOk()
            ->assertSee('Print One Traders', false)
            ->assertSee('03001110001', false)
            ->assertSee('Lahore', false)
            ->assertDontSee('secret-password', false);
    }

    public function test_staff_can_print_selected_and_filtered_directory(): void
    {
        $admin = $this->superAdmin();
        $first = $this->makeSupplier('Alpha Parts');
        $second = $this->makeSupplier('Beta Hardware');
        $this->makeSupplier('Gamma Skipped');

        $this->actingAs($admin)
            ->get(route('admin.suppliers.print', [
                'ids' => $first->id.','.$second->id,
                'autoprint' => '0',
            ]))
            ->assertOk()
            ->assertSee('Alpha Parts', false)
            ->assertSee('Beta Hardware', false)
            ->assertDontSee('Gamma Skipped', false);

        $this->actingAs($admin)
            ->get(route('admin.suppliers.print', ['q' => 'Alpha', 'autoprint' => '0']))
            ->assertOk()
            ->assertSee('Alpha Parts', false)
            ->assertDontSee('Beta Hardware', false);
    }

    public function test_list_print_actions_dispatch_print_url(): void
    {
        $admin = $this->superAdmin();
        $supplier = $this->makeSupplier('Livewire Print Co');

        Livewire::actingAs($admin)
            ->test(ListSuppliers::class)
            ->set('selectedIds', [(string) $supplier->id])
            ->call('printSelected')
            ->assertDispatched('suppliers-open-print');

        Livewire::actingAs($admin)
            ->test(ListSuppliers::class)
            ->call('printSupplier', $supplier->id)
            ->assertDispatched('suppliers-open-print');

        Livewire::actingAs($admin)
            ->test(ListSuppliers::class)
            ->call('printFiltered')
            ->assertDispatched('suppliers-open-print');
    }

    private function makeSupplier(string $businessName, string $mobile = '03001234567'): Supplier
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
                'mobile_number' => $mobile,
            ]],
            'bank_accounts' => [[
                'bank_name_en' => 'HBL',
                'bank_name_ur' => '',
                'account_title' => 'Print Account',
                'account_title_ur' => '',
                'account_number' => 'PK00HABB0000001234567890',
            ]],
            'opening_balance' => 0,
            'portal_enabled' => false,
            'password' => 'secret-password',
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
