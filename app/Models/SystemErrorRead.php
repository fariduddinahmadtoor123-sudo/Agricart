<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemErrorRead extends Model
{
    protected $fillable = [
        'system_error_id',
        'user_id',
        'occurrences_seen',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'occurrences_seen' => 'integer',
            'read_at' => 'datetime',
        ];
    }

    public function error(): BelongsTo
    {
        return $this->belongsTo(SystemError::class, 'system_error_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
