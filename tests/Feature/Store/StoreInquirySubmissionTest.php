<?php

namespace Tests\Feature\Store;

use App\Models\AgricartConversation;
use App\Models\Customer;
use App\Models\StoreInquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StoreInquirySubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_displays_chat_fab_and_login_gate_for_guests(): void
    {
        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('data-store-message-open', false)
            ->assertSee('data-store-message-gate', false)
            ->assertSee(__('agricart.storefront.contact.fab_label'), false)
            ->assertDontSee(route('store.contact-message'));
    }

    public function test_guest_contact_message_redirects_to_login_without_storing(): void
    {
        $this->post(route('store.contact-message'), [
            'name' => 'Ali Khan',
            'phone' => '0300 1234567',
            'email' => 'ali@example.com',
            'message' => 'Please contact me about wheat seed.',
            'source_url' => route('store.home'),
            'company_website' => '',
        ])
            ->assertRedirect(route('store.customer.login', ['redirect' => '/']))
            ->assertSessionHas('customer_auth_status');

        $this->assertDatabaseCount('store_inquiries', 0);
        $this->assertDatabaseCount('agricart_conversations', 0);
    }

    public function test_customer_can_submit_a_store_message(): void
    {
        $customer = $this->makeCustomer('chat-submit@test.test');

        $response = $this
            ->actingAs($customer, 'customer')
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->post(route('store.contact-message'), [
                'message' => 'Please contact me about wheat seed.',
                'source_url' => route('store.home'),
                'company_website' => '',
            ]);

        $conversation = AgricartConversation::query()->sole();

        $response
            ->assertRedirect(route('store.customer.inbox.show', $conversation->id))
            ->assertSessionHas('store_message_sent');

        $inquiry = StoreInquiry::query()->sole();
        $this->assertSame($customer->displayName(), $inquiry->name);
        $this->assertSame(strtolower((string) $customer->email), $inquiry->email);
        $this->assertSame(StoreInquiry::STATUS_NEW, $inquiry->status);
        $this->assertNotSame('203.0.113.10', $inquiry->ip_hash);
        $this->assertSame(64, strlen((string) $inquiry->ip_hash));

        $this->assertSame(AgricartConversation::TOPIC_CONTACT, $conversation->topic);
        $this->assertSame($inquiry->id, $conversation->store_inquiry_id);
        $this->assertSame($customer->id, $conversation->customer_id);
    }

    public function test_honeypot_submission_is_accepted_without_storing_a_message(): void
    {
        $customer = $this->makeCustomer('honeypot@test.test');

        $this->actingAs($customer, 'customer')
            ->post(route('store.contact-message'), [
                'company_website' => 'https://spam.example',
            ])
            ->assertSessionHas('store_message_sent');

        $this->assertDatabaseCount('store_inquiries', 0);
        $this->assertDatabaseCount('agricart_conversations', 0);
    }

    public function test_store_message_requires_message_body(): void
    {
        $customer = $this->makeCustomer('validate@test.test');

        $this->actingAs($customer, 'customer')
            ->post(route('store.contact-message'), [
                'message' => 'x',
            ])
            ->assertSessionHasErrors(['message'], null, 'storeMessage');
    }

    private function makeCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'C-CHAT-'.substr(md5($email), 0, 8),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Chat',
            'last_name' => 'Customer',
            'email' => $email,
            'phone' => '03001112233',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
    }
}
