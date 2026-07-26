<?php

namespace Tests\Feature\Locale;

use App\Support\AgricartLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStoreLocaleIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_locale_switch_sets_store_cookie_only(): void
    {
        $response = $this->get('/locale/ur?scope=store&redirect='.urlencode('/'));

        $response->assertRedirect('/');
        $response->assertCookie(AgricartLocale::STORE_COOKIE, 'ur');
        $response->assertCookieMissing(AgricartLocale::ADMIN_COOKIE);
    }

    public function test_admin_locale_switch_sets_admin_cookie_only(): void
    {
        $response = $this->get('/locale/ur?scope=admin&redirect='.urlencode('/admin'));

        $response->assertRedirect('/admin');
        $response->assertCookie(AgricartLocale::ADMIN_COOKIE, 'ur');
        $response->assertCookieMissing(AgricartLocale::STORE_COOKIE);
    }

    public function test_admin_redirect_infers_admin_scope_without_query(): void
    {
        $response = $this->get('/locale/en?redirect='.urlencode('/admin/catalog/categories'));

        $response->assertRedirect('/admin/catalog/categories');
        $response->assertCookie(AgricartLocale::ADMIN_COOKIE, 'en');
    }

    public function test_storefront_keeps_own_locale_when_admin_cookie_is_urdu(): void
    {
        $this->withCookie(AgricartLocale::ADMIN_COOKIE, 'ur')
            ->withCookie(AgricartLocale::STORE_COOKIE, 'en')
            ->get('/')
            ->assertOk();

        $this->assertSame('en', app()->getLocale());
    }

    public function test_admin_path_uses_admin_cookie_not_store_cookie(): void
    {
        $this->withCookie(AgricartLocale::ADMIN_COOKIE, 'en')
            ->withCookie(AgricartLocale::STORE_COOKIE, 'ur')
            ->get('/admin/login')
            ->assertOk();

        $this->assertSame('en', app()->getLocale());
    }
}
