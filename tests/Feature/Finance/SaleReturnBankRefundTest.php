<?php

namespace Tests\Feature\Finance;

use App\Models\BankAccount;
use App\Models\BankAccountTransaction;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\Finance\BankAccountService;
use App\Services\Sales\SaleReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaleReturnBankRefundTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_refund_posts_once_and_locks_financial_return(): void
    {
        $bank = BankAccount::query()->create([
            'account_code' => 'BNK-00001',
            'name_en' => 'Main Bank',
            'bank_name_en' => 'HBL',
            'account_title' => 'Agricart',
            'account_number' => '1234567890',
            'account_identifier_type' => BankAccount::IDENTIFIER_ACCOUNT_NUMBER,
            'opening_balance' => 5000,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);
        $sale = Sale::query()->create([
            'sale_number' => 'SALE-1',
            'sale_date' => today(),
            'payment_status' => 'paid',
            'payment_method' => 'bank_transfer',
            'bank_account_id' => $bank->id,
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'status' => Sale::STATUS_COMPLETED,
        ]);
        $return = SaleReturn::query()->create([
            'return_number' => 'RET-1',
            'sale_id' => $sale->id,
            'return_date' => today(),
            'refund_subtotal' => 500,
            'refund_total' => 500,
            'payment_status' => 'paid',
            'payment_method' => 'bank_transfer',
            'bank_account_id' => $bank->id,
            'amount_paid' => 500,
        ]);

        $banks = app(BankAccountService::class);
        $banks->recordSaleReturnRefundIfApplicable($return);
        $banks->recordSaleReturnRefundIfApplicable($return);

        $this->assertSame(1, BankAccountTransaction::query()->where('sale_return_id', $return->id)->count());
        $this->assertSame(4500.0, $banks->balance($bank));

        $this->expectException(ValidationException::class);
        app(SaleReturnService::class)->assertFinanciallyEditable($return);
    }
}
