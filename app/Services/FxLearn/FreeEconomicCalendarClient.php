<?php

namespace App\Services\FxLearn;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Free calendar feeds used by FTI: Forex Factory weekly JSON + Finance Calendar API.
 */
class FreeEconomicCalendarClient
{
    public const FF_URL = 'https://nfs.faireconomy.media/ff_calendar_thisweek.json';

    public const FC_BASE = 'https://www.financecalendar.com/wp-json/fc/v1/calendar';

    /**
     * @return array{
     *     ok: bool,
     *     events: list<array<string, mixed>>,
     *     error: string|null,
     *     source: string,
     *     meta: array<string, mixed>
     * }
     */
    public function fetchWeek(string $fromYmd, string $toYmd): array
    {
        $meta = [
            'from' => $fromYmd,
            'to' => $toYmd,
            'ff_ok' => false,
            'fc_ok' => false,
            'ff_count' => 0,
            'fc_count' => 0,
        ];

        $ff = $this->fetchFf();
        $fc = $this->fetchFc($fromYmd, $toYmd);

        $meta['ff_ok'] = $ff['ok'];
        $meta['fc_ok'] = $fc['ok'];
        $meta['ff_count'] = count($ff['events']);
        $meta['fc_count'] = count($fc['events']);

        if ($ff['error'] !== null) {
            Log::warning('fx-learn.free.ff', ['error' => $ff['error']]);
        }

        if ($fc['error'] !== null) {
            Log::warning('fx-learn.free.fc', ['error' => $fc['error']]);
        }

        // Prefer FF on overlap (schedule + forecast/previous); FC fills Actual / wider coverage.
        $merged = $this->merge($ff['events'], $fc['events']);

        if ($merged === []) {
            $parts = array_filter([
                $ff['error'],
                $fc['error'],
            ]);

            return [
                'ok' => false,
                'events' => [],
                'error' => $parts !== []
                    ? implode(' · ', $parts)
                    : __('agricart.pages.fx-learn.free_feed_empty'),
                'source' => 'empty',
                'meta' => $meta,
            ];
        }

        $source = match (true) {
            $ff['ok'] && $fc['ok'] => 'ff+fc',
            $ff['ok'] => 'ff',
            $fc['ok'] => 'fc',
            default => 'partial',
        };

        return [
            'ok' => true,
            'events' => $merged,
            'error' => null,
            'source' => $source,
            'meta' => $meta,
        ];
    }

