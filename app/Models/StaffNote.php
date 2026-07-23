<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffNote extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_DONE = 'done';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'body',
        'created_by',
        'assigned_to',
        'due_on',
        'status',
        'completed_at',
        'completed_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }
}
