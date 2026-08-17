<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseBillCycle extends Model
{
    public const VIA_PAID = 'paid';

    public const VIA_DONE = 'done';

    /** @var list<string> */
    protected $fillable = [
        'expense_id',
        'cycle_year',
        'cycle_month',
        'issue_on',
        'due_on',
        'completed_at',
        'completed_via',
        'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'cycle_year' => 'integer',
            'cycle_month' => 'integer',
            'issue_on' => 'date',
            'due_on' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Expense, $this>
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
