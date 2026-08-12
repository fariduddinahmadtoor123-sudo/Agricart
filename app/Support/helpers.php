<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (! function_exists('agricart_display_timezone')) {
    function agricart_display_timezone(): string
    {
        return (string) config('app.display_timezone', 'Asia/Karachi');
    }
}

if (! function_exists('agricart_datetime')) {
    /**
     * Format a stored UTC instant for people in Pakistan time (PKT).
     */
    function agricart_datetime(null|CarbonInterface|string $value, string $format = 'Y-m-d H:i', bool $withZone = true): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $date = $value instanceof CarbonInterface
            ? $value->copy()
            : Carbon::parse($value, config('app.timezone', 'UTC'));

        $formatted = $date->timezone(agricart_display_timezone())->format($format);

        if (! $withZone) {
            return $formatted;
        }

        $zone = __('agricart.datetime.zone_abbr');

        return $formatted === '—' ? $formatted : $formatted.' '.$zone;
    }
}
