<?php

namespace Tests\Feature\Settings;

use App\Models\CompanyProfile;
use App\Models\StoreSetting;
use App\Services\Settings\CompanyProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyProfileWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_whatsapp_number_is_normalized_but_storefront_chat_is_disabled(): void
    {
        app(CompanyProfileService::class)->save([
            'whatsapp_number' => '0300 1234567',
        ]);

        $this->assertSame('923001234567', CompanyProfile::current()->whatsapp_number);
        $this->assertNull(StoreSetting::current()->whatsappChatUrl());
    }

    public function test_storefront_never_exposes_whatsapp_chat_url(): void
    {
        StoreSetting::current()->update([
            'whatsapp_enabled' => true,
            'whatsapp_number' => '923009999999',
        ]);
        CompanyProfile::current()->update([
            'whatsapp_number' => '923001111111',
        ]);

        $this->assertNull(StoreSetting::current()->whatsappChatUrl('Hello'));
    }
}
