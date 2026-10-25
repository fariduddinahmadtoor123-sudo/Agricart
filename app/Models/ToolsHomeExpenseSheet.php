<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ToolsHomeExpenseSheet extends Model
{
    protected $table = 'tools_home_expense_sheets';

    protected $fillable = [
        'user_id',
        'title',
        'period_ym',
        'status',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ToolsHomeExpenseItem::class, 'sheet_id');
    }
}
