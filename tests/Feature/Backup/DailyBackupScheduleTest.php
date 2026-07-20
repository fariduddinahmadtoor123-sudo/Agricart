<?php

namespace Tests\Feature\Backup;

use App\Models\BackupScheduleSetting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyBackupScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_configured_full_weekly_backup_is_registered(): void
    {
        BackupScheduleSetting::query()->delete();
        BackupScheduleSetting::query()->create([
            'enabled' => true,
            'type' => 'full',
            'frequency' => 'weekly',
            'time' => '02:00',
            'weekday' => 0,
            'database_daily_enabled' => false,
            'database_daily_time' => '01:30',
        ]);

        $this->app->forgetInstance(Schedule::class);

        $this->artisan('schedule:list')
            ->expectsOutputToContain('backup:create --type=full --source=scheduled')
            ->assertSuccessful();
    }

    public function test_daily_database_backup_is_registered_when_selected(): void
    {
        BackupScheduleSetting::query()->delete();
        BackupScheduleSetting::query()->create([
            'enabled' => true,
            'type' => 'database',
            'frequency' => 'daily',
            'time' => '01:30',
            'weekday' => 0,
            'database_daily_enabled' => false,
            'database_daily_time' => '01:30',
        ]);

        $this->app->forgetInstance(Schedule::class);

        $this->artisan('schedule:list')
            ->expectsOutputToContain('backup:create --type=database --source=scheduled')
            ->assertSuccessful();
    }

    public function test_optional_extra_database_daily_is_registered_with_full_schedule(): void
    {
        BackupScheduleSetting::query()->delete();
        BackupScheduleSetting::query()->create([
            'enabled' => true,
            'type' => 'full',
            'frequency' => 'every_2_days',
            'time' => '02:00',
            'weekday' => 0,
            'database_daily_enabled' => true,
            'database_daily_time' => '01:15',
        ]);

        $this->app->forgetInstance(Schedule::class);

        $this->artisan('schedule:list')
            ->expectsOutputToContain('backup:create --type=full --source=scheduled')
            ->expectsOutputToContain('backup:create --type=database --source=scheduled')
            ->assertSuccessful();
    }

    public function test_daily_backup_can_be_disabled_by_configuration(): void
    {
        BackupScheduleSetting::query()->delete();
        BackupScheduleSetting::query()->create([
            'enabled' => false,
            'type' => 'full',
            'frequency' => 'weekly',
            'time' => '02:00',
            'weekday' => 0,
            'database_daily_enabled' => false,
            'database_daily_time' => '01:30',
        ]);

        $this->app->forgetInstance(Schedule::class);

        $this->artisan('schedule:list')
            ->doesntExpectOutputToContain('backup:create')
            ->assertSuccessful();
    }
}
