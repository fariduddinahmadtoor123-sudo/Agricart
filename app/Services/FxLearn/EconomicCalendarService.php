<?php

namespace App\Services\FxLearn;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EconomicCalendarService
{
    private const FF_URL = 'https://nfs.faireconomy.media/ff_calendar_thisweek.json';

    private const FC_BASE = 'https://www.financecalendar.com/wp-json/fc/v1/calendar';

    private const CACHE_KEY = 'fxlearn.econ.events.v1';

    private const CACHE_TTL_SECONDS = 900;

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
        'ALL' => 'Global',
    ];

    /** @var array<string, string> */
    private const COUNTRY_TO_CCY = [
        'US' => 'USD',
        'GB' => 'GBP',
        'UK' => 'GBP',
        'EU' => 'EUR',
        'DE' => 'EUR',
        'FR' => 'EUR',
        'IT' => 'EUR',
        'ES' => 'EUR',
        'JP' => 'JPY',
        'CA' => 'CAD',
        'AU' => 'AUD',
        'NZ' => 'NZD',
        'CH' => 'CHF',
        'CN' => 'CNY',
        'HK' => 'HKD',
        'IN' => 'INR',
    ];

    /**
     * @return array{events: list<array<string, mixed>>, source: string, fetched_at: string}
     */
    public function snapshot(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            return $this->fetchFresh();
        });
    }

    public function refresh(): array
    {
        Cache::forget(self::CACHE_KEY);

        return $this->snapshot();
    }

    /**
     * @return array{events: list<array<string, mixed>>, source: string, fetched_at: string}
     */
    private function fetchFresh(): array
    {
        $tz = agricart_display_timezone();
        $now = CarbonImmutable::now($tz);
        $ff = $this->fetchForexFactory();
        $fc = $this->fetchFinanceCalendar(
            $now->subDays(10)->toDateString(),
            $now->addDays(16)->toDateString(),
        );

        $merged = $this->mergeEventLists($ff, $fc);
        $source = 'backup';

        if ($ff !== [] && $fc !== []) {
            $source = 'ff+fc';
        } elseif ($ff !== []) {
            $source = 'ff';
        } elseif ($fc !== []) {
            $source = 'fc';
        }

        if ($merged === []) {
            $merged = $this->fallbackEvents($now);
            $source = 'backup';
        }

        return [
            'events' => $merged,
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
            $response = Http::timeout(12)
                ->withHeaders([
                    'User-Agent' => 'Agricart-FXLearn/1.0',
                    'Accept' => 'application/json',
                ])
                ->get(self::FF_URL);

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();

            if (! is_array($data)) {
                return [];
            }

            return $this->parseFfPayload($data);
        } catch (Throwable $e) {
            Log::warning('FX Learn FF calendar fetch failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchFinanceCalendar(string $from, string $to): array
    {
        $url = self::FC_BASE.'?'.http_build_query([
            'from' => $from,
            'to' => $to,
            'country' => 'US,GB,EU,DE,JP,CA,AU,CN,CH,NZ',
            'impact' => 'high,medium,low',
            'limit' => 500,
        ]);

        try {
            $response = Http::timeout(12)
                ->withHeaders([
                    'User-Agent' => 'Agricart-FXLearn/1.0',
                    'Accept' => 'application/json',
                ])
                ->get($url);

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();

            return $this->parseFcPayload(is_array($data) ? $data : []);
        } catch (Throwable $e) {
            Log::warning('FX Learn FC calendar fetch failed', ['error' => $e->getMessage()]);

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
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function parseFcPayload(array $data): array
    {
        $rows = [];

        if (isset($data['events']) && is_array($data['events'])) {
            $rows = $data['events'];
        } elseif (array_is_list($data)) {
            $rows = $data;
        }

        $out = [];

        foreach (array_values($rows) as $idx => $item) {
            if (! is_array($item)) {
                continue;
            }

            $mapped = $this->mapFcItem($item, $idx);

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

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    private function mapFcItem(array $item, int $idx): ?array
    {
        if (($item['category'] ?? null) === 'market-holidays') {
            return null;
        }

        $impact = $this->mapImpact($item['impact'] ?? null);

        if ($impact === null) {
            return null;
        }

        $at = $this->parseInstant($item['release_at_utc'] ?? $item['time_utc'] ?? null);

        if ($at === null && ! empty($item['date'])) {
            $at = $this->parseInstant((string) $item['date'].'T12:00:00Z');
        }

        if ($at === null) {
            return null;
        }

        $rawCcy = strtoupper((string) ($item['country'] ?? ''));
        $ccy = self::COUNTRY_TO_CCY[$rawCcy]
            ?? (isset(self::CCY_COUNTRY[$rawCcy]) ? $rawCcy : '');

        if ($ccy === '') {
            return null;
        }

        $title = trim((string) ($item['title'] ?? $item['name'] ?? $item['series_name'] ?? 'Event')) ?: 'Event';
        $impact = $this->normalizeMarketImpact($title, $impact);

        return [
            'id' => 'fc-'.$idx.'-'.$at,
            'ccy' => $ccy,
            'impact' => $impact,
            'title' => $title,
            'country' => self::CCY_COUNTRY[$ccy] ?? $ccy,
            'at' => $at,
            'forecast' => $this->dash($item['consensus'] ?? null),
            'previous' => $this->dash($item['prior'] ?? null),
            'actual' => $this->dash($item['actual'] ?? null),
            'src' => 'fc',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $primary  Preferred (FF)
     * @param  list<array<string, mixed>>  $secondary
     * @return list<array<string, mixed>>
     */
    private function mergeEventLists(array $primary, array $secondary): array
    {
        $map = [];
        $order = [];

        foreach ([$secondary, $primary] as $listIndex => $list) {
            $prefer = $listIndex === 1;

            foreach ($list as $ev) {
                $key = $this->dedupeKey($ev);

                if (! isset($map[$key])) {
                    $map[$key] = $ev;
                    $order[] = $key;
                } elseif ($prefer) {
                    $map[$key] = $ev;
                }
            }
        }

        $out = array_map(static fn (string $k): array => $map[$k], $order);
        usort($out, static fn (array $a, array $b): int => $a['at'] <=> $b['at']);

        return $out;
    }

    /**
     * @param  array<string, mixed>  $ev
     */
    private function dedupeKey(array $ev): string
    {
        $day = CarbonImmutable::createFromTimestampMs((int) $ev['at'], 'UTC')->toDateString();
        $title = strtolower((string) ($ev['title'] ?? ''));
        $title = preg_replace('/[^a-z0-9]+/', ' ', $title) ?? '';
        $title = trim(substr($title, 0, 48));

        return $day.'|'.$ev['ccy'].'|'.$title;
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

        if (preg_match('/auction|speaks|speech|testimony|minutes\b|leading index|steel import|petroleum|natural gas|eia weekly|advance economic indicators|vesting|unlock|vacancies|homeownership|metropolitan area employment|business employment dynamics|consumer expenditures\b/', $s) === 1) {
            if (str_contains($s, 'auction')) {
                return 'low';
            }

            return match ($base) {
                'high' => 'med',
                'med' => 'low',
                default => $base,
            };
        }

        if (preg_match('/fomc|federal funds|rate decision|interest rate|overnight rate|official cash rate|refinancing rate|bank rate|non-farm|nonfarm|\bnfp\b|payroll|employment change|employment cost|\badp\b|cpi\b|core cpi|pce\b|core pce|personal income|personal spending|gdp\b|jobless claims|unemployment claims|consumer confidence|durable goods|press conference|monetary policy|policy rate|\bism\b|\bjolts\b|michigan|ecb\b|boe\b|boc\b|rba\b|rbnz|boj\b/', $s) === 1) {
            return 'high';
        }

        if (preg_match('/retail sales|flash pmi|pmi\b|ifo |zew |trade balance|housing starts|building permits|new home sales|existing home|construction spending/', $s) === 1) {
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

    /**
     * @return list<array<string, mixed>>
     */
    private function fallbackEvents(CarbonImmutable $nowPkt): array
    {
        $base = $nowPkt->utc();
        $samples = [
            ['USD', 'high', 'Core CPI m/m', '0.3%', '0.2%', '—', 38],
            ['USD', 'med', 'CPI m/m', '0.2%', '0.1%', '—', 38],
            ['GBP', 'high', 'GDP q/q', '0.1%', '0.0%', '—', 95],
            ['EUR', 'high', 'ECB Press Conference', '—', '—', '—', 160],
            ['USD', 'high', 'Initial Jobless Claims', '220K', '218K', '223K', -45],
            ['JPY', 'med', 'BOJ Policy Rate', '0.50%', '0.50%', '—', 240],
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
