<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FxLearnDemoAccount extends Model
{
    protected $table = 'fx_learn_demo_accounts';

    protected $fillable = [
        'user_id',
        'starting_balance',
        'balance',
        'phase1_pct',
        'phase2_pct',
        'phase3_pct',
        'max_day_loss_pct',
        'max_total_loss_pct',
        'max_trade_loss_pct',
        'active_phase',
        'day_anchor_equity',
        'day_anchor_date',
        'selected_symbol',
    ];

    protected function casts(): array
    {
        return [
            'starting_balance' => 'float',
            'balance' => 'float',
            'phase1_pct' => 'float',
            'phase2_pct' => 'float',
            'phase3_pct' => 'float',
            'max_day_loss_pct' => 'float',
            'max_total_loss_pct' => 'float',
            'max_trade_loss_pct' => 'float',
            'active_phase' => 'integer',
            'day_anchor_equity' => 'float',
            'day_anchor_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(FxLearnDemoTrade::class, 'user_id', 'user_id');
    }
}
