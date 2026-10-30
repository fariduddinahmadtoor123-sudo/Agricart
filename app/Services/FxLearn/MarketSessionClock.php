<?php

namespace App\Services\FxLearn;

use Carbon\CarbonImmutable;

class MarketSessionClock
{
    /**
     * Major FX sessions in open order: Sydney → Tokyo → London → New York.
     *
     * @return list<array{id: string, label: string, flag: string, tz: string, open_start: int, open_end: int, side: 'left'|'right'}>
     */
    public static function sessions(): array
    {
        return [
            [
                'id' => 'sydney',
                'label' => __('agricart.pages.fx-learn.clock_sydney'),
                'flag' => '🇦🇺',
                'tz' => 'Australia/Sydney',
                'open_start' => 9,
                'open_end' => 18,
                'side' => 'left',
            ],
            [
                'id' => 'tokyo',
                'label' => __('agricart.pages.fx-learn.clock_tokyo'),
                'flag' => '🇯🇵',
                'tz' => 'Asia/Tokyo',
                'open_start' => 9,
                'open_end' => 18,
                'side' => 'left',
            ],
            [
                'id' => 'london',
                'label' => __('agricart.pages.fx-learn.clock_london'),
                'flag' => '🇬🇧',
                'tz' => 'Europe/London',
                'open_start' => 8,
                'open_end' => 17,
                'side' => 'right',
            ],
            [
                'id' => 'new_york',
                'label' => __('agricart.pages.fx-learn.clock_new_york'),
                'flag' => '🇺🇸',
                'tz' => 'America/New_York',
                'open_start' => 8,
                'open_end' => 17,
                'side' => 'right',
            ],
        ];
    }

    /**
     * @param  array{tz: string, open_start: int, open_end: int}  $session
     */
    public static function isOpen(array $session, CarbonImmutable $nowUtc): bool
    {
        $local = $nowUtc->timezone($session['tz']);

        if (! $local->isWeekday()) {
            return false;
        }

        $hour = (int) $local->format('G');

        return $hour >= (int) $session['open_start'] && $hour < (int) $session['open_end'];
    }

