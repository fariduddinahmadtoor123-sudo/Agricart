<?php

namespace App\Services\FxLearn;

use Carbon\CarbonImmutable;

class SessionLiquidityService
{
    /**
     * Session windows in UTC minutes-from-midnight.
     *
     * @return list<array{id: string, label_en: string, label_ur: string, open: int, close: int, color: string}>
     */
    public function sessions(): array
    {
        return [
            [
                'id' => 'sydney',
                'label_en' => 'Sydney',
                'label_ur' => 'سڈنی',
                'open' => 22 * 60,
                'close' => 7 * 60,
                'color' => '#3b82f6',
            ],
            [
                'id' => 'tokyo',
                'label_en' => 'Tokyo',
                'label_ur' => 'ٹوکیو',
                'open' => 0 * 60,
                'close' => 9 * 60,
                'color' => '#14b8a6',
            ],
            [
                'id' => 'london',
                'label_en' => 'London',
                'label_ur' => 'لندن',
                'open' => 8 * 60,
                'close' => 17 * 60,
                'color' => '#ca8a04',
            ],
            [
                'id' => 'newyork',
                'label_en' => 'New York',
                'label_ur' => 'نیویارک',
                'open' => 13 * 60,
                'close' => 22 * 60,
                'color' => '#ea580c',
            ],
        ];
    }

    /**
     * @return array{
     *     now_pkt: string,
     *     now_utc_min: int,
     *     market_closed: bool,
     *     liquidity: string,
     *     liquidity_label_en: string,
     *     liquidity_label_ur: string,
     *     liquidity_detail_en: string,
     *     liquidity_detail_ur: string,
     *     overlap: array{kind: string, label_en: string, label_ur: string},
     *     sessions: list<array<string, mixed>>
     * }
     */
    public function snapshot(?CarbonImmutable $now = null): array
    {
        $tz = agricart_display_timezone();
        $now = ($now ?? CarbonImmutable::now('UTC'))->utc();
        $pkt = $now->timezone($tz);
        $utcMin = $now->hour * 60 + $now->minute;
        $marketClosed = $this->isFxWeekendClosed($now);

        $sessions = [];
        $openMap = [];

        foreach ($this->sessions() as $session) {
            $open = ! $marketClosed && $this->isOpen($utcMin, $session['open'], $session['close']);
            $openMap[$session['id']] = $open;
            $sessions[] = [
                ...$session,
                'open_now' => $open,
                'open_pkt' => $this->utcMinuteToPktLabel($session['open']),
                'close_pkt' => $this->utcMinuteToPktLabel($session['close']),
                'window_pkt' => $this->utcMinuteToPktLabel($session['open']).' – '.$this->utcMinuteToPktLabel($session['close']),
            ];
        }

        $openingBoost = ! $marketClosed && (
            $this->sessionOpeningHour($utcMin, 8 * 60)
            || $this->sessionOpeningHour($utcMin, 13 * 60)
        );

        if ($marketClosed) {
            $liq = 'closed';
        } elseif ((($openMap['london'] ?? false) && ($openMap['newyork'] ?? false)) || $openingBoost) {
            $liq = 'peak';
        } elseif (($openMap['london'] ?? false) || ($openMap['newyork'] ?? false)) {
            $liq = 'mod';
        } else {
            $liq = 'low';
        }

        [$liqEn, $liqUr, $detailEn, $detailUr] = match ($liq) {
            'peak' => [
                'High liquidity',
                'اعلیٰ لیکویڈیٹی',
                'London / New York overlap or open boost — stronger fills.',
                'لندن / نیویارک اوورلیپ یا اوپن بوسٹ — مضبوط فلز۔',
            ],
            'mod' => [
                'Moderate liquidity',
                'درمیانی لیکویڈیٹی',
                'Major session open — normal market pace.',
                'مین سیشن کھلا ہے — عام مارکیٹ رفتار۔',
            ],
            'closed' => [
                'Market closed',
                'مارکیٹ بند',
                'FX weekend close — wait for Sunday open (PKT).',
                'FX ویک اینڈ بند — اتوار اوپن (PKT) کا انتظار۔',
            ],
            default => [
                'Low liquidity',
                'کم لیکویڈیٹی',
                'Asia hours — thinner books, wider spreads possible.',
                'ایشیا اوقات — پتلی لیکویڈیٹی، وسیع اسپریڈ ممکن۔',
            ],
        };

        return [
            'now_pkt' => $pkt->format('H:i:s').' '.__('agricart.datetime.zone_abbr'),
            'now_utc_min' => $utcMin,
            'market_closed' => $marketClosed,
            'liquidity' => $liq,
            'liquidity_label_en' => $liqEn,
            'liquidity_label_ur' => $liqUr,
            'liquidity_detail_en' => $detailEn,
            'liquidity_detail_ur' => $detailUr,
            'overlap' => $this->londonNyOverlap($now, $openMap, $marketClosed),
            'sessions' => $sessions,
        ];
    }

