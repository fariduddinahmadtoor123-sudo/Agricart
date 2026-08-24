<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankAccountTransaction extends Model
{
    public const TYPE_OPENING = 'opening';

    public const TYPE_SALE_RECEIPT = 'sale_receipt';

    public const TYPE_SALE_REFUND = 'sale_refund';

    public const TYPE_TRANSFER_IN = 'transfer_in';

    public const TYPE_TRANSFER_OUT = 'transfer_out';

    public const TYPE_SUPPLIER_PAYMENT = 'supplier_payment';

    public const TYPE_CUSTOMER_RECEIPT = 'customer_receipt';

    public const TYPE_CASH_DEPOSIT = 'cash_deposit';

    public const TYPE_CASH_WITHDRAWAL = 'cash_withdrawal';

    public const TYPE_OWNER_DEPOSIT = 'owner_deposit';

    public const TYPE_OWNER_WITHDRAWAL = 'owner_withdrawal';

    public const TYPE_EXPENSE_PAYMENT = 'expense_payment';

    public const DIRECTION_CREDIT = 'credit';

    public const DIRECTION_DEBIT = 'debit';

    /** @var list<string> */
    protected $fillable = [
        'bank_account_id',
        'type',
        'direction',
        'amount',
        'transaction_date',
        'counterparty_bank_account_id',
        'supplier_id',
        'customer_id',
        'sale_id',
        'sale_return_id',
        'online_sale_order_id',
        'fund_transfer_id',
        'expense_id',
        'expense_payment_id',
        'transfer_group',
        'source_key',
        'description',
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
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * @return BelongsTo<BankAccount, $this>
     */
    public function counterpartyBankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'counterparty_bank_account_id');
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Sale, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * @return BelongsTo<SaleReturn, $this>
     */
    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SaleReturn::class);
    }

    /**
     * @return BelongsTo<OnlineSaleOrder, $this>
     */
    public function onlineSaleOrder(): BelongsTo
    {
        return $this->belongsTo(OnlineSaleOrder::class);
    }

    /**
     * @return BelongsTo<FundTransfer, $this>
     */
    public function fundTransfer(): BelongsTo
    {
        return $this->belongsTo(FundTransfer::class);
    }

    /**
     * @return BelongsTo<Expense, $this>
     */
    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    /**
     * @return BelongsTo<ExpensePayment, $this>
     */
    public function expensePayment(): BelongsTo
    {
        return $this->belongsTo(ExpensePayment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
