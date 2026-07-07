<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecretCodingProfile extends Model
{
    protected $fillable = [
        'profile_number',
        'name_en',
        'name_ur',
        'status',
        'notes',
        'digit_slots',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'digit_slots' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
