<?php

namespace App\Console\Commands;

use App\Services\Analytics\AnalyticsRollupService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class RollupVisitorAnalyticsCommand extends Command
{
    protected $signature = 'analytics:rollup
        {--date= : Aggregate one day in YYYY-MM-DD format}
        {--month= : Aggregate one month in YYYY-MM format}
        {--monthly : Aggregate the previous calendar month}';

    protected $description = 'Build compact visitor analytics daily or monthly rollups';

    public function handle(AnalyticsRollupService $rollups): int
    {
        $timezone = (string) config('app.timezone');

        if ($this->option('monthly') || filled($this->option('month'))) {
            $month = $this->option('monthly')
                ? CarbonImmutable::now($timezone)->subMonthNoOverflow()->startOfMonth()
                : CarbonImmutable::createFromFormat('!Y-m', (string) $this->option('month'), $timezone);

            if ($month === false) {
                $this->components->error('Month must use YYYY-MM format.');

                return self::FAILURE;
            }

            $rollups->rollupMonth($month);
            $this->components->info("Visitor analytics month {$month->format('Y-m')} aggregated.");

            return self::SUCCESS;
        }

        $date = filled($this->option('date'))
            ? CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->option('date'), $timezone)
            : CarbonImmutable::now($timezone)->subDay()->startOfDay();

        if ($date === false) {
            $this->components->error('Date must use YYYY-MM-DD format.');

            return self::FAILURE;
        }

        $rollups->rollupDay($date);
        $this->components->info("Visitor analytics day {$date->format('Y-m-d')} aggregated.");

        return self::SUCCESS;
    }
}