    public static function flagForCcy(string $ccy): string
    {
        return match (strtoupper(trim($ccy))) {
            'USD' => '🇺🇸',
            'EUR' => '🇪🇺',
            'GBP' => '🇬🇧',
            'JPY' => '🇯🇵',
            'AUD' => '🇦🇺',
            'CAD' => '🇨🇦',
            'CHF' => '🇨🇭',
            'NZD' => '🇳🇿',
            'CNY', 'CNH' => '🇨🇳',
            'PKR' => '🇵🇰',
            default => '🏳️',
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function deckSnapshot(): array
    {
        $now = CarbonImmutable::now('UTC');
        $out = [];

        foreach (self::sessions() as $session) {
            $local = $now->timezone($session['tz']);
            $open = self::isOpen($session, $now);
            $nextOpen = self::nextOpenAt($session, $now);
            $nextClose = self::nextCloseAt($session, $now);

            $out[] = [
                'id' => $session['id'],
                'label' => $session['label'],
                'flag' => $session['flag'],
                'tz' => $session['tz'],
                'time' => $local->format('H:i:s'),
                'hm' => $local->format('H:i'),
                'sec' => $local->format('s'),
                'open' => $open,
                'pill' => $open
                    ? __('agricart.pages.fx-learn.session_open')
                    : __('agricart.pages.fx-learn.session_closed'),
                'side' => $session['side'],
                'open_start' => (int) $session['open_start'],
                'open_end' => (int) $session['open_end'],
                'open_boundary_ms' => (int) $nextOpen->valueOf(),
                'close_boundary_ms' => (int) $nextClose->valueOf(),
                // Keep legacy keys for any old JS callers.
                'boundary_ms' => (int) ($open ? $nextClose : $nextOpen)->valueOf(),
                'boundary_kind' => $open ? 'close' : 'open',
                'open_until_label' => $open
                    ? __('agricart.pages.fx-learn.session_open')
                    : self::formatUntil($nextOpen, $now, 'open'),
                'close_until_label' => self::formatUntil($nextClose, $now, 'close'),
                'until_label' => $open
                    ? self::formatUntil($nextClose, $now, 'close')
                    : self::formatUntil($nextOpen, $now, 'open'),
                'hours_label' => '',
            ];
        }

        return $out;
    }

    /**
     * Compact desk strip: per-session fill % + liquidity / overlap summary.
     *
     * @return array{
     *   open_count: int,
     *   overlap: bool,
     *   liquidity: string,
     *   liquidity_label: string,
     *   summary: string,
     *   sessions: list<array{
     *     id: string,
     *     short: string,
     *     label: string,
     *     open: bool,
     *     progress: float,
     *     start_ms: int,
     *     close_ms: int
     *   }>
     * }
     */
    public static function liquidityStrip(): array
    {
        $now = CarbonImmutable::now('UTC');
        $sessions = [];
        $openLabels = [];

        foreach (self::sessions() as $session) {
            $open = self::isOpen($session, $now);
            $nextOpen = self::nextOpenAt($session, $now);
            $nextClose = self::nextCloseAt($session, $now);
            $local = $now->timezone($session['tz']);
            $windowStart = $local->setTime((int) $session['open_start'], 0, 0);
            $windowEnd = $local->setTime((int) $session['open_end'], 0, 0);
            $progress = 0.0;

            if ($open) {
                $total = max(1, $windowEnd->getTimestamp() - $windowStart->getTimestamp());
                $elapsed = max(0, $local->getTimestamp() - $windowStart->getTimestamp());
                $progress = min(100, round(($elapsed / $total) * 100, 1));
                $openLabels[] = $session['label'];
                $startMs = (int) $windowStart->utc()->valueOf();
                $closeMs = (int) $windowEnd->utc()->valueOf();
            } else {
                $startMs = (int) $nextOpen->valueOf();
                $closeMs = (int) $nextClose->valueOf();
            }

            $sessions[] = [
                'id' => $session['id'],
                'short' => self::shortCode($session['id']),
                'label' => $session['label'],
                'tz' => (string) $session['tz'],
                'open' => $open,
                'progress' => $progress,
                'start_ms' => $startMs,
                'close_ms' => $closeMs,
            ];
        }

        $openCount = count($openLabels);
        $openIds = collect($sessions)->where('open', true)->pluck('id')->all();
        $londonNy = in_array('london', $openIds, true) && in_array('new_york', $openIds, true);
        $overlap = $openCount >= 2;

        // Typical FX liquidity: London∩NY is the peak window.
        $liquidity = match (true) {
            $londonNy || $openCount >= 3 => 'very_high',
            $openCount === 2 => 'high',
            $openCount === 1 => 'medium',
            default => 'off',
        };

        return [
            'open_count' => $openCount,
            'overlap' => $overlap,
            'london_ny' => $londonNy,
            'liquidity' => $liquidity,
            'liquidity_label' => match ($liquidity) {
                'very_high' => __('agricart.pages.fx-learn.term_liq_very_high'),
                'high' => __('agricart.pages.fx-learn.term_liq_high'),
                'medium' => __('agricart.pages.fx-learn.term_liq_medium'),
                default => __('agricart.pages.fx-learn.term_liq_off'),
            },
            'summary' => $openLabels !== []
                ? implode(' · ', $openLabels)
                : __('agricart.pages.fx-learn.term_sessions_none'),
            'sessions' => $sessions,
        ];
    }

    protected static function shortCode(string $id): string
    {
        return match ($id) {
            'sydney' => 'SYD',
            'tokyo' => 'TOK',
            'london' => 'LON',
            'new_york' => 'NY',
            default => strtoupper(substr($id, 0, 3)),
        };
    }

    /**
     * @param  array{tz: string, open_start: int, open_end: int}  $session
     */
    protected static function nextOpenAt(array $session, CarbonImmutable $nowUtc): CarbonImmutable
    {
        $tz = $session['tz'];
        $start = (int) $session['open_start'];
        $local = $nowUtc->timezone($tz);

        if (self::isOpen($session, $nowUtc)) {
            // Already open: next open is next weekday session start.
            $next = $local->addDay()->setTime($start, 0, 0);

            while (! $next->isWeekday()) {
                $next = $next->addDay()->setTime($start, 0, 0);
            }

            return $next->utc();
        }

        $candidate = $local->setTime($start, 0, 0);

        if ($local->isWeekday() && $local->lt($candidate)) {
            return $candidate->utc();
        }

        $next = $local->addDay()->setTime($start, 0, 0);

        while (! $next->isWeekday()) {
            $next = $next->addDay()->setTime($start, 0, 0);
        }

        return $next->utc();
    }

    /**
     * @param  array{tz: string, open_start: int, open_end: int}  $session
     */
    protected static function nextCloseAt(array $session, CarbonImmutable $nowUtc): CarbonImmutable
    {
        $tz = $session['tz'];
        $end = (int) $session['open_end'];
        $local = $nowUtc->timezone($tz);

        if (self::isOpen($session, $nowUtc)) {
            $at = $local->setTime($end, 0, 0);

            if ($at->lte($local)) {
                $at = $at->addDay();
            }

            return $at->utc();
        }

        // Closed: close time of the upcoming session (same day as next open).
        $nextOpenLocal = self::nextOpenAt($session, $nowUtc)->timezone($tz);

        return $nextOpenLocal->setTime($end, 0, 0)->utc();
    }

    protected static function formatUntil(CarbonImmutable $boundaryUtc, CarbonImmutable $nowUtc, string $kind): string
    {
        $seconds = max(0, $boundaryUtc->getTimestamp() - $nowUtc->getTimestamp());
        $hours = intdiv($seconds, 3600);
        $mins = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            $span = __('agricart.pages.fx-learn.session_in_hm', [
                'h' => $hours,
                'm' => $mins,
                'hh' => $hours.'h',
                'mm' => $mins.'m',
            ]);
        } else {
            $span = __('agricart.pages.fx-learn.session_in_m', [
                'm' => max(1, $mins),
                'mm' => max(1, $mins).'m',
            ]);
        }

        return $kind === 'close'
            ? __('agricart.pages.fx-learn.session_closes_in', ['span' => $span])
            : __('agricart.pages.fx-learn.session_opens_in', ['span' => $span]);
    }
}
