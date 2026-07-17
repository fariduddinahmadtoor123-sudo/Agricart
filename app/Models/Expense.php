<?php

namespace App\Models;

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

    /** @var list<string> */
    protected $fillable = [
        'expense_number',
        'expense_category_id',
        'status',
        'name_en',
        'name_ur',
        'information',
        'created_by',
    ];

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
