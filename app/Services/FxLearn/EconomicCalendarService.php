<?php

namespace App\Services\FxLearn;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class EconomicCalendarService
{
    public function __construct(
        protected FreeEconomicCalendarClient $freeClient,
        protected EconomicEventArchiveService $archive,
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
        $cacheKey = 'fx-learn:econ:week:v6:'.$from.':'.$to;
        $baseTtl = max(60, (int) config('services.fx_learn.calendar_cache_ttl', 900));
        $nearTtl = max(45, (int) config('services.fx_learn.calendar_near_ttl', 90));

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && ! $forceRefresh) {
            $events = is_array($cached['events'] ?? null) ? $cached['events'] : [];
            $events = $this->archive->hydrate($events);

            try {
                $fetchedTs = CarbonImmutable::parse((string) ($cached['fetched_at'] ?? ''))->getTimestamp();
            } catch (\Throwable) {
                $fetchedTs = 0;
            }

            $age = max(0, now()->getTimestamp() - $fetchedTs);
            $ttlWanted = $this->adaptiveTtl($events, $baseTtl, $nearTtl);

            if ($fetchedTs > 0 && $age < $ttlWanted) {
                return [
                    'events' => $events,
                    'source' => (string) ($cached['source'] ?? 'cache'),
                    'fetched_at' => (string) ($cached['fetched_at'] ?? ''),
                    'error' => $cached['error'] ?? null,
                    'meta' => is_array($cached['meta'] ?? null) ? $cached['meta'] : ['from' => $from, 'to' => $to],
                    'using_demo' => (bool) ($cached['using_demo'] ?? false),
                    'week_start' => $weekStart->toDateString(),
                    'week_end' => $weekEnd->toDateString(),
                ];
            }
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
            $events = $this->archive->hydrate($events);
            $this->archive->remember($events);
            // Always prepend practice rows so Analyze News can be tested without waiting for a live print.
            $events = array_merge($this->practiceEvents($tz), $events);
        }

        $fetchedAt = now($tz)->toIso8601String();
        $ttl = $this->adaptiveTtl($events, $baseTtl, $nearTtl);

        $payload = [
            'events' => $events,
            'source' => $source,
            'fetched_at' => $fetchedAt,
            'error' => $usingDemo ? $error : null,
            'meta' => array_merge($fetch['meta'] ?? ['from' => $from, 'to' => $to], [
                'cache_ttl' => $ttl,
                'archived' => ! $usingDemo,
            ]),
            'using_demo' => $usingDemo,
        ];

        Cache::put($cacheKey, $payload, $ttl);

        return [
            'events' => $events,
            'source' => $source,
            'fetched_at' => $fetchedAt,
            'error' => $payload['error'],
            'meta' => $payload['meta'],
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
        return $this->nextUpcoming($events, highOnly: true);
    }

    /**
     * Next release of any impact (for center countdown line).
     *
     * @param  list<array<string, mixed>>  $events
     * @return array<string, mixed>|null
     */
    public function nextUpcoming(array $events, bool $highOnly = false): ?array
    {
        $nowMs = (int) (CarbonImmutable::now('UTC')->valueOf());

        $upcoming = collect($events)
            ->when($highOnly, fn ($c) => $c->filter(fn (array $ev): bool => ($ev['impact'] ?? '') === 'high'))
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

        foreach (['v1', 'v2', 'v3', 'v4', 'v5', 'v6'] as $ver) {
            Cache::forget('fx-learn:econ:week:'.$ver.':'.$fromWide.':'.$toWide);
            Cache::forget('fx-learn:econ:week:'.$ver.':'.$weekStart->copy()->subDay()->timezone('UTC')->toDateString().':'.$weekEnd->copy()->addDay()->timezone('UTC')->toDateString());
            Cache::forget('fx-learn:econ:week:'.$ver.':'.$weekStart->copy()->timezone('UTC')->toDateString().':'.$weekEnd->copy()->timezone('UTC')->toDateString());
        }
    }

    /**
     * Shorter cache near a print, or when recent releases still lack Actual.
     *
     * @param  list<array<string, mixed>>  $events
     */
    protected function adaptiveTtl(array $events, int $baseTtl, int $nearTtl): int
    {
        $nowMs = (int) CarbonImmutable::now('UTC')->valueOf();
        $windowMs = 20 * 60 * 1000;

        foreach ($events as $ev) {
            if (! is_array($ev)) {
                continue;
            }

            $at = (int) ($ev['at'] ?? 0);

            if ($at <= 0) {
                continue;
            }

            // Upcoming release within 20 minutes → poll faster.
            if ($at >= $nowMs && ($at - $nowMs) <= $windowMs) {
                return $nearTtl;
            }

            // Just released, still no Actual on a data-style event → poll faster.
            if ($at < $nowMs && ($nowMs - $at) <= $windowMs) {
                $actual = trim((string) ($ev['actual'] ?? ''));
                $title = (string) ($ev['title'] ?? '');

                if (($actual === '' || $actual === '—') && ! $this->isNonNumericEvent($title)) {
                    return $nearTtl;
                }
            }
        }

        return $baseTtl;
    }

    protected function isNonNumericEvent(string $title): bool
    {
        return (bool) preg_match('/speaks|speech|statement|press conference|holiday|auction|minutes\b|testimony|budget release|forecast statement/i', $title);
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
