<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalVaultQuota extends Model
{
    protected $fillable = [
        'user_id',
        'quota_bytes',
    ];

    protected function casts(): array
    {
        return [
            'quota_bytes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
