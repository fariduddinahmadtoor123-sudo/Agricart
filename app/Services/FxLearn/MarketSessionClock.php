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
     * @return list<array{
     *     id: string,
     *     label: string,
     *     flag: string,
     *     tz: string,
     *     time: string,
     *     hm: string,
     *     sec: string,
     *     open: bool,
     *     pill: string,
     *     side: 'left'|'right',
     *     hours_label: string,
     *     boundary_ms: int,
     *     boundary_kind: 'open'|'close',
     *     until_label: string
     * }>
     */
    public static function deckSnapshot(): array
    {
        $now = CarbonImmutable::now('UTC');
        $out = [];

        foreach (self::sessions() as $session) {
            $local = $now->timezone($session['tz']);
            $open = self::isOpen($session, $now);
            $boundary = self::nextBoundary($session, $now);
            $kind = $open ? 'close' : 'open';
            $untilLabel = self::formatUntil($boundary['at'], $now, $kind);
            $hoursLabel = $open
                ? __('agricart.pages.fx-learn.session_closes_at', ['time' => $boundary['local_hm']])
                : __('agricart.pages.fx-learn.session_opens_at', ['time' => $boundary['local_hm']]);

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
                'hours_label' => $hoursLabel,
                'boundary_ms' => (int) $boundary['at']->valueOf(),
                'boundary_kind' => $kind,
                'until_label' => $untilLabel,
                'open_start' => (int) $session['open_start'],
                'open_end' => (int) $session['open_end'],
            ];
        }

        return $out;
    }

    /**
     * @param  array{tz: string, open_start: int, open_end: int}  $session
     * @return array{at: CarbonImmutable, local_hm: string}
     */
    protected static function nextBoundary(array $session, CarbonImmutable $nowUtc): array
    {
        $tz = $session['tz'];
        $start = (int) $session['open_start'];
        $end = (int) $session['open_end'];
        $local = $nowUtc->timezone($tz);

        if (self::isOpen($session, $nowUtc)) {
            $at = $local->setTime($end, 0, 0);

            if ($at->lte($local)) {
                $at = $at->addDay();
            }

            return [
                'at' => $at->utc(),
                'local_hm' => $at->format('H:i'),
            ];
        }

        // Next open: today if before open on weekday, else next weekday open.
        $candidate = $local->setTime($start, 0, 0);

        if ($local->isWeekday() && $local->lt($candidate)) {
            return [
                'at' => $candidate->utc(),
                'local_hm' => $candidate->format('H:i'),
            ];
        }

        $next = $local->addDay()->setTime($start, 0, 0);

        while (! $next->isWeekday()) {
            $next = $next->addDay()->setTime($start, 0, 0);
        }

        return [
            'at' => $next->utc(),
            'local_hm' => $next->format('H:i'),
        ];
    }

    protected static function formatUntil(CarbonImmutable $boundaryUtc, CarbonImmutable $nowUtc, string $kind): string
    {
        $seconds = max(0, $boundaryUtc->getTimestamp() - $nowUtc->getTimestamp());
        $hours = intdiv($seconds, 3600);
        $mins = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            $span = __('agricart.pages.fx-learn.session_in_hm', ['h' => $hours, 'm' => $mins]);
        } else {
            $span = __('agricart.pages.fx-learn.session_in_m', ['m' => max(1, $mins)]);
        }

        return $kind === 'close'
            ? __('agricart.pages.fx-learn.session_closes_in', ['span' => $span])
            : __('agricart.pages.fx-learn.session_opens_in', ['span' => $span]);
    }
}
