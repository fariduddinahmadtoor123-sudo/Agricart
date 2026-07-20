<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupScheduleSetting extends Model
{
    public const TYPE_FULL = 'full';

    public const TYPE_DATABASE = 'database';

    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_EVERY_2_DAYS = 'every_2_days';

    public const FREQUENCY_EVERY_3_DAYS = 'every_3_days';

    public const FREQUENCY_WEEKLY = 'weekly';

    /** @var list<string> */
    public const TYPES = [self::TYPE_FULL, self::TYPE_DATABASE];

    /** @var list<string> */
    public const FREQUENCIES = [
        self::FREQUENCY_DAILY,
        self::FREQUENCY_EVERY_2_DAYS,
        self::FREQUENCY_EVERY_3_DAYS,
        self::FREQUENCY_WEEKLY,
    ];

    protected $fillable = [
        'enabled',
        'type',
        'frequency',
        'time',
        'weekday',
        'database_daily_enabled',
        'database_daily_time',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'weekday' => 'integer',
            'database_daily_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        $setting = static::query()->first();

        if ($setting !== null) {
            return $setting;
        }

        return static::query()->create(self::defaultsFromConfig());
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
    public static function defaultsFromConfig(): array
    {
        $type = (string) config('backup.schedule.primary_type', self::TYPE_FULL);
        if (! in_array($type, self::TYPES, true)) {
            $type = self::TYPE_FULL;
        }

        $frequency = (string) config('backup.schedule.frequency', self::FREQUENCY_WEEKLY);
        if (! in_array($frequency, self::FREQUENCIES, true)) {
            $frequency = self::FREQUENCY_WEEKLY;
        }

        return [
            'enabled' => (bool) config('backup.schedule.enabled', true),
            'type' => $type,
            'frequency' => $frequency,
            'time' => (string) config('backup.schedule.time', '02:00'),
            'weekday' => max(0, min(6, (int) config('backup.schedule.weekday', 0))),
            'database_daily_enabled' => (bool) config('backup.schedule.database_daily_enabled', false),
            'database_daily_time' => (string) config('backup.schedule.database_daily_time', '01:30'),
        ];
    }
}
