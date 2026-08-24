<?php

namespace Tests\Feature\Access;

use App\Livewire\OnlineSaleOrders\Form as OnlineOrderForm;
use App\Livewire\PriceControls\Form as PriceControlForm;
use App\Livewire\QuantityControls\Form as QuantityControlForm;
use App\Livewire\Roles\Form as RoleForm;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class FormAuthorizationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_unprivileged_user_cannot_open_sensitive_forms(): void
    {
        $role = Role::query()->create(['name' => 'Viewer', 'slug' => 'viewer']);
        $permission = Permission::query()->create([
            'key' => 'catalog.view',
            'group_key' => 'catalog',
            'label_en' => 'View',
            'sort_order' => 1,
        ]);
        $role->permissions()->attach($permission);

        $this->actingAs(User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]));

        Livewire::test(PriceControlForm::class)->call('openForm')->assertForbidden();
        Livewire::test(QuantityControlForm::class)->call('openForm')->assertForbidden();
        Livewire::test(RoleForm::class)->call('openForm')->assertForbidden();

        $order = $this->makeOrder();
        Livewire::test(OnlineOrderForm::class)->call('openForm', $order->id, 'view')->assertForbidden();
        Livewire::test(OnlineOrderForm::class)->call('openForm', $order->id, 'edit')->assertForbidden();
    }

    public function test_super_admin_can_open_sensitive_forms(): void
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);

        $this->actingAs(User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]));

        Livewire::test(PriceControlForm::class)->call('openForm')->assertOk();
        Livewire::test(QuantityControlForm::class)->call('openForm')->assertOk();
        Livewire::test(RoleForm::class)->call('openForm')->assertOk();

        $order = $this->makeOrder();
        Livewire::test(OnlineOrderForm::class)->call('openForm', $order->id, 'view')->assertOk();
    }

    private function makeOrder(): OnlineSaleOrder
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-AUTH1',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Auth',
            'last_name' => 'Buyer',
            'email' => 'auth-buyer@test.test',
            'phone' => '03009998877',
            'password' => Hash::make('password'),
            'address_line1' => '1 Auth Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        Category::query()->create([
            'slug' => 'auth-cat',
            'status' => 'active',
            'name_en' => 'Auth Cat',
            'name_ur' => 'Auth Cat',
        ]);

        return OnlineSaleOrder::query()->create([
            'order_number' => 'ONL-AUTH-1',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 0,
            'ship_contact_name' => 'Auth Buyer',
            'ship_phone' => '03009998877',
        ]);
    }
}
