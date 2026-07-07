<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuantityControl extends Model
{
    protected $fillable = [
        'control_number',
        'name_en',
        'name_ur',
        'status',
        'notes',
        'settings',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tiers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(QuantityChannelTier::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeTiers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->tiers()->where('status', 'active');
    }
}