    /**
     * @return array{ok: bool, events: list<array<string, mixed>>, error: string|null}
     */
    protected function fetchFf(): array
    {
        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'Agricart-FXLearn/1.0'])
                ->get(self::FF_URL);

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'events' => [],
                    'error' => __('agricart.pages.fx-learn.free_ff_http', ['status' => $response->status()]),
                ];
            }

            $json = $response->json();

            if (! is_array($json)) {
                return [
                    'ok' => false,
                    'events' => [],
                    'error' => __('agricart.pages.fx-learn.free_ff_bad'),
                ];
            }

            $events = [];

            foreach ($json as $idx => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $mapped = $this->mapFfItem($item, (int) $idx);

                if ($mapped !== null) {
                    $events[] = $mapped;
                }
            }

            return ['ok' => true, 'events' => $events, 'error' => null];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'events' => [],
                'error' => __('agricart.pages.fx-learn.free_ff_error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * @return array{ok: bool, events: list<array<string, mixed>>, error: string|null}
     */
    protected function fetchFc(string $fromYmd, string $toYmd): array
    {
        try {
            $response = Http::timeout(25)
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'Agricart-FXLearn/1.0'])
                ->get(self::FC_BASE, [
                    'from' => $fromYmd,
                    'to' => $toYmd,
                    'country' => 'US,GB,EU,DE,JP,CA,AU,CN,CH,NZ',
                    'impact' => 'high,medium,low',
                    'limit' => 500,
                ]);

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'events' => [],
                    'error' => __('agricart.pages.fx-learn.free_fc_http', ['status' => $response->status()]),
                ];
            }

            $json = $response->json();
            $rows = [];

            if (is_array($json) && isset($json['events']) && is_array($json['events'])) {
                $rows = $json['events'];
            } elseif (is_array($json)) {
                $rows = $json;
            }

            $events = [];

            foreach ($rows as $idx => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $mapped = $this->mapFcItem($item, (int) $idx);

                if ($mapped !== null) {
                    $events[] = $mapped;
                }
            }

            return ['ok' => true, 'events' => $events, 'error' => null];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'events' => [],
                'error' => __('agricart.pages.fx-learn.free_fc_error', ['error' => $e->getMessage()]),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    protected function mapFfItem(array $item, int $idx): ?array
    {
        $impact = $this->mapImpact($item['impact'] ?? null);

        if ($impact === null) {
            return null;
        }

        $title = trim((string) ($item['title'] ?? ''));

        if ($title === '') {
            return null;
        }

        try {
            $at = CarbonImmutable::parse((string) ($item['date'] ?? ''));
        } catch (Throwable) {
            return null;
        }

        $ccy = strtoupper(trim((string) ($item['country'] ?? 'USD')));

        if ($ccy === '' || $ccy === 'ALL') {
            $ccy = 'USD';
        }

        $impact = $this->normalizeMarketImpact($title, $impact);

        return [
            'id' => 'ff-'.sha1($ccy.'|'.$title.'|'.$at->toIso8601String().'|'.$idx),
            'title' => $title,
            'country' => $ccy,
            'ccy' => $ccy,
            'impact' => $impact,
            'at' => (int) $at->valueOf(),
            'previous' => $this->dash($item['previous'] ?? null),
            'forecast' => $this->dash($item['forecast'] ?? null),
            'actual' => $this->dash($item['actual'] ?? null),
            'unit' => '',
            'src' => 'ff',
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    protected function mapFcItem(array $item, int $idx): ?array
    {
        if (($item['category'] ?? '') === 'market-holidays') {
            return null;
        }

        $impact = $this->mapImpact($item['impact'] ?? null);

        if ($impact === null) {
            return null;
        }

        $title = trim((string) ($item['title'] ?? $item['name'] ?? $item['series_name'] ?? ''));

        if ($title === '') {
            return null;
        }

        $rawTime = (string) ($item['release_at_utc'] ?? $item['time_utc'] ?? '');

        try {
            if ($rawTime !== '') {
                $at = CarbonImmutable::parse($rawTime);
            } elseif (! empty($item['date'])) {
                $at = CarbonImmutable::parse((string) $item['date'].'T12:00:00Z');
            } else {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        $rawCountry = strtoupper(trim((string) ($item['country'] ?? '')));
        $ccy = $this->countryToCcy($rawCountry);

        if ($ccy === null) {
            return null;
        }

        $impact = $this->normalizeMarketImpact($title, $impact);

        $fromObs = $this->figuresFromObservations($item);

        $previous = $this->dash($item['prior'] ?? null);
        $forecast = $this->dash($item['consensus'] ?? null);
        $actual = $this->dash($item['actual'] ?? null);

        if ($this->isBlank($previous) && ! $this->isBlank($fromObs['previous'])) {
            $previous = $fromObs['previous'];
        }

        if ($this->isBlank($forecast) && ! $this->isBlank($fromObs['forecast'])) {
            $forecast = $fromObs['forecast'];
        }

        if ($this->isBlank($actual) && ! $this->isBlank($fromObs['actual'])) {
            $actual = $fromObs['actual'];
        }

        $unit = trim((string) ($item['unit'] ?? ''));

        if ($unit === '' && $fromObs['unit'] !== '') {
            $unit = $fromObs['unit'];
        }

        return [
            'id' => 'fc-'.sha1($ccy.'|'.$title.'|'.$at->toIso8601String().'|'.$idx),
            'title' => $title,
            'country' => $rawCountry !== '' ? $rawCountry : $ccy,
            'ccy' => $ccy,
            'impact' => $impact,
            'at' => (int) $at->valueOf(),
            'previous' => $previous,
            'forecast' => $forecast,
            'actual' => $actual,
            'unit' => $unit,
            'src' => 'fc',
        ];
    }

    /**
     * FF preferred on overlap; fill blank Actual/Previous/Forecast from the other.
     * Falls back to same-day+ccy fuzzy title match when exact keys diverge.
     *
     * @param  list<array<string, mixed>>  $ff
     * @param  list<array<string, mixed>>  $fc
     * @return list<array<string, mixed>>
     */
    protected function merge(array $ff, array $fc): array
    {
        /** @var array<string, array<string, mixed>> $map */
        $map = [];
        $order = [];

        foreach ($fc as $ev) {
            $key = $this->dedupeKey($ev);

            if (! isset($map[$key])) {
                $map[$key] = $ev;
                $order[] = $key;
            } else {
                $map[$key] = $this->preferFilled($map[$key], $ev);
            }
        }

        foreach ($ff as $ev) {
            $key = $this->dedupeKey($ev);

            if (isset($map[$key])) {
                $map[$key] = $this->preferFilled($ev, $map[$key]);

                continue;
            }

            $fuzzyKey = $this->findFuzzyKey($ev, $map);

            if ($fuzzyKey !== null) {
                $map[$fuzzyKey] = $this->preferFilled($ev, $map[$fuzzyKey]);

                continue;
            }

            $map[$key] = $ev;
            $order[] = $key;
        }

        $out = [];

        foreach ($order as $key) {
            $out[] = $map[$key];
        }

        usort($out, fn (array $a, array $b): int => ((int) $a['at']) <=> ((int) $b['at']));

        return $out;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{previous: string, forecast: string, actual: string, unit: string}
     */
    protected function figuresFromObservations(array $item): array
    {
        $out = ['previous' => '—', 'forecast' => '—', 'actual' => '—', 'unit' => ''];
        $rows = $item['observations'] ?? null;

        if (! is_array($rows) || $rows === []) {
            return $out;
        }

        foreach ($rows as $obs) {
            if (! is_array($obs)) {
                continue;
            }

            $role = strtolower(trim((string) ($obs['role'] ?? '')));
            $value = $this->dash($obs['value'] ?? null);

            if ($this->isBlank($value)) {
                continue;
            }

            if ($out['unit'] === '') {
                $out['unit'] = trim((string) ($obs['unit'] ?? ''));
            }

            if (in_array($role, ['actual', 'print', 'latest'], true)) {
                $out['actual'] = $value;
            } elseif (in_array($role, ['prior', 'previous', 'prev'], true)) {
                $out['previous'] = $value;
            } elseif (in_array($role, ['consensus', 'forecast', 'estimate'], true)) {
                $out['forecast'] = $value;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $needle
     * @param  array<string, array<string, mixed>>  $map
     */
    protected function findFuzzyKey(array $needle, array $map): ?string
    {
        $day = CarbonImmutable::createFromTimestampMs((int) ($needle['at'] ?? 0), 'UTC')->toDateString();
        $ccy = strtoupper((string) ($needle['ccy'] ?? ''));
        $tokens = $this->titleTokens((string) ($needle['title'] ?? ''));

        if ($tokens === [] || $ccy === '') {
            return null;
        }

        $bestKey = null;
        $bestScore = 0.0;

        foreach ($map as $key => $ev) {
            $otherDay = CarbonImmutable::createFromTimestampMs((int) ($ev['at'] ?? 0), 'UTC')->toDateString();
            $otherCcy = strtoupper((string) ($ev['ccy'] ?? ''));

            if ($otherDay !== $day || $otherCcy !== $ccy) {
                continue;
            }

            $score = $this->tokenOverlap($tokens, $this->titleTokens((string) ($ev['title'] ?? '')));

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestKey = $key;
            }
        }

        return $bestScore >= 0.45 ? $bestKey : null;
    }

    /**
     * @return list<string>
     */
    protected function titleTokens(string $title): array
    {
        $norm = Str::of($title)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();

        $parts = preg_split('/\s+/', $norm) ?: [];
        $stop = [
            'the', 'and', 'of', 'for', 'a', 'an', 'm', 'y', 'q', 'mm', 'yy', 'qq',
            'us', 'usa', 'uk', 'gb', 'eu', 'german', 'germany', 'eurozone', 'euro',
            'zone', 'october', 'november', 'december', 'january', 'february', 'march',
            'april', 'may', 'june', 'july', 'august', 'september', '2025', '2026', '2027',
        ];

        $out = [];

        foreach ($parts as $part) {
            if (strlen($part) < 2 || in_array($part, $stop, true)) {
                continue;
            }

            $out[] = $part;
        }

        return array_values(array_unique($out));
    }

    /**
     * @param  list<string>  $a
     * @param  list<string>  $b
     */
    protected function tokenOverlap(array $a, array $b): float
    {
        if ($a === [] || $b === []) {
            return 0.0;
        }

        $setB = array_fill_keys($b, true);
        $hit = 0;

        foreach ($a as $token) {
            if (isset($setB[$token])) {
                $hit++;
            }
        }

        $union = count(array_unique(array_merge($a, $b)));

        return $union > 0 ? $hit / $union : 0.0;
    }

    /**
     * @param  array<string, mixed>  $preferred
     * @param  array<string, mixed>  $fallback
     * @return array<string, mixed>
     */
    protected function preferFilled(array $preferred, array $fallback): array
    {
        foreach (['previous', 'forecast', 'actual'] as $field) {
            if ($this->isBlank($preferred[$field] ?? null) && ! $this->isBlank($fallback[$field] ?? null)) {
                $preferred[$field] = $fallback[$field];
            }
        }

        if (($preferred['impact'] ?? '') === 'low' && in_array($fallback['impact'] ?? '', ['med', 'high'], true)) {
            $preferred['impact'] = $fallback['impact'];
        }

        return $preferred;
    }

    /**
     * @param  array<string, mixed>  $ev
     */
    protected function dedupeKey(array $ev): string
    {
        $day = CarbonImmutable::createFromTimestampMs((int) ($ev['at'] ?? 0), 'UTC')->toDateString();
        $title = Str::of((string) ($ev['title'] ?? ''))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim()
            ->substr(0, 48)
            ->toString();

        return $day.'|'.strtoupper((string) ($ev['ccy'] ?? '')).'|'.$title;
    }

    protected function mapImpact(mixed $raw): ?string
    {
        $s = strtolower(trim((string) $raw));

        return match ($s) {
            'high', '3', 'red' => 'high',
            'medium', 'med', '2', 'orange', 'yellow' => 'med',
            'low', '1' => 'low',
            'holiday', 'none', '' => null,
            default => 'low',
        };
    }

    protected function normalizeMarketImpact(string $title, string $impact): string
    {
        $s = strtolower($title);

        if (preg_match('/auction|speaks|speech|testimony|minutes\b|leading index|natural gas|eia weekly/i', $s)) {
            if (str_contains($s, 'auction')) {
                return 'low';
            }

            return $impact === 'high' ? 'med' : ($impact === 'med' ? 'low' : $impact);
        }

        if (preg_match('/fomc|federal funds|rate decision|interest rate|overnight rate|official cash rate|non-?farm|\bnfp\b|payroll|cpi\b|core cpi|pce\b|gdp\b|jobless claims|consumer confidence|durable goods|press conference|policy rate|\bism\b|ecb\b|boe\b|boc\b|rba\b|rbnz|boj\b/i', $s)) {
            return 'high';
        }

        if (preg_match('/retail sales|flash pmi|pmi\b|ifo |zew |trade balance|housing starts|building permits|new home sales/i', $s)) {
            return $impact === 'low' ? 'med' : $impact;
        }

        return $impact;
    }

    protected function countryToCcy(string $country): ?string
    {
        return match (strtoupper($country)) {
            'US', 'USA', 'USD' => 'USD',
            'GB', 'UK', 'GBP' => 'GBP',
            'EU', 'EMU', 'EZ', 'DE', 'FR', 'IT', 'ES', 'EUR' => 'EUR',
            'JP', 'JPY' => 'JPY',
            'CA', 'CAD' => 'CAD',
            'AU', 'AUD' => 'AUD',
            'NZ', 'NZD' => 'NZD',
            'CH', 'CHF' => 'CHF',
            'CN', 'CNY', 'CNH' => 'CNY',
            default => strlen($country) === 3 ? $country : null,
        };
    }

    protected function dash(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            if (isset($value['value'])) {
                return $this->dash($value['value']);
            }

            $parts = [];

            foreach ($value as $part) {
                if (is_scalar($part) && trim((string) $part) !== '') {
                    $parts[] = (string) $part;
                }
            }

            return $parts !== [] ? implode(' · ', $parts) : '—';
        }

        if (is_int($value) || is_float($value) || is_string($value)) {
            $s = trim((string) $value);

            return $s !== '' ? $s : '—';
        }

        return '—';
    }

    protected function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        $s = trim((string) $value);

        return $s === '' || $s === '—';
    }
}
