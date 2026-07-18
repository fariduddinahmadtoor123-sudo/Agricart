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

    public function test_business_whatsapp_number_is_normalized_and_used_by_storefront(): void
    {
        $profile = app(CompanyProfileService::class)->save([
            'business_name_en' => 'Agricart',
            'whatsapp_number' => '0300 1234567',
        ]);

        $this->assertSame('923001234567', $profile->whatsapp_number);
        $this->assertSame('923001234567', StoreSetting::current()->whatsapp_number);
        $this->assertSame('https://wa.me/923001234567', StoreSetting::current()->whatsappChatUrl());
    }

    public function test_company_profile_number_takes_priority_over_legacy_store_number(): void
    {
        StoreSetting::current()->update(['whatsapp_number' => '923009999999']);
        CompanyProfile::current()->update(['whatsapp_number' => '923001111111']);

        $this->assertSame('https://wa.me/923001111111', StoreSetting::current()->whatsappChatUrl());
    }
}
