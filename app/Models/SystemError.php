<?php

namespace App\Models;

use App\Services\Notifications\TopbarNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemError extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_RESOLVED = 'resolved';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_ERROR = 'error';

    public const SEVERITY_CRITICAL = 'critical';

    /** @var list<string> */
    protected $fillable = [
        'reference',
        'fingerprint',
        'source',
        'severity',
        'status',
        'exception_class',
        'message',
        'file',
        'line',
        'trace',
        'request_method',
        'request_path',
        'route_name',
        'http_status',
        'user_id',
        'environment',
        'release',
        'context',
        'occurrences',
        'first_seen_at',
        'last_seen_at',
        'resolved_at',
        'resolved_by_id',
        'resolution_type',
        'resolution_note',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'resolved_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => TopbarNotificationService::invalidateSystemErrors());
        static::deleted(fn () => TopbarNotificationService::invalidateSystemErrors());
    }

    public function increment($column, $amount = 1, array $extra = []): int
    {
        $result = parent::increment($column, $amount, $extra);
        TopbarNotificationService::invalidateSystemErrors();

        return $result;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(SystemErrorRead::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
