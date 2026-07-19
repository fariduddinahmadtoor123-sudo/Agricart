@php
    $chartPoints = static function (array $series, string $key): string {
        $count = max(1, count($series) - 1);
        $max = max(1, ...array_map(static fn (array $point): int => (int) ($point[$key] ?? 0), $series));

        return collect($series)->map(static function (array $point, int $index) use ($count, $max, $key): string {
            $x = ($index / $count) * 100;
            $y = 100 - (((int) ($point[$key] ?? 0) / $max) * 92);

            return number_format($x, 2, '.', '').','.number_format($y, 2, '.', '');
        })->implode(' ');
    };
    $dailyVisitors = $chartPoints($report['daily'], 'visitors');
    $dailyViews = $chartPoints($report['daily'], 'page_views');
    $monthlyVisitors = $chartPoints($report['monthly'], 'visitors');
    $monthlyViews = $chartPoints($report['monthly'], 'page_views');
@endphp

<div class="agricart-visitor-analytics" wire:poll.60s.visible>
    @if ($statusMessage)
        <div class="agricart-visitor-analytics__notice" role="status">{{ $statusMessage }}</div>
    @endif

    <div class="agricart-visitor-analytics__actions">
        <a href="{{ $exportUrl }}">{{ __('agricart.analytics.export') }}</a>
    </div>

    <div class="agricart-visitor-analytics__kpis">
        @foreach ([
            'today' => __('agricart.analytics.today'),
            'yesterday' => __('agricart.analytics.yesterday'),
            'week' => __('agricart.analytics.this_week'),
            'month' => __('agricart.analytics.this_month'),
            'total_visitors' => __('agricart.analytics.total_visitors'),
            'live' => __('agricart.analytics.live_visitors'),
            'total_page_views' => __('agricart.analytics.total_page_views'),
        ] as $key => $label)
            <div @class(['agricart-visitor-analytics__kpi', 'is-live' => $key === 'live'])>
                <span>{{ $label }}</span>
                <strong>{{ number_format($report['kpis'][$key] ?? 0) }}</strong>
            </div>
        @endforeach
    </div>

    <div class="agricart-visitor-analytics__selected">
        <span>{{ __('agricart.analytics.selected_range') }}</span>
        <strong>{{ number_format($report['selected']['visitors']) }} {{ __('agricart.analytics.visitors') }}</strong>
        <strong>{{ number_format($report['selected']['sessions']) }} {{ __('agricart.analytics.sessions') }}</strong>
        <strong>{{ number_format($report['selected']['page_views']) }} {{ __('agricart.analytics.page_views') }}</strong>
    </div>

    <div class="agricart-visitor-analytics__charts">
        @foreach ([
            ['title' => __('agricart.analytics.daily_graph'), 'series' => $report['daily'], 'visitors' => $dailyVisitors, 'views' => $dailyViews],
            ['title' => __('agricart.analytics.monthly_graph'), 'series' => $report['monthly'], 'visitors' => $monthlyVisitors, 'views' => $monthlyViews],
        ] as $chart)
            <section class="agricart-visitor-analytics__chart">
                <div class="agricart-visitor-analytics__section-head">
                    <h3>{{ $chart['title'] }}</h3>
                    <div class="agricart-visitor-analytics__legend">
                        <span class="is-visitors">{{ __('agricart.analytics.visitors') }}</span>
                        <span class="is-views">{{ __('agricart.analytics.page_views') }}</span>
                    </div>
                </div>
                <svg viewBox="0 0 100 100" preserveAspectRatio="none" role="img" aria-label="{{ $chart['title'] }}">
                    <line x1="0" y1="100" x2="100" y2="100" />
                    <line x1="0" y1="50" x2="100" y2="50" />
                    <polyline class="is-views" points="{{ $chart['views'] }}" />
                    <polyline class="is-visitors" points="{{ $chart['visitors'] }}" />
                </svg>
                <div class="agricart-visitor-analytics__axis">
                    @foreach ($chart['series'] as $index => $point)
                        @if ($index === 0 || $index === count($chart['series']) - 1 || $index % max(1, (int) floor(count($chart['series']) / 4)) === 0)
                            <span>{{ $point['label'] }}</span>
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    <div class="agricart-visitor-analytics__tables">
        @foreach ([
            'top_products' => __('agricart.analytics.top_products'),
            'top_categories' => __('agricart.analytics.top_categories'),
            'countries' => __('agricart.analytics.countries'),
            'cities' => __('agricart.analytics.cities'),
            'ip_networks' => __('agricart.analytics.ip_networks'),
            'devices' => __('agricart.analytics.devices'),
            'sources' => __('agricart.analytics.traffic_sources'),
        ] as $key => $title)
            @php
                $sectionRows = $report[$key];
                $sectionPageSize = 5;
                $sectionTotalPages = max(1, (int) ceil(count($sectionRows) / $sectionPageSize));
                $sectionPage = min($sectionTotalPages, max(1, $sectionPages[$key] ?? 1));
                $visibleSectionRows = array_slice($sectionRows, ($sectionPage - 1) * $sectionPageSize, $sectionPageSize);
            @endphp
            <section class="agricart-visitor-analytics__table-card">
                <h3>{{ $title }}</h3>
                @if ($sectionRows === [])
                    <p>{{ __('agricart.analytics.no_data') }}</p>
                @else
                    <div>
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ __('agricart.analytics.name') }}</th>
                                    <th>{{ __('agricart.analytics.views') }}</th>
                                    <th>{{ __('agricart.analytics.visitors') }}</th>
                                    <th>{{ __('agricart.analytics.share') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($visibleSectionRows as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td>{{ number_format($row['page_views']) }}</td>
                                        <td>{{ number_format($row['visitors']) }}</td>
                                        <td>{{ number_format($row['percentage'], 1) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($sectionTotalPages > 1)
                        <div class="agricart-visitor-analytics__pagination">
                            <button
                                type="button"
                                wire:click="showSectionPage('{{ $key }}', {{ $sectionPage - 1 }})"
                                @disabled($sectionPage <= 1)
                            >
                                {{ __('agricart.analytics.previous') }}
                            </button>
                            <span>{{ __('agricart.analytics.page_of', ['page' => $sectionPage, 'pages' => $sectionTotalPages]) }}</span>
                            <button
                                type="button"
                                wire:click="showSectionPage('{{ $key }}', {{ $sectionPage + 1 }})"
                                @disabled($sectionPage >= $sectionTotalPages)
                            >
                                {{ __('agricart.analytics.next') }}
                            </button>
                        </div>
                    @endif
                @endif
            </section>
        @endforeach
    </div>
</div>
