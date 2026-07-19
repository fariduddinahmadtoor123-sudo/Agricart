<?php

namespace App\Services\Finance;

use App\Models\BankAccount;
use App\Models\BankAccountTransaction;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Supplier;
use App\Models\User;
use App\Support\PakistaniBanks;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BankAccountService
{
    public function formatMoney(float $amount): string
    {
        return number_format($amount, 2).' PKR';
    }

    public function balance(BankAccount $account): float
    {
        $opening = (float) $account->opening_balance;
        $credits = (float) BankAccountTransaction::query()
            ->where('bank_account_id', $account->id)
            ->where('direction', BankAccountTransaction::DIRECTION_CREDIT)
            ->where('type', '!=', BankAccountTransaction::TYPE_OPENING)
            ->sum('amount');
        $debits = (float) BankAccountTransaction::query()
            ->where('bank_account_id', $account->id)
            ->where('direction', BankAccountTransaction::DIRECTION_DEBIT)
            ->sum('amount');

        return round($opening + $credits - $debits, 2);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): BankAccount
    {
        $actor ??= auth()->user();
        $data = $this->prepareAccountData($data);
        $validated = $this->validateAccount($data);
        $opening = round((float) ($validated['opening_balance'] ?? 0), 2);
        $validated['opening_balance'] = $opening;

        $accountNumber = trim((string) ($validated['account_number'] ?? ''));
        $lockKey = 'finance-bank-create:'.($actor?->id ?? 'guest').':'.md5($accountNumber.'|'.($validated['name_en'] ?? ''));

        return Cache::lock($lockKey, 15)->block(10, function () use ($validated, $opening, $actor, $accountNumber): BankAccount {
            if ($accountNumber !== '') {
                $exists = BankAccount::query()->where('account_number', $accountNumber)->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'account_identifier' => [__('agricart.finance.banks.messages.duplicate_account_number')],
                    ]);
                }
            }

            return DB::transaction(function () use ($validated, $opening, $actor): BankAccount {
                $validated['created_by'] = $actor?->id;
                $validated = $this->applyBankKeyLabels($validated);

                $account = BankAccount::query()->create($validated);

                if (! filled($account->account_code)) {
                    $account->update(['account_code' => $this->nextAccountCode($account->id)]);
                }

                if ($opening > 0) {
                    $this->postTransaction(
                        $account,
                        BankAccountTransaction::TYPE_OPENING,
                        BankAccountTransaction::DIRECTION_CREDIT,
                        $opening,
                        now()->toDateString(),
                        __('agricart.finance.banks.ledger.opening'),
                        null,
                        $actor,
                    );
                }

                return $account->fresh();
            });
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BankAccount $account, array $data): BankAccount
    {
        $data = $this->prepareAccountData($data);
        $validated = $this->validateAccount($data, $account->id);
        unset($validated['opening_balance']);
        $validated = $this->applyBankKeyLabels($validated);
        $account->update($validated);

        return $account->fresh();
    }

    public function transferToBank(
        BankAccount $from,
        BankAccount $to,
        float $amount,
        string $transactionDate,
        ?string $notes = null,
        ?User $actor = null,
        ?int $fundTransferId = null,
    ): void {
        if ($from->id === $to->id) {
            throw ValidationException::withMessages([
                'counterparty_bank_account_id' => [__('agricart.finance.banks.messages.same_account')],
            ]);
        }

        $group = (string) Str::uuid();
        $description = __('agricart.finance.banks.ledger.transfer_to', ['name' => $to->displayName()]);

        DB::transaction(function () use ($from, $to, $amount, $transactionDate, $notes, $actor, $group, $description, $fundTransferId): void {
            $locked = BankAccount::query()
                ->whereIn('id', [$from->id, $to->id])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $lockedFrom = $locked->get($from->id);
            $lockedTo = $locked->get($to->id);

            if (! $lockedFrom instanceof BankAccount || ! $lockedTo instanceof BankAccount) {
                throw ValidationException::withMessages([
                    'source_bank_account_id' => ['The selected bank account is unavailable.'],
                ]);
            }

            $this->assertSufficientBalance($lockedFrom, $amount);
            $this->postTransaction(
                $lockedFrom,
                BankAccountTransaction::TYPE_TRANSFER_OUT,
                BankAccountTransaction::DIRECTION_DEBIT,
                $amount,
                $transactionDate,
                $description,
                [
                    'counterparty_bank_account_id' => $to->id,
                    'transfer_group' => $group,
                    'notes' => $notes,
                    'fund_transfer_id' => $fundTransferId,
                ],
                $actor,
            );

            $this->postTransaction(
                $lockedTo,
                BankAccountTransaction::TYPE_TRANSFER_IN,
                BankAccountTransaction::DIRECTION_CREDIT,
                $amount,
                $transactionDate,
                __('agricart.finance.banks.ledger.transfer_from', ['name' => $from->displayName()]),
                [
                    'counterparty_bank_account_id' => $from->id,
                    'transfer_group' => $group,
                    'notes' => $notes,
                    'fund_transfer_id' => $fundTransferId,
                ],
                $actor,
            );
        });
    }

    public function recordCashDeposit(
        BankAccount $account,
        float $amount,
        string $transactionDate,
        ?string $description,
        ?string $notes = null,
        ?int $fundTransferId = null,
        ?User $actor = null,
    ): void {
        $this->postTransaction(
            $account,
            BankAccountTransaction::TYPE_CASH_DEPOSIT,
            BankAccountTransaction::DIRECTION_CREDIT,
            $amount,
            $transactionDate,
            $description,
            [
                'fund_transfer_id' => $fundTransferId,
                'notes' => $notes,
            ],
            $actor,
        );
    }

    public function paySupplier(
        BankAccount $from,
        Supplier $supplier,
        float $amount,
        string $transactionDate,
        ?string $notes = null,
        ?User $actor = null,
        ?int $fundTransferId = null,
    ): void {
        DB::transaction(function () use ($from, $supplier, $amount, $transactionDate, $notes, $actor, $fundTransferId): void {
            $locked = BankAccount::query()->lockForUpdate()->findOrFail($from->id);
            $this->assertSufficientBalance($locked, $amount);
            $this->postTransaction(
                $locked,
                BankAccountTransaction::TYPE_SUPPLIER_PAYMENT,
                BankAccountTransaction::DIRECTION_DEBIT,
                $amount,
                $transactionDate,
                __('agricart.finance.banks.ledger.supplier_payment', ['name' => $supplier->displayBusinessName()]),
                [
                    'supplier_id' => $supplier->id,
                    'notes' => $notes,
                    'fund_transfer_id' => $fundTransferId,
                ],
                $actor,
            );
        });
    }

    public function receiveCustomerPayment(
        BankAccount $into,
        Customer $customer,
        float $amount,
        string $transactionDate,
        ?string $notes = null,
        ?User $actor = null,
        ?int $fundTransferId = null,
    ): void {
        $this->postTransaction(
            $into,
            BankAccountTransaction::TYPE_CUSTOMER_RECEIPT,
            BankAccountTransaction::DIRECTION_CREDIT,
            $amount,
            $transactionDate,
            __('agricart.finance.banks.ledger.customer_receipt', ['name' => $customer->displayName()]),
            [
                'customer_id' => $customer->id,
                'notes' => $notes,
                'fund_transfer_id' => $fundTransferId,
            ],
            $actor,
        );
    }

    public function recordExpensePayment(
        BankAccount $from,
        float $amount,
        string $transactionDate,
        ?string $description,
        ?string $notes = null,
        ?int $fundTransferId = null,
        ?User $actor = null,
        ?int $expenseId = null,
        ?int $expensePaymentId = null,
    ): void {
        DB::transaction(function () use ($from, $amount, $transactionDate, $description, $notes, $fundTransferId, $actor, $expenseId, $expensePaymentId): void {
            $locked = BankAccount::query()->lockForUpdate()->findOrFail($from->id);
            $this->assertSufficientBalance($locked, $amount);
            $this->postTransaction(
                $locked,
                BankAccountTransaction::TYPE_EXPENSE_PAYMENT,
                BankAccountTransaction::DIRECTION_DEBIT,
                $amount,
                $transactionDate,
                $description,
                [
                    'notes' => $notes,
                    'fund_transfer_id' => $fundTransferId,
                    'expense_id' => $expenseId,
                    'expense_payment_id' => $expensePaymentId,
                ],
                $actor,
            );
        });
    }

    public function recordOnlineOrderReceiptIfApplicable(OnlineSaleOrder $order): void
    {
        if ($order->payment_method !== 'bank_transfer' || ! $order->bank_account_id) {
            return;
        }

        $paid = (float) $order->amount_paid;

        if ($paid <= 0) {
            return;
        }

        $sourceKey = 'online-sale-order:'.$order->id;

        if (BankAccountTransaction::query()
            ->where('online_sale_order_id', $order->id)
            ->orWhere('source_key', $sourceKey)
            ->exists()) {
            return;
        }

        $account = BankAccount::query()->find($order->bank_account_id);

        if ($account === null || ! $account->isActive()) {
            return;
        }

        $this->postTransaction(
            $account,
            BankAccountTransaction::TYPE_SALE_RECEIPT,
            BankAccountTransaction::DIRECTION_CREDIT,
            $paid,
            $order->order_date?->toDateString() ?? now()->toDateString(),
            __('agricart.finance.banks.ledger.online_order_receipt', ['number' => $order->order_number]),
            [
                'online_sale_order_id' => $order->id,
                'source_key' => $sourceKey,
            ],
            null,
        );
    }

    public function recordSaleReceiptIfApplicable(Sale $sale): void
    {
        if ($sale->payment_method !== 'bank_transfer' || ! $sale->bank_account_id) {
            return;
        }

        $paid = (float) $sale->amount_paid;

        if ($paid <= 0) {
            return;
        }

        if (BankAccountTransaction::query()->where('sale_id', $sale->id)->exists()) {
            return;
        }

        $account = BankAccount::query()->find($sale->bank_account_id);

        if ($account === null || ! $account->isActive()) {
            return;
        }

        $this->postTransaction(
            $account,
            BankAccountTransaction::TYPE_SALE_RECEIPT,
            BankAccountTransaction::DIRECTION_CREDIT,
            $paid,
            $sale->sale_date?->toDateString() ?? now()->toDateString(),
            __('agricart.finance.banks.ledger.sale_receipt', ['number' => $sale->sale_number]),
            ['sale_id' => $sale->id],
            null,
        );
    }

    public function recordSaleReturnRefundIfApplicable(SaleReturn $return): void
    {
        if ($return->payment_method !== 'bank_transfer' || ! $return->bank_account_id) {
            return;
        }

        DB::transaction(function () use ($return): void {
            $lockedReturn = SaleReturn::query()->lockForUpdate()->findOrFail($return->id);
            $paid = (float) $lockedReturn->amount_paid;

            if ($paid <= 0 || BankAccountTransaction::query()->where('sale_return_id', $lockedReturn->id)->exists()) {
                return;
            }

            $account = BankAccount::query()->lockForUpdate()->find($lockedReturn->bank_account_id);

            if ($account === null || ! $account->isActive()) {
                return;
            }

            $this->assertSufficientBalance($account, $paid);
            $this->postTransaction(
                $account,
                BankAccountTransaction::TYPE_SALE_REFUND,
                BankAccountTransaction::DIRECTION_DEBIT,
                $paid,
                $lockedReturn->return_date?->toDateString() ?? now()->toDateString(),
                __('agricart.finance.banks.ledger.sale_return_refund', ['number' => $lockedReturn->return_number]),
                ['sale_return_id' => $lockedReturn->id],
                auth()->user() instanceof User ? auth()->user() : null,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function postTransaction(
        BankAccount $account,
        string $type,
        string $direction,
        float $amount,
        string $transactionDate,
        ?string $description,
        ?array $extra,
        ?User $actor,
    ): BankAccountTransaction {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => [__('agricart.finance.banks.messages.amount_positive')],
            ]);
        }

        return BankAccountTransaction::query()->create([
            'bank_account_id' => $account->id,
            'type' => $type,
            'direction' => $direction,
            'amount' => $amount,
            'transaction_date' => $transactionDate,
            'description' => $description,
            'created_by' => $actor?->id,
            ...($extra ?? []),
        ]);
    }

    private function assertSufficientBalance(BankAccount $account, float $amount): void
    {
        if ($this->balance($account) + 0.001 < $amount) {
            throw ValidationException::withMessages([
                'amount' => [__('agricart.finance.banks.messages.insufficient_balance')],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateAccount(array $data, ?int $ignoreId = null): array
    {
        $accountNumber = isset($data['account_number']) ? trim((string) $data['account_number']) : '';

        $rules = [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ur' => ['nullable', 'string', 'max:255'],
            'bank_key' => ['nullable', 'string', 'max:64'],
            'account_title' => ['required', 'string', 'max:255'],
            'account_identifier_type' => ['required', 'string', Rule::in(BankAccount::IDENTIFIER_TYPES)],
            'account_number' => ['nullable', 'string', 'max:64'],
            'iban' => ['nullable', 'string', 'max:64'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'status' => ['required', Rule::in([BankAccount::STATUS_ACTIVE, BankAccount::STATUS_INACTIVE])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        if ($accountNumber !== '') {
            $rules['account_number'][] = Rule::unique('bank_accounts', 'account_number')
                ->ignore($ignoreId);
        }

        return validator($data, $rules, [
            'account_number.unique' => __('agricart.finance.banks.messages.duplicate_account_number'),
            'account_title.required' => __('agricart.finance.banks.messages.account_title_required'),
        ])->validate();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareAccountData(array $data): array
    {
        $type = (string) ($data['account_identifier_type'] ?? BankAccount::IDENTIFIER_ACCOUNT_NUMBER);

        if (! in_array($type, BankAccount::IDENTIFIER_TYPES, true)) {
            $type = BankAccount::IDENTIFIER_ACCOUNT_NUMBER;
        }

        $value = trim((string) ($data['account_identifier'] ?? ''));

        if ($value === '' && filled($data['iban'] ?? null)) {
            $value = trim((string) $data['iban']);
            $type = BankAccount::IDENTIFIER_IBAN;
        } elseif ($value === '' && filled($data['account_number'] ?? null)) {
            $value = trim((string) $data['account_number']);
        }

        $data['account_identifier_type'] = $type;
        unset($data['account_identifier']);

        if ($type === BankAccount::IDENTIFIER_IBAN) {
            $data['iban'] = $value !== '' ? $value : null;
            $data['account_number'] = $value !== '' ? $value : null;
        } else {
            $data['account_number'] = $value !== '' ? $value : null;
            $data['iban'] = null;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function applyBankKeyLabels(array $validated): array
    {
        $key = $validated['bank_key'] ?? null;

        if (filled($key)) {
            $validated['bank_name_en'] = PakistaniBanks::english($key);
            $validated['bank_name_ur'] = PakistaniBanks::urdu($key);
        }

        return $validated;
    }

    private function nextAccountCode(int $id): string
    {
        return 'BANK-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }
}
