<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderStaffAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ListOrderDispatchQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_still_needs_forward_scope_excludes_orders_with_staff_sent_assignment(): void
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-DISP-1',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Dispatch',
            'last_name' => 'Test',
            'display_name' => 'Dispatch Test',
            'name_en' => 'Dispatch Test',
            'email' => 'dispatch-query@test.test',
            'phone' => '03001234567',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        $pending = OnlineSaleOrder::query()->create([
            'order_number' => 'ONL-DISP-PENDING',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'order_status' => OnlineSaleOrder::STATUS_CONVERTED,
            'payment_status' => 'paid',
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 100,
            'queued_at' => now(),
        ]);

        $forwarded = OnlineSaleOrder::query()->create([
            'order_number' => 'ONL-DISP-FWD',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'order_status' => OnlineSaleOrder::STATUS_CONVERTED,
            'payment_status' => 'paid',
            'subtotal' => 200,
            'total_amount' => 200,
            'amount_paid' => 200,
            'queued_at' => now(),
        ]);

        $staff = User::factory()->create();

        OnlineSaleOrderStaffAssignment::query()->create([
            'online_sale_order_id' => $forwarded->id,
            'user_id' => $staff->id,
            'document' => OnlineSaleOrderStaffAssignment::DOCUMENT_INVOICE,
            'status' => OnlineSaleOrderStaffAssignment::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $visibleNumbers = OnlineSaleOrder::query()
            ->where('order_status', OnlineSaleOrder::STATUS_CONVERTED)
            ->whereNotNull('queued_at')
            ->stillNeedsForward()
            ->orderBy('order_number')
            ->pluck('order_number')
            ->all();

        $this->assertSame(['ONL-DISP-PENDING'], $visibleNumbers);
        $this->assertTrue(
            OnlineSaleOrder::query()
                ->whereKey($pending->id)
                ->stillNeedsForward()
                ->exists(),
        );
        $this->assertFalse(
            OnlineSaleOrder::query()
                ->whereKey($forwarded->id)
                ->stillNeedsForward()
                ->exists(),
        );
    }
}
