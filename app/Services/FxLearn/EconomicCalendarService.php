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

    private const CACHE_KEY = 'fxlearn.econ.ff.v1';

    private const CACHE_TTL_SECONDS = 900;

    /** Prevent hammering FF while waiting for Actual. */
    private const ACTUAL_REFRESH_LOCK_KEY = 'fxlearn.econ.ff.actual_lock';

    private const ACTUAL_REFRESH_LOCK_SECONDS = 45;

    /** Seconds before a high-impact release when we start watching. */
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
        $data = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            return $this->fetchFresh();
        });

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
        Cache::put(self::CACHE_KEY, $fresh, self::CACHE_TTL_SECONDS);

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
            Cache::put(self::CACHE_KEY, $fresh, self::CACHE_TTL_SECONDS);

            return $fresh;
        } catch (Throwable $e) {
            Log::warning('FX Learn FF actual refresh failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $events
     */
    private function shouldRefreshForActual(array $events): bool
    {
        $nowMs = (int) CarbonImmutable::now('UTC')->getTimestampMs();

        foreach ($events as $ev) {
            if (($ev['impact'] ?? '') !== 'high') {
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
        $source = 'ff';

        if ($events === []) {
            $events = $this->fallbackEvents(CarbonImmutable::now(agricart_display_timezone()));
            $source = 'backup';
        }

        return [
            'events' => $events,
            'source' => $source,
            'fetched_at' => CarbonImmutable::now('UTC')->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchForexFactory(): array
    {
        try {
            $response = Http::timeout(14)
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
     * @return list<array<string, mixed>>
     */
    private function fallbackEvents(CarbonImmutable $nowPkt): array
    {
        $base = $nowPkt->utc();
        $samples = [
            ['USD', 'high', 'Core CPI m/m', '0.3%', '0.2%', '—', 38],
            ['GBP', 'high', 'GDP q/q', '0.1%', '0.0%', '—', 95],
            ['EUR', 'med', 'German ifo Business Climate', '90.0', '89.9', '—', 160],
        ];

        $out = [];

        foreach ($samples as $i => [$ccy, $impact, $title, $forecast, $previous, $actual, $offsetMin]) {
            $at = $base->addMinutes($offsetMin)->getTimestampMs();
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
