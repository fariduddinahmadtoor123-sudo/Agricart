<?php

namespace Tests\Feature\Store;

use App\Models\StoreSetting;
use App\Services\Store\StoreLayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterRegistrationLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_join_us_column_contains_staff_and_supplier_not_in_quick_links(): void
    {
        StoreSetting::current()->update([
            'footer_enabled' => true,
            'footer_show_login_link' => true,
            'footer_show_register_link' => true,
            'footer_registration_title_en' => 'Join Us',
        ]);

        $html = view(
            'store.components.footer',
            app(StoreLayoutService::class)->layoutData(),
        )->render();

        $this->assertStringContainsString('Join Us', $html);
        $this->assertStringContainsString(route('auth.staff.register'), $html);
        $this->assertStringContainsString(__('agricart.storefront.footer.staff'), $html);
        $this->assertStringContainsString(route('auth.supplier.register'), $html);
        $this->assertStringContainsString(__('agricart.storefront.footer.supplier'), $html);
        $this->assertStringNotContainsString(__('agricart.auth.register_title'), $html);
        $this->assertStringNotContainsString(__('agricart.supplier_register.title'), $html);
        $this->assertStringNotContainsString('href="'.route('store.customer.login').'"', $html);
        $this->assertStringNotContainsString('tab=register', $html);

        $quickPos = strpos($html, 'ag-store-footer__col--quick');
        $regPos = strpos($html, 'ag-store-footer__col--registration');
        $mapPos = strpos($html, 'ag-store-footer__col--map');
        $staffInReg = strpos($html, route('auth.staff.register'), $regPos ?: 0);

        $this->assertNotFalse($quickPos);
        $this->assertNotFalse($regPos);
        $this->assertNotFalse($mapPos);
        $this->assertNotFalse($staffInReg);
        $this->assertGreaterThan($regPos, $staffInReg);
        $this->assertLessThan($mapPos, $staffInReg);

        $staffInQuick = strpos(substr($html, $quickPos, $regPos - $quickPos), route('auth.staff.register'));
        $this->assertFalse($staffInQuick);
    }
}
