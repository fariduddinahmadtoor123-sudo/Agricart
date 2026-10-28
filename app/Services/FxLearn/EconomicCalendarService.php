<?php

namespace App\Services\FxLearn;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class EconomicCalendarService
{
    public function __construct(
        protected FreeEconomicCalendarClient $freeClient,
    ) {}

    /**
     * @return array{
     *     events: list<array<string, mixed>>,
     *     source: string,
     *     fetched_at: string,
     *     error: string|null,
     *     meta: array<string, mixed>,
     *     using_demo: bool,
     *     week_start: string,
     *     week_end: string
     * }
     */
    public function snapshot(bool $forceRefresh = false): array
    {
        return $this->weekBundle($forceRefresh);
    }

    /**
     * @return array{
     *     events: list<array<string, mixed>>,
     *     source: string,
     *     fetched_at: string,
     *     error: string|null,
     *     meta: array<string, mixed>,
     *     using_demo: bool,
     *     week_start: string,
     *     week_end: string
     * }
     */
    public function refresh(): array
    {
        $this->bustCache();

        return $this->weekBundle(true);
    }

    /**
     * @return array{
     *     events: list<array<string, mixed>>,
     *     source: string,
     *     fetched_at: string,
     *     error: string|null,
     *     meta: array<string, mixed>,
     *     using_demo: bool,
     *     week_start: string,
     *     week_end: string
     * }
     */
    public function weekBundle(bool $forceRefresh = false): array
    {
        $tz = agricart_display_timezone();
        $weekStart = now($tz)->startOfWeek(Carbon::SUNDAY)->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();
        // Include prior week so released Actuals are available for AI testing.
        $from = $weekStart->copy()->subDays(7)->timezone('UTC')->toDateString();
        $to = $weekEnd->copy()->addDay()->timezone('UTC')->toDateString();
        $cacheKey = 'fx-learn:econ:week:v5:'.$from.':'.$to;
        $ttl = max(60, (int) config('services.fx_learn.calendar_cache_ttl', 900));

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && ! $forceRefresh) {
            return [
                'events' => $cached['events'] ?? [],
                'source' => (string) ($cached['source'] ?? 'cache'),
                'fetched_at' => (string) ($cached['fetched_at'] ?? ''),
                'error' => $cached['error'] ?? null,
                'meta' => is_array($cached['meta'] ?? null) ? $cached['meta'] : ['from' => $from, 'to' => $to],
                'using_demo' => (bool) ($cached['using_demo'] ?? false),
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
            ];
        }

        $fetch = $this->freeClient->fetchWeek($from, $to);
        $usingDemo = false;
        $error = $fetch['error'];
        $source = $fetch['source'];
        $events = $fetch['events'];

        if (! $fetch['ok'] || $events === []) {
            $events = $this->demoEvents($tz);
            $usingDemo = true;
            $source = 'demo';
            $error = $error ?? __('agricart.pages.fx-learn.free_feed_empty');
        } else {
            // Always prepend practice rows so Analyze News can be tested without waiting for a live print.
            $events = array_merge($this->practiceEvents($tz), $events);
        }

        $fetchedAt = now($tz)->toIso8601String();

        $payload = [
            'events' => $events,
            'source' => $source,
            'fetched_at' => $fetchedAt,
            'error' => $usingDemo ? $error : null,
            'meta' => $fetch['meta'],
            'using_demo' => $usingDemo,
        ];

        Cache::put($cacheKey, $payload, $ttl);

        return [
            'events' => $events,
            'source' => $source,
            'fetched_at' => $fetchedAt,
            'error' => $payload['error'],
            'meta' => $fetch['meta'],
            'using_demo' => $usingDemo,
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $events
     * @return array<string, mixed>|null
     */
    public function nextHighImpact(array $events): ?array
    {
        $nowMs = (int) (CarbonImmutable::now('UTC')->valueOf());

        $upcoming = collect($events)
            ->filter(fn (array $ev): bool => ($ev['impact'] ?? '') === 'high')
            ->filter(fn (array $ev): bool => (int) ($ev['at'] ?? 0) >= $nowMs - 60_000)
            ->sortBy('at')
            ->values();

        return $upcoming->first();
    }

    public function bustCache(): void
    {
        $tz = agricart_display_timezone();
        $weekStart = now($tz)->startOfWeek(Carbon::SUNDAY)->startOfDay();
        $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();
        $fromWide = $weekStart->copy()->subDays(7)->timezone('UTC')->toDateString();
        $toWide = $weekEnd->copy()->addDay()->timezone('UTC')->toDateString();

        foreach (['v1', 'v2', 'v3', 'v4', 'v5'] as $ver) {
            Cache::forget('fx-learn:econ:week:'.$ver.':'.$fromWide.':'.$toWide);
            Cache::forget('fx-learn:econ:week:'.$ver.':'.$weekStart->copy()->subDay()->timezone('UTC')->toDateString().':'.$weekEnd->copy()->addDay()->timezone('UTC')->toDateString());
            Cache::forget('fx-learn:econ:week:'.$ver.':'.$weekStart->copy()->timezone('UTC')->toDateString().':'.$weekEnd->copy()->timezone('UTC')->toDateString());
        }
    }

    /**
     * Fixed rows with Previous + Forecast + Actual for AI / sheet testing.
     *
     * @return list<array<string, mixed>>
     */
    protected function practiceEvents(string $tz): array
    {
        $at = CarbonImmutable::now($tz)->subHours(3)->utc();

        return [
            [
                'id' => 'practice-usd-cpi',
                'title' => 'US CPI m/m (practice sample)',
                'country' => 'US',
                'ccy' => 'USD',
                'impact' => 'high',
                'at' => (int) $at->valueOf(),
                'previous' => '0.2%',
                'forecast' => '0.3%',
                'actual' => '0.4%',
                'unit' => '%',
                'src' => 'practice',
            ],
            [
                'id' => 'practice-eur-rate',
                'title' => 'ECB Rate Decision (practice sample)',
                'country' => 'EU',
                'ccy' => 'EUR',
                'impact' => 'high',
                'at' => (int) $at->subHour()->valueOf(),
                'previous' => '2.65%',
                'forecast' => '2.65%',
                'actual' => '2.50%',
                'unit' => '%',
                'src' => 'practice',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function demoEvents(string $tz): array
    {
        return $this->practiceEvents($tz);
    }
}
