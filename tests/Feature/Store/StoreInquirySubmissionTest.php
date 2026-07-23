<?php

namespace Tests\Feature\Store;

use App\Models\AgricartConversation;
use App\Models\StoreInquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreInquirySubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_displays_internal_message_button_and_form(): void
    {
        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('data-store-message-open', false)
            ->assertSee(route('store.contact-message'), false);
    }

    public function test_customer_can_submit_a_store_message(): void
    {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->post(route('store.contact-message'), [
                'name' => 'Ali Khan',
                'phone' => '0300 1234567',
                'email' => 'ALI@example.com',
                'message' => 'Please contact me about wheat seed.',
                'source_url' => route('store.home'),
                'company_website' => '',
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('store_message_sent');

        $inquiry = StoreInquiry::query()->sole();
        $this->assertSame('Ali Khan', $inquiry->name);
        $this->assertSame('ali@example.com', $inquiry->email);
        $this->assertSame(StoreInquiry::STATUS_NEW, $inquiry->status);
        $this->assertNotSame('203.0.113.10', $inquiry->ip_hash);
        $this->assertSame(64, strlen((string) $inquiry->ip_hash));

        $conversation = AgricartConversation::query()->sole();
        $this->assertSame(AgricartConversation::TOPIC_CONTACT, $conversation->topic);
        $this->assertSame($inquiry->id, $conversation->store_inquiry_id);
        $this->assertSame('Ali Khan', $conversation->guest_name);
    }

    public function test_honeypot_submission_is_accepted_without_storing_a_message(): void
    {
        $this->post(route('store.contact-message'), [
            'company_website' => 'https://spam.example',
        ])->assertSessionHas('store_message_sent');

        $this->assertDatabaseCount('store_inquiries', 0);
        $this->assertDatabaseCount('agricart_conversations', 0);
    }

    public function test_store_message_requires_contact_details_and_message(): void
    {
        $this->post(route('store.contact-message'), [
            'name' => '',
            'phone' => 'not-a-number',
            'message' => 'x',
        ])->assertSessionHasErrors(['name', 'phone', 'message'], null, 'storeMessage');
    }
}
