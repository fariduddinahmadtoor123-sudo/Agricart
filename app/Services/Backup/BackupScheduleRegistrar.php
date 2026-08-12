<?php

namespace App\Services\Backup;

use App\Models\BackupScheduleSetting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BackupScheduleRegistrar
{
    /**
     * @param  callable(Event): Event  $protect
     */
    public function register(Schedule $schedule, callable $protect): void
    {
        $settings = $this->resolveSettings();

        if (! $settings['enabled']) {
            return;
        }

        $type = $settings['type'];
        $time = $settings['time'];
        $frequency = $settings['frequency'];
        $weekday = $settings['weekday'];

        $event = $schedule->command('backup:create --type='.$type.' --source=scheduled')
            ->timezone(config('app.display_timezone'))
            ->withoutOverlapping($type === BackupScheduleSetting::TYPE_FULL ? 360 : 180)
            ->appendOutputTo(storage_path('logs/backup-schedule.log'));

        $this->applyFrequency($event, $frequency, $time, $weekday);
        $protect($event);

        if (
            $settings['database_daily_enabled']
            && $type !== BackupScheduleSetting::TYPE_DATABASE
        ) {
            $protect($schedule->command('backup:create --type=database --source=scheduled')
                ->dailyAt($settings['database_daily_time'])
                ->timezone(config('app.display_timezone'))
                ->withoutOverlapping(180)
                ->appendOutputTo(storage_path('logs/backup-schedule.log')));
        }
    }

    /**
     * @return array{
     *     enabled: bool,
     *     type: string,
     *     frequency: string,
     *     time: string,
     *     weekday: int,
     *     database_daily_enabled: bool,
     *     database_daily_time: string,
     *     summary: string
     * }
     */
    public function resolved(): array
    {
        $settings = $this->resolveSettings();
        $settings['summary'] = $this->summaryLine($settings);

        return $settings;
    }

    public function hostCronCommand(): string
    {
        $root = base_path();

        return '* * * * * cd '.$root.' && php artisan schedule:run >> /dev/null 2>&1';
    }

    public function schedulerLooksConfigured(): bool
    {
        return (bool) config('deployment.scheduler_configured', false);
    }

    /**
     * @return array{
     *     enabled: bool,
     *     type: string,
     *     frequency: string,
     *     time: string,
     *     weekday: int,
     *     database_daily_enabled: bool,
     *     database_daily_time: string
     * }
     */
    private function resolveSettings(): array
    {
        try {
            if (Schema::hasTable('backup_schedule_settings')) {
                $row = BackupScheduleSetting::current();

                return [
                    'enabled' => (bool) $row->enabled,
                    'type' => in_array($row->type, BackupScheduleSetting::TYPES, true)
                        ? $row->type
                        : BackupScheduleSetting::TYPE_FULL,
                    'frequency' => in_array($row->frequency, BackupScheduleSetting::FREQUENCIES, true)
                        ? $row->frequency
                        : BackupScheduleSetting::FREQUENCY_WEEKLY,
                    'time' => $this->normalizeTime((string) $row->time, '02:00'),
                    'weekday' => max(0, min(6, (int) $row->weekday)),
                    'database_daily_enabled' => (bool) $row->database_daily_enabled,
                    'database_daily_time' => $this->normalizeTime((string) $row->database_daily_time, '01:30'),
                ];
            }
        } catch (Throwable) {
            // Fall back to config during early boot / missing migration.
        }

        return BackupScheduleSetting::defaultsFromConfig();
    }

    private function applyFrequency(Event $event, string $frequency, string $time, int $weekday): void
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        match ($frequency) {
            BackupScheduleSetting::FREQUENCY_EVERY_2_DAYS => $event->cron(sprintf('%d %d */2 * *', $minute, $hour)),
            BackupScheduleSetting::FREQUENCY_EVERY_3_DAYS => $event->cron(sprintf('%d %d */3 * *', $minute, $hour)),
            BackupScheduleSetting::FREQUENCY_WEEKLY => $event->weeklyOn($weekday, $time),
            default => $event->dailyAt($time),
        };
    }

    /**
     * @param  array{
     *     enabled: bool,
     *     type: string,
     *     frequency: string,
     *     time: string,
     *     weekday: int,
     *     database_daily_enabled: bool,
     *     database_daily_time: string
     * }  $settings
     */
    private function summaryLine(array $settings): string
    {
        if (! $settings['enabled']) {
            return __('agricart.backups.schedule.summary_disabled');
        }

        $type = __('agricart.backups.types.'.$settings['type']);
        $weekdays = [
            0 => __('agricart.backups.schedule.sunday'),
            1 => __('agricart.backups.schedule.monday'),
            2 => __('agricart.backups.schedule.tuesday'),
            3 => __('agricart.backups.schedule.wednesday'),
            4 => __('agricart.backups.schedule.thursday'),
            5 => __('agricart.backups.schedule.friday'),
            6 => __('agricart.backups.schedule.saturday'),
        ];

        $primary = match ($settings['frequency']) {
            BackupScheduleSetting::FREQUENCY_EVERY_2_DAYS => __('agricart.backups.schedule.summary_every_2_days', [
                'type' => $type,
                'time' => $settings['time'],
            ]),
            BackupScheduleSetting::FREQUENCY_EVERY_3_DAYS => __('agricart.backups.schedule.summary_every_3_days', [
                'type' => $type,
                'time' => $settings['time'],
            ]),
            BackupScheduleSetting::FREQUENCY_WEEKLY => __('agricart.backups.schedule.summary_weekly', [
                'type' => $type,
                'day' => $weekdays[$settings['weekday']],
                'time' => $settings['time'],
            ]),
            default => __('agricart.backups.schedule.summary_daily', [
                'type' => $type,
                'time' => $settings['time'],
            ]),
        };

        if ($settings['database_daily_enabled'] && $settings['type'] !== BackupScheduleSetting::TYPE_DATABASE) {
            $primary .= ' · '.__('agricart.backups.schedule.summary_extra_database', [
                'time' => $settings['database_daily_time'],
            ]);
        }

        return $primary;
    }

    private function normalizeTime(string $time, string $fallback): string
    {
        if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $time, $matches) !== 1) {
            return $fallback;
        }

        return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
    }
}
