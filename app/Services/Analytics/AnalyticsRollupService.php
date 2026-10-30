<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsVisitor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AnalyticsRollupService
{
    public function rollupDay(CarbonImmutable $date): void
    {
        $this->rollup('day', $date->startOfDay(), $date->endOfDay());
    }

    public function rollupMonth(CarbonImmutable $month): void
    {
        $this->rollup('month', $month->startOfMonth(), $month->endOfMonth());
    }

    private function rollup(string $grain, CarbonImmutable $start, CarbonImmutable $end): void
    {
        DB::transaction(function () use ($grain, $start, $end): void {
            DB::table('analytics_rollups')
                ->where('grain', $grain)
                ->whereDate('period_start', $start->toDateString())
                ->delete();

            $this->store($grain, $start, 'all', '_all', __('agricart.analytics.all_traffic'), [
                'page_views' => $this->views($start, $end)->count(),
                'visitors' => $this->views($start, $end)->distinct()->count('visitor_id'),
                'sessions' => $this->views($start, $end)->distinct()->count('session_id'),
                'new_visitors' => AnalyticsVisitor::query()
                    ->whereBetween('first_seen_at', [$start, $end])
                    ->count(),
            ]);

            foreach (['country_name' => 'country', 'region_name' => 'region', 'city' => 'city', 'timezone' => 'timezone', 'device_type' => 'device', 'traffic_source' => 'source'] as $column => $dimension) {
                $rows = DB::table('analytics_page_views as views')
                    ->join('analytics_sessions as sessions', 'sessions.id', '=', 'views.session_id')
                    ->whereBetween('views.occurred_at', [$start, $end])
                    ->whereNotNull("sessions.{$column}")
                    ->where("sessions.{$column}", '!=', '')
                    ->groupBy("sessions.{$column}")
                    ->selectRaw("sessions.{$column} as dimension_key")
                    ->selectRaw('COUNT(*) as page_views')
                    ->selectRaw('COUNT(DISTINCT views.visitor_id) as visitors')
                    ->selectRaw('COUNT(DISTINCT views.session_id) as sessions')
                    ->get();

                foreach ($rows as $row) {
                    $this->store($grain, $start, $dimension, (string) $row->dimension_key, (string) $row->dimension_key, [
                        'page_views' => (int) $row->page_views,
                        'visitors' => (int) $row->visitors,
                        'sessions' => (int) $row->sessions,
                        'new_visitors' => 0,
                    ]);
                }
            }

            foreach (['product', 'category'] as $type) {
                // Group by entity_id only — the unique key is dimension_key (= entity id).
                // Grouping by slug too produced duplicate inserts when the same id had multiple slugs.
                $rows = $this->views($start, $end)
                    ->where('page_type', $type)
                    ->whereNotNull('entity_id')
                    ->groupBy('entity_id')
                    ->select(['entity_id'])
                    ->selectRaw('MAX(entity_slug) as entity_slug')
                    ->selectRaw('COUNT(*) as page_views')
                    ->selectRaw('COUNT(DISTINCT visitor_id) as visitors')
                    ->selectRaw('COUNT(DISTINCT session_id) as sessions')
                    ->get();

                foreach ($rows as $row) {
                    $key = (string) $row->entity_id;
                    $label = filled($row->entity_slug) ? (string) $row->entity_slug : "#{$key}";
                    $this->store($grain, $start, $type, $key, $label, [
                        'page_views' => (int) $row->page_views,
                        'visitors' => (int) $row->visitors,
                        'sessions' => (int) $row->sessions,
                        'new_visitors' => 0,
                    ]);
                }
            }
        });
    }

    private function views(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return DB::table('analytics_page_views')->whereBetween('occurred_at', [$start, $end]);
    }

    /**
     * @param  array{page_views: int, visitors: int, sessions: int, new_visitors: int}  $metrics
     */
    private function store(
        string $grain,
        CarbonImmutable $periodStart,
        string $dimensionType,
        string $dimensionKey,
        string $dimensionLabel,
        array $metrics,
    ): void {
        $payload = [
            'grain' => $grain,
            'period_start' => $periodStart->toDateString(),
            'dimension_type' => $dimensionType,
            'dimension_key' => mb_substr($dimensionKey, 0, 191),
            'dimension_label' => mb_substr($dimensionLabel, 0, 255),
            ...$metrics,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('analytics_rollups')->upsert(
            [$payload],
            ['grain', 'period_start', 'dimension_type', 'dimension_key'],
            ['dimension_label', 'page_views', 'visitors', 'sessions', 'new_visitors', 'updated_at'],
        );
    }
}