    /**
     * @param  array<string, bool>  $openMap
     * @return array{kind: string, label_en: string, label_ur: string}
     */
    private function londonNyOverlap(CarbonImmutable $now, array $openMap, bool $marketClosed): array
    {
        if ($marketClosed) {
            return [
                'kind' => 'none',
                'label_en' => 'No London–NY overlap (weekend).',
                'label_ur' => 'لندن–نیویارک اوورلیپ نہیں (ویک اینڈ)۔',
            ];
        }

        $londonOpen = ($openMap['london'] ?? false);
        $nyOpen = ($openMap['newyork'] ?? false);

        if ($londonOpen && $nyOpen) {
            return [
                'kind' => 'active',
                'label_en' => 'London–New York overlap is live (peak window).',
                'label_ur' => 'لندن–نیویارک اوورلیپ لائیو ہے (پیک ونڈو)۔',
            ];
        }

        $utcMin = $now->hour * 60 + $now->minute;
        // Overlap window UTC 13:00–17:00
        $overlapStart = 13 * 60;
        $delta = ($overlapStart - $utcMin + 24 * 60) % (24 * 60);

        if ($delta > 0 && $delta <= 6 * 60 && ! $nyOpen) {
            $hours = intdiv($delta, 60);
            $mins = $delta % 60;

            return [
                'kind' => 'soon',
                'label_en' => 'Overlap in '.$hours.'h '.$mins.'m (PKT).',
                'label_ur' => 'اوورلیپ '.$hours.' گھنٹے '.$mins.' منٹ میں (PKT)۔',
            ];
        }

        return [
            'kind' => 'none',
            'label_en' => 'Outside London–NY overlap.',
            'label_ur' => 'لندن–نیویارک اوورلیپ سے باہر۔',
        ];
    }

    private function utcMinuteToPktLabel(int $utcMin): string
    {
        $h = intdiv($utcMin, 60) % 24;
        $m = $utcMin % 60;
        $pkt = CarbonImmutable::now('UTC')
            ->setTime($h, $m)
            ->timezone(agricart_display_timezone());

        return $pkt->format('H:i');
    }

    private function isOpen(int $utcMin, int $open, int $close): bool
    {
        if ($open < $close) {
            return $utcMin >= $open && $utcMin < $close;
        }

        return $utcMin >= $open || $utcMin < $close;
    }

    private function sessionOpeningHour(int $utcMin, int $openMin): bool
    {
        $elapsed = ($utcMin - $openMin + 24 * 60) % (24 * 60);

        return $elapsed < 60;
    }

    private function isFxWeekendClosed(CarbonImmutable $utcNow): bool
    {
        // FX: closes Friday 22:00 UTC, opens Sunday 22:00 UTC
        $dow = (int) $utcNow->dayOfWeekIso; // 1=Mon … 7=Sun
        $min = $utcNow->hour * 60 + $utcNow->minute;

        if ($dow === 6) {
            return true;
        }

        if ($dow === 5 && $min >= 22 * 60) {
            return true;
        }

        if ($dow === 7 && $min < 22 * 60) {
            return true;
        }

        return false;
    }
}
