<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsPageView;
use App\Models\AnalyticsRollup;
use App\Models\AnalyticsSession;
use App\Models\AnalyticsVisitor;
use App\Models\Category;
use App\Models\Product;
use App\Models\StorePage;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class VisitorAnalyticsReportService
{
    public function __construct(
        private readonly AnalyticsTrafficFilter $trafficFilter,
    ) {}

    /**
     * @param  array{country?: string, city?: string, device?: string, source?: string}  $filters
     * @return array<string, mixed>
     */
    public function report(CarbonImmutable $from, CarbonImmutable $to, array $filters = []): array
    {
        $from = $from->startOfDay();
        $to = $to->endOfDay();

        return [
            'kpis' => $this->kpis(),
            'geoip_ready' => $this->trafficFilter->geoIpDatabaseReady(),
            'selected' => [
                'visitors' => $this->views($from, $to, $filters)->distinct()->count('views.visitor_id'),
                'sessions' => $this->views($from, $to, $filters)->distinct()->count('views.session_id'),
                'page_views' => $this->views($from, $to, $filters)->count(),
                'conversions' => $this->conversions($from, $to, $filters),
            ],
            'funnel' => $this->funnel($from, $to, $filters),
            'daily' => $this->dailySeries($from, $to, $filters),
            'monthly' => $this->monthlySeries(),
            'top_pages' => $this->routeRanking($from, $to, $filters),
            'top_products' => $this->entityRanking('product', $from, $to, $filters),
            'top_categories' => $this->entityRanking('category', $from, $to, $filters),
            'top_cms_pages' => $this->entityRanking('page', $from, $to, $filters),
            'countries' => $this->breakdown('country_name', $from, $to, $filters),
            'regions' => $this->breakdown('region_name', $from, $to, $filters),
            'cities' => $this->breakdown('city', $from, $to, $filters),
            'timezones' => $this->breakdown('timezone', $from, $to, $filters),
            'ip_networks' => $this->breakdown('masked_ip', $from, $to, $filters),
            'devices' => $this->breakdown('device_type', $from, $to, $filters),
            'browsers' => $this->breakdown('browser', $from, $to, $filters),
            'operating_systems' => $this->breakdown('operating_system', $from, $to, $filters),
            'sources' => $this->breakdown('traffic_source', $from, $to, $filters),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function kpis(): array
    {
        $now = CarbonImmutable::now((string) config('app.timezone'));
        $today = $now->startOfDay();
        $yesterday = $today->subDay();

        return [
            'today' => $this->uniqueVisitors($today, $now),
            'yesterday' => $this->uniqueVisitors($yesterday, $yesterday->endOfDay()),
            'week' => $this->uniqueVisitors($today->startOfWeek(), $now),
            'month' => $this->uniqueVisitors($today->startOfMonth(), $now),
            'total_visitors' => $this->lifetimeVisitors(),
            'live' => AnalyticsSession::query()
                ->where('last_seen_at', '>=', $now->subMinutes((int) config('analytics.live_window_minutes')))
                ->count(),
            'total_page_views' => $this->lifetimePageViews(),
        ];
    }

    /**
     * @param  array{country?: string, city?: string, device?: string, source?: string}  $filters
     */
    public function exportRows(CarbonImmutable $from, CarbonImmutable $to, array $filters = []): array
    {
        $report = $this->report($from, $to, $filters);
        $rows = [];

        foreach ([
            'top_pages' => 'Top Page',
            'top_products' => 'Top Product',
            'top_categories' => 'Top Category',
            'top_cms_pages' => 'CMS Page',
            'countries' => 'Country',
            'regions' => 'Region / Province',
            'cities' => 'City',
            'timezones' => 'Time Zone',
            'ip_networks' => 'Masked IP Network',
            'devices' => 'Device',
            'browsers' => 'Browser',
            'operating_systems' => 'Operating System',
            'sources' => 'Traffic Source',
        ] as $key => $section) {
            foreach ($report[$key] as $item) {
                $rows[] = [
                    'section' => $section,
                    'label' => $item['label'],
                    'page_views' => $item['page_views'],
                    'visitors' => $item['visitors'],
                    'sessions' => $item['sessions'],
                ];
            }
        }

        foreach ($report['funnel'] as $step) {
            $rows[] = [
                'section' => 'Funnel',
                'label' => $step['label'],
                'page_views' => $step['sessions'],
                'visitors' => $step['visitors'],
                'sessions' => $step['sessions'],
            ];
        }

        return $rows;
    }

    private function uniqueVisitors(CarbonImmutable $from, CarbonImmutable $to): int
    {
        return AnalyticsPageView::query()
            ->whereBetween('occurred_at', [$from, $to])
            ->distinct()
            ->count('visitor_id');
    }

    private function lifetimePageViews(): int
    {
        $rawCount = AnalyticsPageView::query()->count();
        $oldestRaw = AnalyticsPageView::query()->min('occurred_at');

        if (! is_string($oldestRaw)) {
            return (int) AnalyticsRollup::query()
                ->where('grain', 'day')
                ->where('dimension_type', 'all')
                ->sum('page_views');
        }

        return $rawCount + (int) AnalyticsRollup::query()
            ->where('grain', 'day')
            ->where('dimension_type', 'all')
            ->whereDate('period_start', '<', CarbonImmutable::parse($oldestRaw)->toDateString())
            ->sum('page_views');
    }

    private function lifetimeVisitors(): int
    {
        $retainedCount = AnalyticsVisitor::query()->count();
        $oldestRetained = AnalyticsVisitor::query()->min('first_seen_at');

        if (! is_string($oldestRetained)) {
            return (int) AnalyticsRollup::query()
                ->where('grain', 'day')
                ->where('dimension_type', 'all')
                ->sum('new_visitors');
        }

        return $retainedCount + (int) AnalyticsRollup::query()
            ->where('grain', 'day')
            ->where('dimension_type', 'all')
            ->whereDate('period_start', '<', CarbonImmutable::parse($oldestRetained)->toDateString())
            ->sum('new_visitors');
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function views(CarbonImmutable $from, CarbonImmutable $to, array $filters): Builder
    {
        $query = DB::table('analytics_page_views as views')
            ->join('analytics_sessions as sessions', 'sessions.id', '=', 'views.session_id')
            ->whereBetween('views.occurred_at', [$from, $to]);

        foreach ([
            'country' => 'country_name',
            'city' => 'city',
            'device' => 'device_type',
            'source' => 'traffic_source',
        ] as $filter => $column) {
            if (filled($filters[$filter] ?? null)) {
                $query->where("sessions.{$column}", $filters[$filter]);
            }
        }

        return $query;
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function conversions(CarbonImmutable $from, CarbonImmutable $to, array $filters): int
    {
        $query = DB::table('analytics_sessions as sessions')
            ->where('sessions.conversions', '>', 0)
            ->whereBetween('sessions.last_seen_at', [$from, $to]);

        foreach ([
            'country' => 'country_name',
            'city' => 'city',
            'device' => 'device_type',
            'source' => 'traffic_source',
        ] as $filter => $column) {
            if (filled($filters[$filter] ?? null)) {
                $query->where("sessions.{$column}", $filters[$filter]);
            }
        }

        return (int) $query->count();
    }

    /**
     * @param  array<string, string>  $filters
     * @return list<array{key: string, label: string, visitors: int, sessions: int, percentage: float}>
     */
    private function funnel(CarbonImmutable $from, CarbonImmutable $to, array $filters): array
    {
        $steps = [
            'home' => __('agricart.analytics.funnel_home'),
            'product' => __('agricart.analytics.funnel_product'),
            'cart' => __('agricart.analytics.funnel_cart'),
            'checkout' => __('agricart.analytics.funnel_checkout'),
            'checkout_success' => __('agricart.analytics.funnel_order'),
        ];
        $rows = [];
        $baseline = 0;

        foreach ($steps as $type => $label) {
            $sessions = (int) $this->views($from, $to, $filters)
                ->where('views.page_type', $type)
                ->distinct()
                ->count('views.session_id');
            $visitors = (int) $this->views($from, $to, $filters)
                ->where('views.page_type', $type)
                ->distinct()
                ->count('views.visitor_id');

            if ($baseline === 0) {
                $baseline = max(1, $sessions);
            }

            $rows[] = [
                'key' => $type,
                'label' => $label,
                'visitors' => $visitors,
                'sessions' => $sessions,
                'percentage' => round(($sessions / $baseline) * 100, 1),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, string>  $filters
     * @return list<array{label: string, visitors: int, page_views: int}>
     */
    private function dailySeries(CarbonImmutable $from, CarbonImmutable $to, array $filters): array
    {
        $rows = $this->views($from, $to, $filters)
            ->groupByRaw('DATE(views.occurred_at)')
            ->selectRaw('DATE(views.occurred_at) as period')
            ->selectRaw('COUNT(*) as page_views')
            ->selectRaw('COUNT(DISTINCT views.visitor_id) as visitors')
            ->get()
            ->keyBy('period');
        $series = [];

        for ($date = $from->startOfDay(); $date <= $to; $date = $date->addDay()) {
            $row = $rows->get($date->toDateString());
            $series[] = [
                'label' => $date->format('d M'),
                'visitors' => (int) ($row->visitors ?? 0),
                'page_views' => (int) ($row->page_views ?? 0),
            ];
        }

        return $series;
    }

    /**
     * @return list<array{label: string, visitors: int, page_views: int}>
     */
    private function monthlySeries(): array
    {
        $now = CarbonImmutable::now((string) config('app.timezone'));
        $start = $now->subMonths(11)->startOfMonth();
        $rollups = AnalyticsRollup::query()
            ->where('grain', 'month')
            ->where('dimension_type', 'all')
            ->where('period_start', '>=', $start->toDateString())
            ->get()
            ->keyBy(fn (AnalyticsRollup $rollup): string => $rollup->period_start->format('Y-m'));
        $series = [];

        for ($month = $start; $month <= $now; $month = $month->addMonth()) {
            $key = $month->format('Y-m');
            $rollup = $rollups->get($key);

            if ($month->isSameMonth($now)) {
                $pageViews = AnalyticsPageView::query()
                    ->whereBetween('occurred_at', [$month, $now])
                    ->count();
                $visitors = $this->uniqueVisitors($month, $now);
            } else {
                $pageViews = (int) ($rollup?->page_views ?? 0);
                $visitors = (int) ($rollup?->visitors ?? 0);
            }

            $series[] = [
                'label' => $month->format('M Y'),
                'visitors' => $visitors,
                'page_views' => $pageViews,
            ];
        }

        return $series;
    }

    /**
     * @param  array<string, string>  $filters
     * @return list<array{label: string, page_views: int, visitors: int, sessions: int, percentage: float}>
     */
    private function routeRanking(CarbonImmutable $from, CarbonImmutable $to, array $filters): array
    {
        $rows = $this->views($from, $to, $filters)
            ->whereNotNull('views.route_name')
            ->where('views.route_name', '!=', '')
            ->groupBy('views.route_name')
            ->selectRaw('views.route_name as route_name')
            ->selectRaw('COUNT(*) as page_views')
            ->selectRaw('COUNT(DISTINCT views.visitor_id) as visitors')
            ->selectRaw('COUNT(DISTINCT views.session_id) as sessions')
            ->orderByDesc('page_views')
            ->limit(50)
            ->get();

        return $this->withPercentages($rows->map(fn ($row): array => [
            'label' => $this->routeLabel((string) $row->route_name),
            'page_views' => (int) $row->page_views,
            'visitors' => (int) $row->visitors,
            'sessions' => (int) $row->sessions,
        ])->all());
    }

    /**
     * @param  array<string, string>  $filters
     * @return list<array{label: string, page_views: int, visitors: int, sessions: int, percentage: float}>
     */
    private function entityRanking(string $type, CarbonImmutable $from, CarbonImmutable $to, array $filters): array
    {
        $rows = $this->views($from, $to, $filters)
            ->where('views.page_type', $type)
            ->whereNotNull('views.entity_id')
            ->groupBy(['views.entity_id', 'views.entity_slug'])
            ->select(['views.entity_id', 'views.entity_slug'])
            ->selectRaw('COUNT(*) as page_views')
            ->selectRaw('COUNT(DISTINCT views.visitor_id) as visitors')
            ->selectRaw('COUNT(DISTINCT views.session_id) as sessions')
            ->orderByDesc('page_views')
            ->limit(50)
            ->get();
        $ids = $rows->pluck('entity_id')->map(fn ($id): int => (int) $id);
        $labels = match ($type) {
            'product' => Product::query()->whereIn('id', $ids)->pluck('name_en', 'id'),
            'category' => Category::query()->whereIn('id', $ids)->pluck('name_en', 'id'),
            'page' => StorePage::query()->whereIn('id', $ids)->pluck('title_en', 'id'),
            default => collect(),
        };

        return $this->withPercentages($rows->map(fn ($row): array => [
            'label' => (string) ($labels->get((int) $row->entity_id) ?: $row->entity_slug ?: "#{$row->entity_id}"),
            'page_views' => (int) $row->page_views,
            'visitors' => (int) $row->visitors,
            'sessions' => (int) $row->sessions,
        ])->all());
    }

    /**
     * @param  array<string, string>  $filters
     * @return list<array{label: string, page_views: int, visitors: int, sessions: int, percentage: float}>
     */
    private function breakdown(string $column, CarbonImmutable $from, CarbonImmutable $to, array $filters): array
    {
        $rows = $this->views($from, $to, $filters)
            ->whereNotNull("sessions.{$column}")
            ->where("sessions.{$column}", '!=', '')
            ->groupBy("sessions.{$column}")
            ->selectRaw("sessions.{$column} as label")
            ->selectRaw('COUNT(*) as page_views')
            ->selectRaw('COUNT(DISTINCT views.visitor_id) as visitors')
            ->selectRaw('COUNT(DISTINCT views.session_id) as sessions')
            ->orderByDesc('page_views')
            ->limit(50)
            ->get()
            ->map(fn ($row): array => [
                'label' => (string) $row->label,
                'page_views' => (int) $row->page_views,
                'visitors' => (int) $row->visitors,
                'sessions' => (int) $row->sessions,
            ])
            ->all();

        return $this->withPercentages($rows);
    }

    /**
     * @param  list<array{label: string, page_views: int, visitors: int, sessions: int}>  $rows
     * @return list<array{label: string, page_views: int, visitors: int, sessions: int, percentage: float}>
     */
    private function withPercentages(array $rows): array
    {
        $total = max(1, array_sum(array_column($rows, 'page_views')));

        return array_map(
            fn (array $row): array => [
                ...$row,
                'percentage' => round(($row['page_views'] / $total) * 100, 1),
            ],
            $rows,
        );
    }

    private function routeLabel(string $routeName): string
    {
        return match (true) {
            $routeName === 'store.home' => __('agricart.analytics.route_home'),
            $routeName === 'store.search' => __('agricart.analytics.route_search'),
            $routeName === 'store.cart' => __('agricart.analytics.route_cart'),
            $routeName === 'store.checkout' => __('agricart.analytics.route_checkout'),
            $routeName === 'store.checkout.success' => __('agricart.analytics.route_order_success'),
            $routeName === 'store.product' => __('agricart.analytics.route_product'),
            $routeName === 'store.category' => __('agricart.analytics.route_category'),
            $routeName === 'store.page' => __('agricart.analytics.route_cms_page'),
            str_starts_with($routeName, 'store.customer.account') => __('agricart.analytics.route_account'),
            str_starts_with($routeName, 'auth.') => __('agricart.analytics.route_auth'),
            default => $routeName,
        };
    }
}
