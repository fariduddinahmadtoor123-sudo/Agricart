<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundTransfer extends Model
{
    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_TRANSFER = 'transfer';

    public const TYPE_VENDOR_PAYMENT = 'vendor_payment';

    public const TYPE_EXPENSE_PAYMENT = 'expense_payment';

    public const WALLET_CASH = 'cash';

    public const WALLET_BANK = 'bank';

    /** @var list<string> */
    protected $fillable = [
        'transfer_number',
        'transaction_type',
        'source_wallet',
        'source_bank_account_id',
        'destination_bank_account_id',
        'destination_supplier_id',
        'destination_purchaser_id',
        'amount',
        'transaction_date',
        'transaction_time',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<BankAccount, $this>
     */
    public function sourceBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'source_bank_account_id');
    }

    /**
     * @return BelongsTo<BankAccount, $this>
     */
    public function destinationBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'destination_bank_account_id');
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function destinationSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'destination_supplier_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function destinationPurchaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'destination_purchaser_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<BankAccountTransaction, $this>
     */
    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankAccountTransaction::class);
    }

    /**
     * @return HasMany<CashBookTransaction, $this>
     */
    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashBookTransaction::class);
    }
}
