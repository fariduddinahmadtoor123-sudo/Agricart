<?php

namespace Tests\Feature\Auth;

use App\Livewire\Approvals\ListSupplierApprovals;
use App\Livewire\Auth\SupplierRegisterForm;
use App\Models\Category;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Contacts\SupplierApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicSupplierRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_includes_supplier_registration_link(): void
    {
        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee(route('auth.supplier.register'), false)
            ->assertSee(__('agricart.supplier_register.title'), false);
    }

    public function test_public_supplier_application_is_pending_and_visible_in_approvals(): void
    {
        $category = Category::query()->create([
            'slug' => 'seeders',
            'code' => 'CAT-SEED',
            'status' => 'active',
            'name_en' => 'Seeders',
            'name_ur' => 'سیڈرز',
        ]);

        $admin = $this->superAdmin();

        Livewire::test(SupplierRegisterForm::class)
            ->set('business_name_en', 'Green Fields Traders')
            ->set('business_name_ur', 'گرین فیلڈز ٹریڈرز')
            ->set('supplier_type', 'distributor')
            ->set('city_en', 'Lahore')
            ->set('address_en', '12 Canal Road, Lahore')
            ->set('contactRows.0.contact_name_en', 'Ali Supplier')
            ->set('contactRows.0.mobile_number', '03001234567')
            ->set('category_ids', [$category->id])
            ->set('custom_categories', 'Custom planter parts')
            ->set('application_note', 'We supply maize planter parts across Punjab.')
            ->set('terms_accepted', true)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true)
            ->assertSee(__('agricart.supplier_register.pending_approval'))
            ->assertDispatched('supplier-registration-result', success: true);

        $supplier = Supplier::query()->where('business_name_en', 'Green Fields Traders')->firstOrFail();

        $this->assertSame(Supplier::STATUS_PENDING, $supplier->status);
        $this->assertSame(Supplier::SOURCE_PUBLIC, $supplier->source);
        $this->assertNotNull($supplier->terms_accepted_at);
        $this->assertSame('Custom planter parts', $supplier->custom_categories);
        $this->assertTrue($supplier->categories->contains('id', $category->id));

        Livewire::actingAs($admin)
            ->test(ListSupplierApprovals::class)
            ->assertSee('Green Fields Traders')
            ->assertSee('03001234567');
    }

    public function test_admin_can_approve_and_reject_supplier_applications(): void
    {
        $admin = $this->superAdmin();
        $pending = Supplier::query()->create([
            'supplier_code' => 'SUP-90001',
            'supplier_type' => 'wholesaler',
            'status' => Supplier::STATUS_PENDING,
            'source' => Supplier::SOURCE_PUBLIC,
            'business_name_en' => 'Approve Me Supplies',
            'country_code' => 'PK',
            'city_en' => 'Multan',
            'address_en' => 'Shop 1',
            'contacts' => [[
                'contact_name_en' => 'Sami',
                'contact_name_ur' => '',
                'mobile_number' => '03007654321',
            ]],
            'bank_accounts' => [],
            'opening_balance' => 0,
            'portal_enabled' => false,
            'terms_accepted_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(ListSupplierApprovals::class)
            ->call('openApprove', $pending->id)
            ->call('confirmApprove')
            ->assertSet('flashType', 'success');

        $this->assertSame(Supplier::STATUS_ACTIVE, $pending->fresh()->status);

        $rejectable = Supplier::query()->create([
            'supplier_code' => 'SUP-90002',
            'supplier_type' => 'farmer',
            'status' => Supplier::STATUS_PENDING,
            'source' => Supplier::SOURCE_PUBLIC,
            'business_name_en' => 'Reject Me Farms',
            'country_code' => 'PK',
            'city_en' => 'Sahiwal',
            'address_en' => 'Farm 2',
            'contacts' => [[
                'contact_name_en' => 'Rashid',
                'contact_name_ur' => '',
                'mobile_number' => '03001112233',
            ]],
            'bank_accounts' => [],
            'opening_balance' => 0,
            'portal_enabled' => false,
            'terms_accepted_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(ListSupplierApprovals::class)
            ->call('openReject', $rejectable->id)
            ->set('rejectReason', 'Please add clearer category details.')
            ->call('confirmReject')
            ->assertSet('flashType', 'success');

        $rejectable->refresh();
        $this->assertSame(Supplier::STATUS_REJECTED, $rejectable->status);
        $this->assertSame('Please add clearer category details.', $rejectable->rejection_reason);
    }

    public function test_rejected_supplier_can_resubmit_with_same_phone(): void
    {
        Supplier::query()->create([
            'supplier_code' => 'SUP-90003',
            'supplier_type' => 'other',
            'status' => Supplier::STATUS_REJECTED,
            'source' => Supplier::SOURCE_PUBLIC,
            'business_name_en' => 'Old Name',
            'country_code' => 'PK',
            'city_en' => 'Faisalabad',
            'address_en' => 'Old address',
            'contacts' => [[
                'contact_name_en' => 'Old Contact',
                'contact_name_ur' => '',
                'mobile_number' => '03009998877',
            ]],
            'bank_accounts' => [],
            'opening_balance' => 0,
            'portal_enabled' => false,
            'rejection_reason' => 'Incomplete',
            'rejected_at' => now(),
            'terms_accepted_at' => now()->subDay(),
        ]);

        Livewire::test(SupplierRegisterForm::class)
            ->set('business_name_en', 'New Name Traders')
            ->set('supplier_type', 'distributor')
            ->set('city_en', 'Faisalabad')
            ->set('address_en', 'New address')
            ->set('contactRows.0.contact_name_en', 'New Contact')
            ->set('contactRows.0.mobile_number', '03009998877')
            ->set('terms_accepted', true)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true)
            ->assertSet('isResubmit', true);

        $supplier = Supplier::query()->where('contacts', 'like', '%03009998877%')->sole();
        $this->assertSame(Supplier::STATUS_PENDING, $supplier->status);
        $this->assertSame('New Name Traders', $supplier->business_name_en);
        $this->assertNull($supplier->rejection_reason);
    }

    public function test_contacts_list_excludes_pending_suppliers(): void
    {
        Supplier::query()->create([
            'supplier_code' => 'SUP-90004',
            'supplier_type' => 'retailer',
            'status' => Supplier::STATUS_PENDING,
            'source' => Supplier::SOURCE_PUBLIC,
            'business_name_en' => 'Hidden Pending Co',
            'country_code' => 'PK',
            'city_en' => 'Lahore',
            'address_en' => 'Hidden',
            'contacts' => [[
                'contact_name_en' => 'Hidden',
                'contact_name_ur' => '',
                'mobile_number' => '03005554433',
            ]],
            'bank_accounts' => [],
            'opening_balance' => 0,
            'portal_enabled' => false,
        ]);

        Supplier::query()->create([
            'supplier_code' => 'SUP-90005',
            'supplier_type' => 'retailer',
            'status' => Supplier::STATUS_ACTIVE,
            'source' => Supplier::SOURCE_ADMIN,
            'business_name_en' => 'Visible Active Co',
            'country_code' => 'PK',
            'city_en' => 'Lahore',
            'address_en' => 'Visible',
            'contacts' => [[
                'contact_name_en' => 'Visible',
                'contact_name_ur' => '',
                'mobile_number' => '03005554434',
            ]],
            'bank_accounts' => [],
            'opening_balance' => 0,
            'portal_enabled' => false,
        ]);

        $admin = $this->superAdmin();

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Suppliers\ListSuppliers::class)
            ->assertSee('Visible Active Co')
            ->assertDontSee('Hidden Pending Co');
    }

    public function test_approval_service_requires_reason_on_reject(): void
    {
        $supplier = Supplier::query()->create([
            'supplier_code' => 'SUP-90006',
            'supplier_type' => 'importer',
            'status' => Supplier::STATUS_PENDING,
            'source' => Supplier::SOURCE_PUBLIC,
            'business_name_en' => 'Need Reason',
            'country_code' => 'PK',
            'city_en' => 'Karachi',
            'address_en' => 'Port',
            'contacts' => [[
                'contact_name_en' => 'Port Contact',
                'contact_name_ur' => '',
                'mobile_number' => '0211234567',
            ]],
            'bank_accounts' => [],
            'opening_balance' => 0,
            'portal_enabled' => false,
        ]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(SupplierApprovalService::class)->reject($supplier, '   ');
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
