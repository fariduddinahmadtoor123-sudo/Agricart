<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolsHomeExpenseItem extends Model
{
    protected $table = 'tools_home_expense_items';

    protected $fillable = [
        'sheet_id',
        'user_id',
        'name',
        'expected_amount',
        'expense_date',
        'is_paid',
        'paid_amount',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'expected_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'expense_date' => 'date',
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(ToolsHomeExpenseSheet::class, 'sheet_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
