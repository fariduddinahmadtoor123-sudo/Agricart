<?php

namespace Tests\Feature\Store;

use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontWhatsAppRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_has_messenger_float_but_no_whatsapp_chat_link(): void
    {
        StoreSetting::current()->update([
            'whatsapp_enabled' => true,
            'whatsapp_number' => '923001234567',
            'social_whatsapp' => 'https://wa.me/923001234567',
        ]);

        $this->get(route('store.home'))
            ->assertOk()
            ->assertSee('data-store-message-open', false)
            ->assertSee('ag-store-message-float', false)
            ->assertSee(__('agricart.storefront.contact.fab_label'), false)
            ->assertDontSee('ag-store-whatsapp-float', false)
            ->assertDontSee('https://wa.me/', false)
            ->assertDontSee('api.whatsapp.com', false);
    }
}
