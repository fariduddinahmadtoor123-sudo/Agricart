<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FxLearnAccountType extends Model
{
    protected $table = 'fx_learn_account_types';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'starting_balance',
        'phase1_pct',
        'phase2_pct',
        'phase3_pct',
        'max_day_loss_pct',
        'max_total_loss_pct',
        'max_trade_loss_pct',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'starting_balance' => 'float',
            'phase1_pct' => 'float',
            'phase2_pct' => 'float',
            'phase3_pct' => 'float',
            'max_day_loss_pct' => 'float',
            'max_total_loss_pct' => 'float',
            'max_trade_loss_pct' => 'float',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $type): void {
            if (! filled($type->slug)) {
                $type->slug = Str::slug((string) $type->name);
            }
        });
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
