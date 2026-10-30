<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FxLearnDemoTrade extends Model
{
    protected $table = 'fx_learn_demo_trades';

    protected $fillable = [
        'user_id',
        'demo_account_id',
        'symbol',
        'label',
        'category',
        'side',
        'lots',
        'entry_price',
        'exit_price',
        'stop_loss',
        'take_profit',
        'contract_size',
        'digits',
        'pnl',
        'status',
        'close_reason',
        'opened_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'lots' => 'float',
            'entry_price' => 'float',
            'exit_price' => 'float',
            'stop_loss' => 'float',
            'take_profit' => 'float',
            'contract_size' => 'float',
            'digits' => 'integer',
            'pnl' => 'float',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function demoAccount(): BelongsTo
    {
        return $this->belongsTo(FxLearnDemoAccount::class, 'demo_account_id');
    }
}
