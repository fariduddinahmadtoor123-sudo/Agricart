<?php

namespace App\Services\FxLearn;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Single-pipeline Forex Factory economic calendar.
 *
 * Schedule (title/time/ccy/impact/forecast/previous) is cached normally.
 * Around high-impact release windows the cache is busted once so a fresh
 * Actual can land without polling all day.
 */
class EconomicCalendarService
{
    private const FF_URL = 'https://nfs.faireconomy.media/ff_calendar_thisweek.json';

    private const CACHE_KEY = 'fxlearn.econ.ff.v3';

    private const LAST_GOOD_KEY = 'fxlearn.econ.ff.last_good.v3';

    private const ACTUALS_KEY = 'fxlearn.econ.ff.actuals.v3';

    private const CACHE_TTL_SECONDS = 900;

    /** Keep a full FF week around when NFS returns 429 / blanks. */
    private const LAST_GOOD_TTL_SECONDS = 86_400;

    private const ACTUALS_TTL_SECONDS = 604_800;

    private const BACKUP_CACHE_TTL_SECONDS = 60;

    /** Prevent hammering FF while waiting for Actual. */
    private const ACTUAL_REFRESH_LOCK_KEY = 'fxlearn.econ.ff.actual_lock.v3';

    private const ACTUAL_REFRESH_LOCK_SECONDS = 60;

    /** Seconds before a high/med release when we start watching. */
    private const WATCH_BEFORE_SECONDS = 120;

    /** Seconds after release we keep refreshing until Actual appears. */
    private const WATCH_AFTER_SECONDS = 2400;

    /** @var array<string, string> */
    private const CCY_COUNTRY = [
        'USD' => 'United States',
        'EUR' => 'Eurozone',
        'GBP' => 'United Kingdom',
        'JPY' => 'Japan',
        'AUD' => 'Australia',
        'CAD' => 'Canada',
        'NZD' => 'New Zealand',
        'CHF' => 'Switzerland',
        'CNY' => 'China',
        'CNH' => 'China',
    ];

    /**
     * @return array{events: list<array<string, mixed>>, source: string, fetched_at: string}
     */
    public function snapshot(): array
    {
        $data = Cache::get(self::CACHE_KEY);

        if (! is_array($data) || ! isset($data['events']) || ! is_array($data['events'])) {
            $data = $this->fetchFresh();
            $this->storeSnapshot($data);
        }

        if ($this->shouldRefreshForActual($data['events'] ?? [])) {
            $refreshed = $this->refreshForActual();

            if ($refreshed !== null) {
                return $refreshed;
            }
        }

        return $data;
    }

