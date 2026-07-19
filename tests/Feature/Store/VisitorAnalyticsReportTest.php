<?php

namespace Tests\Feature\Store;

use App\Livewire\Store\VisitorAnalyticsDashboard;
use App\Models\AnalyticsPageView;
use App\Models\AnalyticsSession;
use App\Models\AnalyticsVisitor;
use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Analytics\AnalyticsRollupService;
use App\Services\Analytics\VisitorAnalyticsReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VisitorAnalyticsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_calculates_kpis_rankings_geography_devices_and_sources(): void
    {
        [$visitor, $session] = $this->analyticsIdentity();
        $category = Category::query()->create([
            'slug' => 'popular-category',
            'status' => 'active',
            'name_en' => 'Popular Category',
            'name_ur' => 'مقبول زمرہ',
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'popular-product',
            'code' => 'POPULAR-1',
            'status' => 'active',
            'name_en' => 'Popular Product',
            'name_ur' => 'مقبول پراڈکٹ',
        ]);

        $this->pageView($visitor->id, $session->id, 'product', $product->id, $product->slug);
        $this->pageView($visitor->id, $session->id, 'category', $category->id, $category->slug);

        $report = app(VisitorAnalyticsReportService::class)->report(
            CarbonImmutable::today()->subDay(),
            CarbonImmutable::today()->endOfDay(),
        );

        $this->assertSame(1, $report['kpis']['today']);
        $this->assertSame(2, $report['kpis']['total_page_views']);
        $this->assertSame(1, $report['selected']['visitors']);
        $this->assertSame('Popular Product', $report['top_products'][0]['label']);
        $this->assertSame('Popular Category', $report['top_categories'][0]['label']);
        $this->assertSame('Pakistan', $report['countries'][0]['label']);
        $this->assertSame('Lahore', $report['cities'][0]['label']);
        $this->assertSame('mobile', $report['devices'][0]['label']);
        $this->assertSame('facebook', $report['sources'][0]['label']);
    }

    public function test_rollups_and_pruning_preserve_compact_history(): void
    {
        [$visitor, $session] = $this->analyticsIdentity(now()->subDays(100));
        $occurredAt = CarbonImmutable::today()->subDays(100)->setHour(12);
        $this->pageView($visitor->id, $session->id, 'home', null, null, $occurredAt);

        app(AnalyticsRollupService::class)->rollupDay($occurredAt);

        $this->assertDatabaseHas('analytics_rollups', [
            'grain' => 'day',
            'period_start' => $occurredAt->toDateString(),
            'dimension_type' => 'all',
            'page_views' => 1,
            'visitors' => 1,
        ]);

        $this->artisan('analytics:prune', ['--raw-days' => 90, '--visitor-days' => 760])
            ->assertSuccessful();

        $this->assertDatabaseCount('analytics_page_views', 0);
        $this->assertDatabaseCount('analytics_sessions', 0);
        $this->assertDatabaseCount('analytics_visitors', 1);
        $this->assertDatabaseCount('analytics_rollups', 5);
    }

    public function test_analytics_page_requires_dedicated_permission_and_exports_csv(): void
    {
        $permission = Permission::query()->create([
            'key' => 'store.analytics.view',
            'group_key' => 'store',
            'label_en' => 'View visitor analytics',
            'label_ur' => 'وزیٹر اینالیٹکس دیکھیں',
            'sort_order' => 1,
        ]);
        $storeView = Permission::query()->create([
            'key' => 'store.view',
            'group_key' => 'store',
            'label_en' => 'View store',
            'label_ur' => 'اسٹور دیکھیں',
            'sort_order' => 0,
        ]);
        $role = Role::query()->create([
            'name' => 'Analytics Viewer',
            'slug' => 'analytics-viewer',
        ]);
        $role->permissions()->attach([$storeView->id, $permission->id]);
        $authorized = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
        $unauthorizedRole = Role::query()->create([
            'name' => 'Store Viewer',
            'slug' => 'store-viewer',
        ]);
        $unauthorizedRole->permissions()->attach($storeView);
        $unauthorized = User::factory()->create([
            'role_id' => $unauthorizedRole->id,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($unauthorized)
            ->get('/admin/store/visitor-analytics')
            ->assertForbidden();

        $this->actingAs($authorized)
            ->get('/admin/store/visitor-analytics')
            ->assertOk()
            ->assertSee('Visitor Analytics');

        Livewire::actingAs($authorized)
            ->test(VisitorAnalyticsDashboard::class)
            ->assertSee(__('agricart.analytics.total_visitors'))
            ->assertSee(__('agricart.analytics.daily_graph'));

        $this->actingAs($authorized)
            ->get(route('admin.store.visitor-analytics.export', [
                'from' => today()->subDays(7)->toDateString(),
                'to' => today()->toDateString(),
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    /**
     * @return array{AnalyticsVisitor, AnalyticsSession}
     */
    private function analyticsIdentity(mixed $seenAt = null): array
    {
        $seenAt ??= now();
        $visitor = AnalyticsVisitor::query()->create([
            'token_hash' => hash('sha256', uniqid('visitor', true)),
            'first_seen_at' => $seenAt,
            'last_seen_at' => $seenAt,
            'first_country_code' => 'PK',
            'first_country_name' => 'Pakistan',
            'first_city' => 'Lahore',
            'first_device_type' => 'mobile',
        ]);
        $session = AnalyticsSession::query()->create([
            'visitor_id' => $visitor->id,
            'token_hash' => hash('sha256', uniqid('session', true)),
            'started_at' => $seenAt,
            'last_seen_at' => $seenAt,
            'country_code' => 'PK',
            'country_name' => 'Pakistan',
            'city' => 'Lahore',
            'masked_ip' => '203.0.113.xxx',
            'device_type' => 'mobile',
            'traffic_source' => 'facebook',
        ]);

        return [$visitor, $session];
    }

    private function pageView(
        int $visitorId,
        int $sessionId,
        string $type,
        ?int $entityId,
        ?string $slug,
        mixed $occurredAt = null,
    ): AnalyticsPageView {
        return AnalyticsPageView::query()->create([
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'occurred_at' => $occurredAt ?? now(),
            'route_name' => "store.{$type}",
            'page_type' => $type,
            'entity_id' => $entityId,
            'entity_slug' => $slug,
            'response_status' => 200,
            'traffic_source' => 'facebook',
        ]);
    }
}
