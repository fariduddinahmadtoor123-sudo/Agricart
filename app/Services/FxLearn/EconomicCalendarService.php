<?php

namespace App\Services\FxLearn;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class EconomicCalendarService
{
    private const FC_BASE = 'https://www.financecalendar.com/wp-json/fc/v1/calendar';

    private const SMT_URL = 'https://showmytrades.com/api/public/calendar/v1';

    private const CACHE_KEY = 'fxlearn.econ.events.v2';

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
        'HKD' => 'Hong Kong',
        'INR' => 'India',
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
     * @return array{
     *     events: list<array<string, mixed>>,
     *     feeds: array{fc: list<array<string, mixed>>, smt: list<array<string, mixed>>},
     *     counts: array{ag: int, fc: int, smt: int},
     *     source: string,
     *     fetched_at: string
     * }
     */
    public function snapshot(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): array {
            return $this->fetchFresh();
        });
    }

    /**
     * @return array{
     *     events: list<array<string, mixed>>,
     *     feeds: array{fc: list<array<string, mixed>>, smt: list<array<string, mixed>>},
     *     counts: array{ag: int, fc: int, smt: int},
     *     source: string,
     *     fetched_at: string
     * }
     */
    public function refresh(): array
    {
        Cache::forget(self::CACHE_KEY);

        return $this->snapshot();
    }

    /**
     * @return array{
     *     events: list<array<string, mixed>>,
     *     feeds: array{fc: list<array<string, mixed>>, smt: list<array<string, mixed>>},
     *     counts: array{ag: int, fc: int, smt: int},
     *     source: string,
     *     fetched_at: string
     * }
     */
    private function fetchFresh(): array
    {
        $tz = agricart_display_timezone();
        $now = CarbonImmutable::now($tz);
        $from = $now->subDays(10)->toDateString();
        $to = $now->addDays(16)->toDateString();

        [$fc, $smt] = $this->fetchFeedsParallel($from, $to);

        $merged = $this->mergeFeeds($fc, $smt);
        $source = $this->resolveSourceLabel($fc, $smt);

        if ($merged === []) {
            $merged = $this->fallbackEvents($now);
            $source = 'backup';
        }

        return [
            'events' => $merged,
            'feeds' => [
                'fc' => $fc,
                'smt' => $smt,
            ],
            'counts' => [
                'ag' => count($merged),
                'fc' => count($fc),
                'smt' => count($smt),
            ],
            'source' => $source,
            'fetched_at' => CarbonImmutable::now('UTC')->toIso8601String(),
        ];
    }

    /**
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function fetchFeedsParallel(string $from, string $to): array
    {
        $fcUrl = self::FC_BASE.'?'.http_build_query([
            'from' => $from,
            'to' => $to,
            'country' => 'US,GB,EU,DE,JP,CA,AU,CN,CH,NZ',
            'impact' => 'high,medium,low',
            'limit' => 500,
        ]);

        $smtUrl = self::SMT_URL.'?'.http_build_query([
            'impact' => 'high,medium,low',
            'days' => 30,
        ]);

        $headers = [
            'User-Agent' => 'Agricart-FXLearn/1.0',
            'Accept' => 'application/json',
        ];

        try {
            $responses = Http::pool(fn (Pool $pool): array => [
                $pool->as('fc')->timeout(14)->withHeaders($headers)->get($fcUrl),
                $pool->as('smt')->timeout(14)->withHeaders($headers)->get($smtUrl),
            ]);

            $fc = $this->parseFcResponse($responses['fc'] ?? null);
            $smt = $this->parseSmtResponse($responses['smt'] ?? null);

            return [$fc, $smt];
        } catch (Throwable $e) {
            Log::warning('FX Learn calendar pool failed', ['error' => $e->getMessage()]);

            return [
                $this->fetchFinanceCalendar($from, $to),
                $this->fetchShowMyTrades(),
            ];
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
            $response = Http::timeout(14)
                ->withHeaders([
                    'User-Agent' => 'Agricart-FXLearn/1.0',
                    'Accept' => 'application/json',
                ])
                ->get($url);

            return $this->parseFcResponse($response);
        } catch (Throwable $e) {
            Log::warning('FX Learn FC calendar fetch failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchShowMyTrades(): array
    {
        $url = self::SMT_URL.'?'.http_build_query([
            'impact' => 'high,medium,low',
            'days' => 30,
        ]);

        try {
            $response = Http::timeout(14)
                ->withHeaders([
                    'User-Agent' => 'Agricart-FXLearn/1.0',
                    'Accept' => 'application/json',
                ])
                ->get($url);

            return $this->parseSmtResponse($response);
        } catch (Throwable $e) {
            Log::warning('FX Learn SMT calendar fetch failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseFcResponse(mixed $response): array
    {
        if (! $response instanceof Response || ! $response->successful()) {
            if ($response instanceof Response) {
                Log::warning('FX Learn FC calendar HTTP failed', ['status' => $response->status()]);
            }

            return [];
        }

        $data = $response->json();

        return $this->parseFcPayload(is_array($data) ? $data : []);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseSmtResponse(mixed $response): array
    {
        if (! $response instanceof Response || ! $response->successful()) {
            if ($response instanceof Response) {
                Log::warning('FX Learn SMT calendar HTTP failed', ['status' => $response->status()]);
            }

            return [];
        }

        $data = $response->json();

        return $this->parseSmtPayload(is_array($data) ? $data : []);
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
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function parseSmtPayload(array $data): array
    {
        $rows = [];

        if (isset($data['events']) && is_array($data['events'])) {
            $rows = $data['events'];
        }

        $out = [];

        foreach (array_values($rows) as $idx => $item) {
            if (! is_array($item)) {
                continue;
            }

            $mapped = $this->mapSmtItem($item, $idx);

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

        return $this->eventRow(
            id: 'fc-'.$idx.'-'.$at,
            ccy: $ccy,
            impact: $impact,
            title: $title,
            at: $at,
            forecast: $this->dash($item['consensus'] ?? null),
            previous: $this->dash($item['prior'] ?? null),
            actual: $this->dash($item['actual'] ?? null),
            src: 'fc',
            sources: ['fc'],
        );
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    private function mapSmtItem(array $item, int $idx): ?array
    {
        $impact = $this->mapImpact($item['impact'] ?? null);

        if ($impact === null) {
            return null;
        }

        $at = null;

        if (isset($item['ts']) && is_numeric($item['ts'])) {
            $at = ((int) $item['ts']) * 1000;
        }

        if ($at === null) {
            $at = $this->parseInstant($item['time'] ?? null);
        }

        if ($at === null) {
            return null;
        }

        $ccy = strtoupper((string) ($item['currency'] ?? ''));

        if ($ccy === '' || ! isset(self::CCY_COUNTRY[$ccy])) {
            return null;
        }

        $title = trim((string) ($item['title'] ?? 'Event')) ?: 'Event';
        $impact = $this->normalizeMarketImpact($title, $impact);

        return $this->eventRow(
            id: 'smt-'.$idx.'-'.$at,
            ccy: $ccy,
            impact: $impact,
            title: $title,
            at: $at,
            forecast: $this->dash($item['forecast'] ?? null),
            previous: $this->dash($item['previous'] ?? null),
            actual: $this->dash($item['actual'] ?? null),
            src: 'smt',
            sources: ['smt'],
        );
    }

    /**
     * @param  list<string>  $sources
     * @return array<string, mixed>
     */
    private function eventRow(
        string $id,
        string $ccy,
        string $impact,
        string $title,
        int $at,
        string $forecast,
        string $previous,
        string $actual,
        string $src,
        array $sources,
    ): array {
        return [
            'id' => $id,
            'ccy' => $ccy,
            'impact' => $impact,
            'title' => $title,
            'country' => self::CCY_COUNTRY[$ccy] ?? $ccy,
            'at' => $at,
            'forecast' => $forecast,
            'previous' => $previous,
            'actual' => $actual,
            'src' => $src,
            'sources' => array_values(array_unique($sources)),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $fc
     * @param  list<array<string, mixed>>  $smt
     * @return list<array<string, mixed>>
     */
    private function mergeFeeds(array $fc, array $smt): array
    {
        $map = [];
        $order = [];

        foreach ([$fc, $smt] as $list) {
            foreach ($list as $ev) {
                $key = $this->dedupeKey($ev);

                if (! isset($map[$key])) {
                    $row = $ev;
                    $row['src'] = 'ag';
                    $row['id'] = 'ag-'.substr(sha1($key), 0, 12).'-'.$ev['at'];
                    $map[$key] = $row;
                    $order[] = $key;

                    continue;
                }

                $map[$key] = $this->blendEvents($map[$key], $ev);
            }
        }

        $out = array_map(static fn (string $k): array => $map[$k], $order);
        usort($out, static fn (array $a, array $b): int => $a['at'] <=> $b['at']);

        return $out;
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function blendEvents(array $base, array $incoming): array
    {
        $sources = array_values(array_unique(array_merge(
            is_array($base['sources'] ?? null) ? $base['sources'] : [],
            is_array($incoming['sources'] ?? null) ? $incoming['sources'] : [(string) ($incoming['src'] ?? '')],
        )));
        $sources = array_values(array_filter($sources, static fn (string $s): bool => $s !== '' && $s !== 'ag'));

        $base['sources'] = $sources;
        $base['src'] = 'ag';
        $base['forecast'] = $this->preferValue($base['forecast'] ?? '—', $incoming['forecast'] ?? '—');
        $base['previous'] = $this->preferValue($base['previous'] ?? '—', $incoming['previous'] ?? '—');
        $base['actual'] = $this->preferValue($base['actual'] ?? '—', $incoming['actual'] ?? '—');
        $base['impact'] = $this->preferImpact($base['impact'] ?? 'low', $incoming['impact'] ?? 'low');

        if (strlen((string) ($incoming['title'] ?? '')) > strlen((string) ($base['title'] ?? ''))) {
            $base['title'] = $incoming['title'];
        }

        return $base;
    }

    private function preferValue(string $a, string $b): string
    {
        $aEmpty = $a === '' || $a === '—';
        $bEmpty = $b === '' || $b === '—';

        if ($aEmpty && ! $bEmpty) {
            return $b;
        }

        if (! $aEmpty && $bEmpty) {
            return $a;
        }

        if (! $aEmpty && ! $bEmpty && $a !== $b) {
            return strlen($b) > strlen($a) ? $b : $a;
        }

        return $aEmpty ? '—' : $a;
    }

    private function preferImpact(string $a, string $b): string
    {
        $rank = ['low' => 1, 'med' => 2, 'high' => 3];

        return ($rank[$b] ?? 0) > ($rank[$a] ?? 0) ? $b : $a;
    }

    /**
     * @param  list<array<string, mixed>>  $fc
     * @param  list<array<string, mixed>>  $smt
     */
    private function resolveSourceLabel(array $fc, array $smt): string
    {
        if ($fc !== [] && $smt !== []) {
            return 'fc+smt';
        }

        if ($fc !== []) {
            return 'fc';
        }

        if ($smt !== []) {
            return 'smt';
        }

        return 'backup';
    }

    /**
     * @param  array<string, mixed>  $ev
     */
    private function dedupeKey(array $ev): string
    {
        $day = CarbonImmutable::createFromTimestampMs((int) $ev['at'], 'UTC')->toDateString();
        $hour = CarbonImmutable::createFromTimestampMs((int) $ev['at'], 'UTC')->format('H');
        $title = strtolower((string) ($ev['title'] ?? ''));
        $title = preg_replace('/[^a-z0-9]+/', ' ', $title) ?? '';
        $title = trim(substr($title, 0, 48));

        return $day.'|'.$hour.'|'.$ev['ccy'].'|'.$title;
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
            $out[] = $this->eventRow(
                id: 'fb-'.$i.'-'.$at,
                ccy: $ccy,
                impact: $impact,
                title: $title,
                at: $at,
                forecast: $forecast,
                previous: $previous,
                actual: $actual,
                src: 'backup',
                sources: ['backup'],
            );
        }

        usort($out, static fn (array $a, array $b): int => $a['at'] <=> $b['at']);

        return $out;
    }
}
