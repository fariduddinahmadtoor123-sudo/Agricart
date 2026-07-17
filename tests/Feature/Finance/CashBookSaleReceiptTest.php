<?php

namespace Tests\Feature\Finance;

use App\Models\CashBookTransaction;
use App\Models\Sale;
use App\Models\User;
use App\Services\Finance\CashBookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashBookSaleReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_sale_posts_cash_book_receipt_once(): void
    {
        $cashier = User::factory()->create();

        $sale = Sale::query()->create([
            'sale_number' => 'SAL-CASH-1',
            'cashier_id' => $cashier->id,
            'status' => Sale::STATUS_COMPLETED,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'sale_date' => now()->toDateString(),
            'subtotal' => 250,
            'total_amount' => 250,
            'amount_paid' => 250,
        ]);

        $service = app(CashBookService::class);
        $service->recordSaleReceiptIfApplicable($sale);
        $service->recordSaleReceiptIfApplicable($sale);

        $this->assertSame(1, CashBookTransaction::query()->where('sale_id', $sale->id)->count());
        $this->assertSame(250.0, $service->balance());
    }

    public function test_bank_sale_does_not_post_cash_book(): void
    {
        $cashier = User::factory()->create();

        $sale = Sale::query()->create([
            'sale_number' => 'SAL-BANK-1',
            'cashier_id' => $cashier->id,
            'status' => Sale::STATUS_COMPLETED,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'sale_date' => now()->toDateString(),
            'subtotal' => 100,
            'total_amount' => 100,
            'amount_paid' => 100,
        ]);

        app(CashBookService::class)->recordSaleReceiptIfApplicable($sale);

        $this->assertSame(0, CashBookTransaction::query()->count());
    }
}
