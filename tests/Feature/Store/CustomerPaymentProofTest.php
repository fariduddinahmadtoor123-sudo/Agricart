<?php

namespace Tests\Feature\Store;

use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerPaymentProofTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_only_notify_closes_the_upload_form(): void
    {
        [$customer, $order] = $this->awaitingBankOrder();

        $this->actingAs($customer, 'customer')
            ->from(route('store.customer.orders.show', $order->order_number))
            ->post(route('store.customer.orders.payment-proof', $order->order_number), [
                'customer_payment_reference' => 'TID-998877',
            ])
            ->assertRedirect(route('store.customer.orders.show', $order->order_number))
            ->assertSessionHas('customer_auth_status', __('agricart.customer.orders.payment_proof_submitted'));

        $order->refresh();

        $this->assertSame('TID-998877', $order->customer_payment_reference);
        $this->assertSame(OnlineSaleOrder::PROOF_SUBMITTED, $order->customer_payment_proof_status);
        $this->assertFalse($order->requiresCustomerPaymentProof());

        $this->actingAs($customer, 'customer')
            ->get(route('store.customer.orders.show', $order->order_number))
            ->assertOk()
            ->assertSee('TID-998877', false)
            ->assertDontSee(__('agricart.customer.orders.submit_payment_proof'), false);
    }

    /**
     * @return array{0: Customer, 1: OnlineSaleOrder}
     */
    private function awaitingBankOrder(): array
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-PROOF-1',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Pay',
            'last_name' => 'Ref',
            'email' => 'pay-ref@test.test',
            'phone' => '03001112233',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-PROOF-1',
            'customer_id' => $customer->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'payment_status' => 'unpaid',
            'customer_payment_method' => 'bank_transfer',
            'customer_payment_proof_status' => OnlineSaleOrder::PROOF_AWAITING,
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 0,
            'ship_contact_name' => $customer->displayName(),
            'ship_phone' => $customer->phone,
        ]);

        return [$customer, $order];
    }
}
