<?php

namespace Tests\Feature\Store;

use App\Models\AnalyticsSession;
use App\Models\AnalyticsVisitor;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Analytics\VisitorAnalyticsTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorAnalyticsTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'analytics.enabled' => true,
            'analytics.hash_key' => 'test-analytics-key-that-is-separate-from-app-key',
            'analytics.geoip_database' => storage_path('app/private/testing-missing.mmdb'),
        ]);
    }

    public function test_storefront_html_view_is_recorded_without_raw_ip(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/126.0 Safari/537.36')
            ->get(route('store.home'));

        $response
            ->assertOk()
            ->assertCookie((string) config('analytics.visitor_cookie'))
            ->assertCookie((string) config('analytics.session_cookie'));

        $this->assertDatabaseCount('analytics_visitors', 1);
        $this->assertDatabaseCount('analytics_sessions', 1);
        $this->assertDatabaseHas('analytics_page_views', [
            'route_name' => 'store.home',
            'page_type' => 'home',
            'response_status' => 200,
        ]);

        $session = AnalyticsSession::query()->firstOrFail();
        $this->assertSame('8.8.8.xxx', $session->masked_ip);
        $this->assertNotSame('8.8.8.8', $session->ip_hash);
        $this->assertSame(64, strlen((string) $session->ip_hash));
        $this->assertSame('desktop', $session->device_type);
        $this->assertDatabaseMissing('analytics_sessions', ['masked_ip' => '8.8.8.8']);
    }

    public function test_product_context_and_external_source_are_recorded(): void
    {
        $category = Category::query()->create([
            'slug' => 'analytics-category',
            'status' => 'active',
            'name_en' => 'Analytics Category',
            'name_ur' => 'اینالیٹکس زمرہ',
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'analytics-product',
            'code' => 'AN-1',
            'status' => 'active',
            'name_en' => 'Analytics Product',
            'name_ur' => 'اینالیٹکس پراڈکٹ',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '1.1.1.1'])
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Mobile Safari/537.36',
                'Referer' => 'https://www.google.com/search?q=agriculture',
            ])
            ->get(route('store.product', $product->slug))
            ->assertOk();

        $this->assertDatabaseHas('analytics_page_views', [
            'page_type' => 'product',
            'entity_id' => $product->id,
            'entity_slug' => $product->slug,
            'traffic_source' => 'google',
        ]);
    }

    public function test_bots_privacy_signals_and_internal_fragments_are_excluded(): void
    {
        $this->withHeader('User-Agent', 'Googlebot/2.1 (+http://www.google.com/bot.html)')
            ->get(route('store.home'))
            ->assertOk();

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0',
            'DNT' => '1',
        ])->get(route('store.home'))->assertOk();

        $this->withHeader('User-Agent', 'Mozilla/5.0')
            ->get(route('store.search.fragment', ['q' => 'seed']))
            ->assertOk();

        $this->assertDatabaseCount('analytics_page_views', 0);
        $this->assertDatabaseCount('analytics_visitors', 0);
    }

    public function test_authenticated_staff_browsing_and_opted_out_visitors_are_excluded(): void
    {
        $role = Role::query()->create([
            'name' => 'Administrator',
            'slug' => 'administrator',
        ]);
        $staff = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($staff)
            ->withHeader('User-Agent', 'Mozilla/5.0')
            ->get(route('store.home'))
            ->assertOk();

        auth()->logout();

        $this->withCookie((string) config('analytics.opt_out_cookie'), '1')
            ->withHeader('User-Agent', 'Mozilla/5.0')
            ->get(route('store.home'))
            ->assertOk();

        $this->assertDatabaseCount('analytics_page_views', 0);
    }

    public function test_repeat_views_with_first_party_tokens_share_one_visitor_and_session(): void
    {
        $visitorToken = str_repeat('v', 64);
        $sessionToken = str_repeat('s', 64);

        for ($visit = 0; $visit < 2; $visit++) {
            $this->withCookie((string) config('analytics.visitor_cookie'), $visitorToken)
                ->withCookie((string) config('analytics.session_cookie'), $sessionToken)
                ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126.0')
                ->get(route('store.home'))
                ->assertOk();
        }

        $this->assertDatabaseCount('analytics_visitors', 1);
        $this->assertDatabaseCount('analytics_sessions', 1);
        $this->assertDatabaseCount('analytics_page_views', 2);
        $this->assertSame(2, AnalyticsSession::query()->firstOrFail()->page_views);
    }

    public function test_presence_heartbeat_updates_recent_session_only(): void
    {
        $tracker = app(VisitorAnalyticsTracker::class);
        $token = str_repeat('a', 64);
        $visitor = AnalyticsVisitor::query()->create([
            'token_hash' => $tracker->tokenHash(str_repeat('v', 64)),
            'first_seen_at' => now()->subMinutes(10),
            'last_seen_at' => now()->subMinutes(10),
        ]);
        $session = AnalyticsSession::query()->create([
            'visitor_id' => $visitor->id,
            'token_hash' => $tracker->tokenHash($token),
            'started_at' => now()->subMinutes(10),
            'last_seen_at' => now()->subMinutes(2),
        ]);

        $this->withCookie((string) config('analytics.session_cookie'), $token)
            ->post(route('store.analytics.presence'))
            ->assertNoContent();

        $this->assertTrue($session->fresh()->last_seen_at->greaterThan(now()->subSeconds(10)));
    }
}
