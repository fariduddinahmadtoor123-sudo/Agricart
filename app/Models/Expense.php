<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    public const DUE_UPCOMING = 'upcoming';

    public const DUE_ISSUED = 'issued';

    public const DUE_SOON = 'due_soon';

    public const DUE_OVERDUE = 'overdue';

    public const DUE_PAID = 'paid';

    /** @var list<string> */
    protected $fillable = [
        'expense_number',
        'expense_category_id',
        'status',
        'name_en',
        'name_ur',
        'information',
        'issue_day',
        'due_day',
        'detail_1',
        'detail_2',
        'detail_3',
        'detail_4',
        'estimated_cost',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_day' => 'integer',
            'due_day' => 'integer',
            'estimated_cost' => 'decimal:2',
        ];
    }

    public function displayName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale === 'ur' && filled($this->name_ur)) {
            return (string) $this->name_ur;
        }

        return (string) ($this->name_en ?: $this->name_ur ?: $this->expense_number);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function hasMonthlySchedule(): bool
    {
        return $this->issue_day !== null;
    }

    public function dueStatus(?CarbonInterface $today = null): ?string
    {
        return app(\App\Services\Finance\ExpenseScheduleService::class)->dueStatus($this, $today);
    }

    /**
     * @param  Builder<Expense>  $query
     * @return Builder<Expense>
     */
    public function scopeAwaitingPayment(Builder $query, ?CarbonInterface $today = null): Builder
    {
        $ids = app(\App\Services\Finance\ExpenseScheduleService::class)->awaitingExpenseIds($today);

        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('id', $ids);
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ExpensePayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(ExpensePayment::class);
    }

    /**
     * @return HasMany<ExpenseBillCycle, $this>
     */
    public function billCycles(): HasMany
    {
        return $this->hasMany(ExpenseBillCycle::class);
    }

    /**
     * @return HasMany<CashBookTransaction, $this>
     */
    public function cashBookTransactions(): HasMany
    {
        return $this->hasMany(CashBookTransaction::class);
    }

    /**
     * @return HasMany<BankAccountTransaction, $this>
     */
    public function bankAccountTransactions(): HasMany
    {
        return $this->hasMany(BankAccountTransaction::class);
    }
}
