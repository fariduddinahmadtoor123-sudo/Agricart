<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonalVaultSecret extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'site_url',
        'username',
        'password',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'notes' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
