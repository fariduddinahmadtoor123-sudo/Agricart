<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\ReportPeriod;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportPeriodTest extends TestCase
{
    #[DataProvider('periodProvider')]
    public function test_range_returns_expected_bounds(string $input, ?string $startDate, ?string $endDate): void
    {
        CarbonImmutable::setTestNow('2026-07-15 12:00:00');

        try {
            [$start, $end] = ReportPeriod::range($input);

            if ($startDate === null) {
                $this->assertNull($start);
                $this->assertNull($end);

                return;
            }

            $this->assertSame($startDate, $start?->toDateString());
            $this->assertSame($endDate, $end?->toDateString());
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    /**
     * @return list<array{0: string, 1: ?string, 2: ?string}>
     */
    public static function periodProvider(): array
    {
        return [
            ['today', '2026-07-15', '2026-07-15'],
            ['period:today', '2026-07-15', '2026-07-15'],
            ['week', '2026-07-13', '2026-07-19'],
            ['month', '2026-07-01', '2026-07-31'],
            ['quarter', '2026-07-01', '2026-09-30'],
            ['year', '2026-01-01', '2026-12-31'],
            ['all', null, null],
            ['', null, null],
        ];
    }
}
