<?php

namespace App\Services\Reports;

use Carbon\CarbonImmutable;

final class ReportPeriod
{
    /**
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    public static function range(?string $periodFilter): array
    {
        $period = $periodFilter ?? '';

        if (str_starts_with($period, 'period:')) {
            $period = substr($period, strlen('period:'));
        }

        $today = CarbonImmutable::today();

        return match ($period) {
            'today' => [$today, $today],
            'week' => [$today->startOfWeek(), $today->endOfWeek()],
            'month' => [$today->startOfMonth(), $today->endOfMonth()],
            'quarter' => [$today->startOfQuarter(), $today->endOfQuarter()],
            'year' => [$today->startOfYear(), $today->endOfYear()],
            default => [null, null],
        };
    }
}
