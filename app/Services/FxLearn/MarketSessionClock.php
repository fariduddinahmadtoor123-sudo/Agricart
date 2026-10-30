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
