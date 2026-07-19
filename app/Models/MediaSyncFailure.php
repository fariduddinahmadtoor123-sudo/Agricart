<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaSyncFailure extends Model
{
    protected $fillable = [
        'owner_type',
        'owner_id',
        'operation',
        'changed_fields',
        'old_paths',
        'last_error',
        'attempts',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_fields' => 'array',
            'old_paths' => 'array',
            'resolved_at' => 'datetime',
        ];
    }
}