    /**
     * @return array{events: list<array<string, mixed>>, source: string, fetched_at: string}
     */
    public function refresh(): array
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::ACTUAL_REFRESH_LOCK_KEY);

        $fresh = $this->fetchFresh();
        $this->storeSnapshot($fresh);

        return $fresh;
    }

    /**
     * @return array{events: list<array<string, mixed>>, source: string, fetched_at: string}|null
     */
    private function refreshForActual(): ?array
    {
        if (! Cache::add(self::ACTUAL_REFRESH_LOCK_KEY, 1, self::ACTUAL_REFRESH_LOCK_SECONDS)) {
            return null;
        }

        try {
            $fresh = $this->fetchFresh();
            $this->storeSnapshot($fresh);

            return $fresh;
        } catch (Throwable $e) {
            Log::warning('FX Learn FF actual refresh failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array{events: list<array<string, mixed>>, source: string, fetched_at: string}  $data
     */
    private function storeSnapshot(array $data): void
    {
        $source = (string) ($data['source'] ?? 'backup');
        $ttl = $source === 'ff' ? self::CACHE_TTL_SECONDS : self::BACKUP_CACHE_TTL_SECONDS;

        Cache::put(self::CACHE_KEY, $data, $ttl);

        if ($source === 'ff' && ($data['events'] ?? []) !== []) {
            Cache::put(self::LAST_GOOD_KEY, $data, self::LAST_GOOD_TTL_SECONDS);
        }
    }

    /**
     * Soft Actual watch — high/med numeric releases only (speeches rarely get Actual).
     * Locked to ~1 req/min so NFS does not 429 our IP.
     *
     * @param  list<array<string, mixed>>  $events
     */
    private function shouldRefreshForActual(array $events): bool
    {
        $nowMs = (int) CarbonImmutable::now('UTC')->getTimestampMs();

        foreach ($events as $ev) {
            if (! in_array((string) ($ev['impact'] ?? ''), ['high', 'med'], true)) {
                continue;
            }

            // Qualitative items (speaks / no forecast) almost never publish Actual in the JSON feed.
            if ($this->isBlank($ev['forecast'] ?? null) && $this->isBlank($ev['previous'] ?? null)) {
                continue;
            }

            if (! $this->isBlank($ev['actual'] ?? null)) {
                continue;
            }

            $at = (int) ($ev['at'] ?? 0);

            if ($at <= 0) {
                continue;
            }

            $from = $at - (self::WATCH_BEFORE_SECONDS * 1000);
            $to = $at + (self::WATCH_AFTER_SECONDS * 1000);

            if ($nowMs >= $from && $nowMs <= $to) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{events: list<array<string, mixed>>, source: string, fetched_at: string}
     */
    private function fetchFresh(): array
    {
        $events = $this->fetchForexFactory();

        if ($events !== []) {
            $events = $this->hydrateActuals($events);
            $this->rememberActuals($events);

            return [
                'events' => $events,
                'source' => 'ff',
                'fetched_at' => CarbonImmutable::now('UTC')->toIso8601String(),
            ];
        }

        // Never let a 429/blank response wipe a good week with the 3-row demo backup.
        $lastGood = Cache::get(self::LAST_GOOD_KEY);

        if (is_array($lastGood) && ($lastGood['events'] ?? []) !== []) {
            $kept = $this->hydrateActuals(array_values($lastGood['events']));

            return [
                'events' => $kept,
                'source' => 'ff',
                'fetched_at' => (string) ($lastGood['fetched_at'] ?? CarbonImmutable::now('UTC')->toIso8601String()),
            ];
        }

        return [
            'events' => $this->fallbackEvents(CarbonImmutable::now(agricart_display_timezone())),
            'source' => 'backup',
            'fetched_at' => CarbonImmutable::now('UTC')->toIso8601String(),
        ];
    }

    /**
     * Keep any Actual we already saw — NFS often re-sends blank Actual later.
     *
     * @param  list<array<string, mixed>>  $events
     * @return list<array<string, mixed>>
     */
    private function hydrateActuals(array $events): array
    {
        /** @var array<string, string> $map */
        $map = Cache::get(self::ACTUALS_KEY, []);

        if (! is_array($map)) {
            $map = [];
        }

        $previous = Cache::get(self::CACHE_KEY);
        if (is_array($previous) && isset($previous['events']) && is_array($previous['events'])) {
            foreach ($previous['events'] as $ev) {
                if (! is_array($ev) || $this->isBlank($ev['actual'] ?? null)) {
                    continue;
                }
                $map[$this->eventFingerprint($ev)] = (string) $ev['actual'];
            }
        }

        foreach ($events as $i => $ev) {
            if (! $this->isBlank($ev['actual'] ?? null)) {
                continue;
            }

            $fp = $this->eventFingerprint($ev);

            if (isset($map[$fp]) && ! $this->isBlank($map[$fp])) {
                $events[$i]['actual'] = $map[$fp];
            }
        }

        return $events;
    }

    /**
     * @param  list<array<string, mixed>>  $events
     */
    private function rememberActuals(array $events): void
    {
        /** @var array<string, string> $map */
        $map = Cache::get(self::ACTUALS_KEY, []);

        if (! is_array($map)) {
            $map = [];
        }

        foreach ($events as $ev) {
            if ($this->isBlank($ev['actual'] ?? null)) {
                continue;
            }

            $map[$this->eventFingerprint($ev)] = (string) $ev['actual'];
        }

        Cache::put(self::ACTUALS_KEY, $map, self::ACTUALS_TTL_SECONDS);
    }

    /**
     * @param  array<string, mixed>  $ev
     */
    private function eventFingerprint(array $ev): string
    {
        return strtolower(trim((string) ($ev['ccy'] ?? '')).'|'.trim((string) ($ev['title'] ?? '')).'|'.(string) (int) ($ev['at'] ?? 0));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchForexFactory(): array
    {
        try {
            $response = Http::timeout(16)
                ->retry(2, 700)
                ->withHeaders([
                    'User-Agent' => 'Agricart-FXLearn/1.0',
                    'Accept' => 'application/json',
                ])
                ->get(self::FF_URL);

            if (! $response->successful()) {
                Log::warning('FX Learn FF HTTP failed', ['status' => $response->status()]);

                return [];
            }

            $data = $response->json();

            if (! is_array($data)) {
                return [];
            }

            return $this->parseFfPayload($data);
        } catch (Throwable $e) {
            Log::warning('FX Learn FF fetch failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @param  list<mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function parseFfPayload(array $data): array
    {
        $out = [];

        foreach (array_values($data) as $idx => $item) {
            if (! is_array($item)) {
                continue;
            }

            $mapped = $this->mapFfItem($item, $idx);

            if ($mapped !== null) {
                $out[] = $mapped;
            }
        }

        usort($out, static fn (array $a, array $b): int => $a['at'] <=> $b['at']);

        return $out;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    private function mapFfItem(array $item, int $idx): ?array
    {
        $impact = $this->mapImpact($item['impact'] ?? null);

        if ($impact === null) {
            return null;
        }

        $at = $this->parseInstant($item['date'] ?? null);

        if ($at === null) {
            return null;
        }

        $ccy = strtoupper((string) ($item['country'] ?? 'USD'));

        if ($ccy === 'ALL') {
            $ccy = 'USD';
        }

        if (! isset(self::CCY_COUNTRY[$ccy])) {
            return null;
        }

        $title = trim((string) ($item['title'] ?? 'Event')) ?: 'Event';
        $impact = $this->normalizeMarketImpact($title, $impact);

        return [
            'id' => 'ff-'.$idx.'-'.$at,
            'ccy' => $ccy,
            'impact' => $impact,
            'title' => $title,
            'country' => self::CCY_COUNTRY[$ccy] ?? $ccy,
            'at' => $at,
            'forecast' => $this->dash($item['forecast'] ?? null),
            'previous' => $this->dash($item['previous'] ?? null),
            'actual' => $this->dash($item['actual'] ?? null),
            'src' => 'ff',
        ];
    }

    private function mapImpact(mixed $raw): ?string
    {
        $s = strtolower(trim((string) $raw));

        return match ($s) {
            'high' => 'high',
            'medium', 'med' => 'med',
            'low' => 'low',
            'holiday', 'none', '' => null,
            default => 'low',
        };
    }

    private function normalizeMarketImpact(string $title, string $impact): string
    {
        $s = strtolower($title);
        $base = $impact;

        if (preg_match('/auction|speaks|speech|testimony|minutes\b|leading index|steel import|petroleum|natural gas|eia weekly/', $s) === 1) {
            if (str_contains($s, 'auction')) {
                return 'low';
            }

            return match ($base) {
                'high' => 'med',
                'med' => 'low',
                default => $base,
            };
        }

        if (preg_match('/fomc|federal funds|rate decision|interest rate|non-farm|nonfarm|\bnfp\b|payroll|\badp\b|cpi\b|core cpi|pce\b|core pce|gdp\b|jobless claims|consumer confidence|durable goods|press conference|monetary policy|\bism\b|\bjolts\b|michigan|ecb\b|boe\b|boc\b|rba\b|rbnz|boj\b/', $s) === 1) {
            return 'high';
        }

        if (preg_match('/retail sales|flash pmi|pmi\b|ifo |zew |trade balance|housing starts|building permits/', $s) === 1) {
            return $base === 'low' ? 'med' : $base;
        }

        return $base;
    }

    private function parseInstant(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return (int) (CarbonImmutable::parse((string) $value)->getTimestampMs());
        } catch (Throwable) {
            return null;
        }
    }

    private function dash(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        $s = trim((string) $value);

        return $s !== '' ? $s : '—';
    }

    private function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        $s = trim((string) $value);

        return $s === '' || $s === '—';
    }

    /**
     * Offline / rate-limit safety net — covers the full PKT week so View is never stuck on 3 rows.
     *
     * @return list<array<string, mixed>>
     */
    private function fallbackEvents(CarbonImmutable $nowPkt): array
    {
        $week0 = $nowPkt->startOfWeek(CarbonImmutable::MONDAY)->setTime(8, 0);
        $samples = [
            ['USD', 'high', 'Core CPI m/m', '0.3%', '0.2%', '0.3%'],
            ['EUR', 'med', 'German ifo Business Climate', '90.0', '89.9', '—'],
            ['GBP', 'high', 'GDP q/q', '0.1%', '0.0%', '—'],
            ['JPY', 'med', 'SPPI y/y', '2.1%', '2.0%', '—'],
            ['USD', 'high', 'Unemployment Claims', '220K', '218K', '—'],
            ['EUR', 'low', 'Italian CPI m/m', '0.1%', '0.0%', '—'],
            ['AUD', 'med', 'CPI q/q', '0.8%', '0.7%', '—'],
            ['CAD', 'high', 'BoC Rate Decision', '2.75%', '2.75%', '—'],
            ['USD', 'high', 'FOMC Statement', '—', '—', '—'],
            ['GBP', 'med', 'CPI y/y', '2.4%', '2.6%', '—'],
            ['EUR', 'high', 'ECB Press Conference', '—', '—', '—'],
            ['USD', 'med', 'Retail Sales m/m', '0.4%', '0.3%', '—'],
            ['JPY', 'high', 'BoJ Policy Rate', '0.50%', '0.50%', '—'],
            ['CHF', 'low', 'Trade Balance', '3.2B', '2.9B', '—'],
            ['NZD', 'med', 'GDP q/q', '0.3%', '0.2%', '—'],
            ['USD', 'high', 'Non-Farm Payrolls', '180K', '175K', '—'],
            ['EUR', 'med', 'Flash CPI y/y', '2.1%', '2.2%', '—'],
            ['GBP', 'low', 'CBI Realized Sales', '-20', '-25', '—'],
            ['USD', 'med', 'Consumer Confidence', '105.0', '104.2', '—'],
            ['CAD', 'med', 'CPI m/m', '0.2%', '0.1%', '—'],
            ['AUD', 'high', 'RBA Rate Decision', '3.60%', '3.60%', '—'],
            ['USD', 'low', 'Building Permits', '1.45M', '1.42M', '—'],
            ['EUR', 'high', 'German GDP q/q', '0.1%', '0.0%', '—'],
            ['GBP', 'high', 'BoE Rate Decision', '4.00%', '4.00%', '—'],
        ];

        $out = [];

        foreach ($samples as $i => [$ccy, $impact, $title, $forecast, $previous, $actual]) {
            $day = intdiv($i, 4);
            $slot = ($i % 4) * 2;
            $at = $week0->addDays($day)->addHours($slot)->utc()->getTimestampMs();
            $out[] = [
                'id' => 'fb-'.$i.'-'.$at,
                'ccy' => $ccy,
                'impact' => $impact,
                'title' => $title,
                'country' => self::CCY_COUNTRY[$ccy] ?? $ccy,
                'at' => $at,
                'forecast' => $forecast,
                'previous' => $previous,
                'actual' => $actual,
                'src' => 'backup',
            ];
        }

        usort($out, static fn (array $a, array $b): int => $a['at'] <=> $b['at']);

        return $out;
    }
}
