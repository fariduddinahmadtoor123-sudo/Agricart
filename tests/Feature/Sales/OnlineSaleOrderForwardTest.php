<?php

namespace Tests\Feature\Sales;

use App\Livewire\OnlineSaleOrders\ListOrderDispatch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\OnlineSaleOrderSupplierAssignment;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Sales\OnlineOrderDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class OnlineSaleOrderForwardTest extends TestCase
{
    use RefreshDatabase;

    public function test_forward_panel_lists_category_suppliers_and_excludes_unrelated(): void
    {
        [$admin, $order] = $this->seedForwardScenario();

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->call('openForward', $order->id)
            ->assertSet('forwardOrderId', $order->id)
            ->assertSee('Tor Hardware', false)
            ->assertDontSee('Unrelated Supply Co', false)
            ->assertSee($admin->name, false);
    }

    public function test_forward_whatsapp_uses_supplier_primary_mobile_and_marks_sent(): void
    {
        [$admin, $order, $matched] = $this->seedForwardScenario();

        Livewire::actingAs($admin)
            ->test(ListOrderDispatch::class)
            ->call('openForward', $order->id)
            ->call('forwardWhatsApp', 'supplier', $matched->id)
            ->assertDispatched('online-sale-orders-open-external');

        $assignment = OnlineSaleOrderSupplierAssignment::query()
            ->where('online_sale_order_id', $order->id)
            ->where('supplier_id', $matched->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertSame(OnlineSaleOrderSupplierAssignment::STATUS_SENT, $assignment->status);

        $url = app(OnlineOrderDispatchService::class)->whatsAppUrlForSupplier($order->fresh('items'), $matched, 'packing');
        $this->assertNotNull($url);
        $this->assertStringContainsString('wa.me/923001112233', $url);
        $this->assertStringContainsString(rawurlencode('Tor Hardware'), $url);
    }

    public function test_packing_slip_shows_recipient_business_name(): void
    {
        [$admin, $order, $matched] = $this->seedForwardScenario();

        $this->actingAs($admin)
            ->get(route('admin.online-sale-orders.packing-slip', [
                'order' => $order,
                'supplier_id' => $matched->id,
                'autoprint' => 0,
            ]))
            ->assertOk()
            ->assertSee('To: Tor Hardware', false)
            ->assertSee($order->order_number, false);
    }

    public function test_forward_staff_whatsapp_uses_primary_phone(): void
    {
        [$admin, $order] = $this->seedForwardScenario();

        $staff = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Packing Staff',
            'email' => 'packing-staff@test.test',
            'phone_numbers' => [
                [
                    'contact_person' => 'Packing Staff',
                    'contact_person_ur' => 'Packing Staff',
                    'phone_number' => '03009998877',
                ],
            ],
        ]);

        $url = app(OnlineOrderDispatchService::class)->whatsAppUrlForStaff($order->fresh('items'), $staff, 'packing');

        $this->assertNotNull($url);
        $this->assertStringContainsString('wa.me/923009998877', $url);
        $this->assertStringContainsString(rawurlencode('Packing Staff'), $url);
    }

    /**
     * @return array{0: User, 1: OnlineSaleOrder, 2: Supplier, 3: Supplier}
     */
    private function seedForwardScenario(): array
    {
        $admin = $this->superAdmin();

        $category = Category::query()->create([
            'slug' => 'agriculture-machinery',
            'status' => 'active',
            'name_en' => 'Agriculture Machinery',
            'name_ur' => 'Agriculture Machinery',
        ]);

        $otherCategory = Category::query()->create([
            'slug' => 'unrelated-parts',
            'status' => 'active',
            'name_en' => 'Unrelated Parts',
            'name_ur' => 'Unrelated Parts',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'boom-sprayer',
            'code' => 'BOOM-1',
            'status' => 'active',
            'name_en' => 'Boom Sprayer',
            'name_ur' => 'Boom Sprayer',
        ]);

        $matched = Supplier::query()->create([
            'supplier_code' => 'SUP-TOR1',
            'status' => 'active',
            'business_name_en' => 'Tor Hardware',
            'business_name_ur' => 'Tor Hardware',
            'email' => 'tor@test.test',
            'address_en' => '1 Test Street',
            'city_en' => 'Lahore',
            'opening_balance' => 0,
            'contacts' => [
                ['mobile_number' => '03001112233', 'contact_name_en' => 'Tor'],
            ],
        ]);
        $matched->categories()->sync([$category->id]);

        $other = Supplier::query()->create([
            'supplier_code' => 'SUP-OTH1',
            'status' => 'active',
            'business_name_en' => 'Unrelated Supply Co',
            'business_name_ur' => 'Unrelated Supply Co',
            'email' => 'other@test.test',
            'address_en' => '2 Test Street',
            'city_en' => 'Lahore',
            'opening_balance' => 0,
            'contacts' => [
                ['mobile_number' => '03004445566', 'contact_name_en' => 'Other'],
            ],
        ]);
        $other->categories()->sync([$otherCategory->id]);

        $customer = Customer::query()->create([
            'customer_number' => 'CUS-FWD01',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Forward',
            'last_name' => 'Buyer',
            'email' => 'forward-buyer@test.test',
            'phone' => '03001234567',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'ONL-FWD-1001',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_CONVERTED,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'payment_status' => 'paid',
            'subtotal' => 500,
            'total_amount' => 500,
            'amount_paid' => 500,
            'ship_contact_name' => 'Forward Buyer',
            'ship_phone' => '03001234567',
            'converted_at' => now(),
            'queued_at' => now(),
        ]);

        OnlineSaleOrderItem::query()->create([
            'online_sale_order_id' => $order->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => 'Boom Sprayer',
            'quantity' => 2,
            'unit_price' => 250,
            'line_total' => 500,
        ]);

        return [$admin, $order->fresh('items'), $matched, $other];
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
            'name' => 'Admin Forward',
            'email' => 'admin-forward@test.test',
            'phone_numbers' => [
                [
                    'contact_person' => 'Admin Forward',
                    'contact_person_ur' => 'Admin Forward',
                    'phone_number' => '03005556677',
                ],
            ],
        ]);
    }
}
