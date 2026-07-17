<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpensePayment extends Model
{
    public const WALLET_CASH = 'cash';

    public const WALLET_BANK = 'bank';

    /** @var list<string> */
    public const WALLETS = [
        self::WALLET_CASH,
        self::WALLET_BANK,
    ];

    /** @var list<string> */
    protected $fillable = [
        'payment_number',
        'expense_id',
        'amount',
        'payment_date',
        'payment_time',
        'source_wallet',
        'bank_account_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
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
     * @return BelongsTo<BankAccount, $this>
     */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
