<?php

namespace Tests\Feature\Store;

use App\Models\StoreSetting;
use App\Services\Store\StoreLayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterRegistrationLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_column_contains_staff_application_not_customer_auth_links(): void
    {
        StoreSetting::current()->update([
            'footer_enabled' => true,
            'footer_show_login_link' => true,
            'footer_show_register_link' => true,
        ]);

        $html = view(
            'store.components.footer',
            app(StoreLayoutService::class)->layoutData(),
        )->render();

        $this->assertStringContainsString(route('auth.staff.register'), $html);
        $this->assertStringContainsString(__('agricart.auth.register_title'), $html);
        $this->assertStringNotContainsString('href="'.route('store.customer.login').'"', $html);
        $this->assertStringNotContainsString('tab=register', $html);
    }
}
