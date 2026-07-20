<?php

namespace Tests\Feature\Backup;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class DailyBackupScheduleTest extends TestCase
{
    public function test_daily_database_backup_is_registered_on_the_scheduler(): void
    {
        config([
            'backup.schedule.enabled' => true,
            'backup.schedule.time' => '01:30',
            'backup.schedule.type' => 'database',
            'backup.schedule.weekly_full_enabled' => false,
        ]);

        $this->app->forgetInstance(Schedule::class);

        $this->artisan('schedule:list')
            ->expectsOutputToContain('backup:create --type=database --source=scheduled')
            ->assertSuccessful();
    }

    public function test_weekly_full_backup_is_registered_on_the_scheduler(): void
    {
        config([
            'backup.schedule.enabled' => true,
            'backup.schedule.weekly_full_enabled' => true,
            'backup.schedule.weekly_full_day' => 0,
            'backup.schedule.weekly_full_time' => '02:00',
        ]);

        $this->app->forgetInstance(Schedule::class);

        $this->artisan('schedule:list')
            ->expectsOutputToContain('backup:create --type=full --source=scheduled')
            ->assertSuccessful();
    }

    public function test_daily_backup_can_be_disabled_by_configuration(): void
    {
        config(['backup.schedule.enabled' => false]);
        $this->app->forgetInstance(Schedule::class);

        $this->artisan('schedule:list')
            ->doesntExpectOutputToContain('backup:create')
            ->assertSuccessful();
    }
}
