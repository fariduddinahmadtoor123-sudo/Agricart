<?php

namespace Tests\Feature\Finance;

use App\Models\BankAccount;
use App\Models\BankAccountTransaction;
use App\Models\CashBookTransaction;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Finance\BankAccountService;
use App\Services\Purchasing\PurchaseService;
use App\Services\Sales\OnlineSaleOrderService;
use App\Services\Sales\SaleReturnService;
use App\Services\Sales\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseTwoTransactionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_bank_opening_balance_is_reconciled_with_its_audit_entry(): void
    {
        $account = app(BankAccountService::class)->create([
            'name_en' => 'Opening Balance Bank',
            'account_title' => 'Agricart',
            'account_identifier_type' => BankAccount::IDENTIFIER_ACCOUNT_NUMBER,
            'account_identifier' => 'OPEN-001',
            'opening_balance' => 1250.50,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);

        $this->assertSame(1250.50, (float) $account->opening_balance);
        $this->assertSame(1250.50, app(BankAccountService::class)->balance($account));
        $this->assertDatabaseHas('bank_account_transactions', [
            'bank_account_id' => $account->id,
            'type' => BankAccountTransaction::TYPE_OPENING,
            'amount' => 1250.50,
        ]);
    }

    public function test_return_uses_locked_sale_values_and_aggregates_duplicate_lines(): void
    {
        [$sale, $item, $product] = $this->saleWithItem(quantity: 5, unitPrice: 40);

        $return = app(SaleReturnService::class)->create($this->returnPayload($sale, [
            [
                'sale_item_id' => $item->id,
                'product_id' => $product->id,
                'product_name_en' => 'Forged name',
                'sold_quantity' => '999',
                'unit_price' => '1',
                'return_quantity' => '1',
            ],
            [
                'sale_item_id' => $item->id,
                'product_id' => $product->id,
                'product_name_en' => 'Forged name',
                'sold_quantity' => '999',
                'unit_price' => '1',
                'return_quantity' => '2',
            ],
            [
                'sale_item_id' => $item->id,
                'return_quantity' => '0',
            ],
        ]));

        $this->assertCount(1, $return->items);
        $this->assertSame('Trusted product', $return->items->first()->product_name_en);
        $this->assertSame(3.0, (float) $return->items->first()->return_quantity);
        $this->assertSame(40.0, (float) $return->items->first()->unit_price);
        $this->assertSame(120.0, (float) $return->refund_total);
        $this->assertSame(3.0, (float) $product->fresh()->stock_qty);
    }

    public function test_locked_bank_debits_cannot_overdraw_the_source_account(): void
    {
        $source = $this->bank(100, 'SOURCE');
        $destination = $this->bank(0, 'DESTINATION');
        $service = app(BankAccountService::class);

        $service->transferToBank($source, $destination, 80, today()->toDateString());

        try {
            $service->transferToBank($source, $destination, 30, today()->toDateString());
            $this->fail('An overdrawing debit was accepted.');
        } catch (ValidationException) {
            $this->assertSame(20.0, $service->balance($source));
            $this->assertSame(1, BankAccountTransaction::query()
                ->where('bank_account_id', $source->id)
                ->where('direction', BankAccountTransaction::DIRECTION_DEBIT)
                ->count());
        }
    }

    public function test_return_rejects_wrong_sale_items_and_cumulative_over_returns(): void
    {
        [$sale, $item] = $this->saleWithItem(quantity: 2, unitPrice: 50);
        [, $otherItem] = $this->saleWithItem(quantity: 2, unitPrice: 50, suffix: 'B');

        try {
            app(SaleReturnService::class)->create($this->returnPayload($sale, [[
                'sale_item_id' => $otherItem->id,
                'return_quantity' => 1,
            ]]));
            $this->fail('A sale item from another sale was accepted.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('sale_returns', 0);
        }

        app(SaleReturnService::class)->create($this->returnPayload($sale, [[
            'sale_item_id' => $item->id,
            'return_quantity' => 2,
        ]]));

        $this->expectException(ValidationException::class);
        app(SaleReturnService::class)->create($this->returnPayload($sale, [[
            'sale_item_id' => $item->id,
            'return_quantity' => 1,
        ]]));
    }

    public function test_sales_and_returns_reject_overpayment_and_completed_sales_are_immutable(): void
    {
        [$sale, $item] = $this->saleWithItem(quantity: 2, unitPrice: 50);

        try {
            app(SaleService::class)->complete($this->salePayload($this->product('OVERPAY'), amountPaid: 101));
            $this->fail('Sale overpayment was accepted.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        try {
            app(SaleReturnService::class)->create($this->returnPayload($sale, [[
                'sale_item_id' => $item->id,
                'return_quantity' => 1,
            ]], amountPaid: 51));
            $this->fail('Return overpayment was accepted.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        CashBookTransaction::query()->create([
            'sale_id' => $sale->id,
            'direction' => CashBookTransaction::DIRECTION_IN,
            'amount' => 100,
            'transaction_date' => today(),
        ]);

        try {
            app(SaleService::class)->delete($sale);
            $this->fail('A posted completed sale was deleted.');
        } catch (ValidationException) {
            $this->assertModelExists($sale);
            $this->assertDatabaseHas('cash_book_transactions', ['sale_id' => $sale->id]);
        }
    }

    public function test_payment_proof_verification_is_idempotent_with_one_ledger_source(): void
    {
        $bank = $this->bank(0);
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-PHASE2',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Proof',
            'last_name' => 'Owner',
            'email' => 'phase2-proof@example.test',
            'phone' => '03001234567',
            'password' => bcrypt('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-PHASE2',
            'customer_id' => $customer->id,
            'order_date' => today(),
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'payment_status' => 'unpaid',
            'customer_payment_method' => 'bank_transfer',
            'customer_payment_proof_status' => OnlineSaleOrder::PROOF_SUBMITTED,
            'payment_method' => 'bank_transfer',
            'bank_account_id' => $bank->id,
            'subtotal' => 300,
            'total_amount' => 300,
            'amount_paid' => 0,
        ]);
        $reviewer = User::factory()->create();
        $service = app(OnlineSaleOrderService::class);

        $service->verifyCustomerPaymentProof($order, $reviewer);
        $service->verifyCustomerPaymentProof($order->fresh(), $reviewer);

        $this->assertSame(1, BankAccountTransaction::query()->where('online_sale_order_id', $order->id)->count());
        $this->assertSame(300.0, app(BankAccountService::class)->balance($bank));
    }

    public function test_verify_then_convert_posts_bank_credit_only_once(): void
    {
        $bank = $this->bank(0);
        $category = Category::query()->create([
            'slug' => 'phase2-bank-cat',
            'status' => 'active',
            'name_en' => 'Phase2',
            'name_ur' => 'Phase2',
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'phase2-bank-product',
            'code' => 'P2-BANK',
            'status' => 'active',
            'name_en' => 'Bank Item',
            'name_ur' => 'Bank Item',
            'stock_qty' => 20,
            'retail_price' => 300,
        ]);
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-PHASE2B',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Convert',
            'last_name' => 'Pay',
            'email' => 'phase2-convert@example.test',
            'phone' => '03007654321',
            'password' => bcrypt('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-PHASE2-CONV',
            'customer_id' => $customer->id,
            'order_date' => today(),
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'payment_status' => 'unpaid',
            'customer_payment_method' => 'bank_transfer',
            'customer_payment_proof_status' => OnlineSaleOrder::PROOF_SUBMITTED,
            'payment_method' => 'bank_transfer',
            'bank_account_id' => $bank->id,
            'subtotal' => 300,
            'total_amount' => 300,
            'amount_paid' => 0,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => 'Bank Item',
            'quantity' => 1,
            'unit_price' => 300,
            'line_total' => 300,
        ]);

        $actor = User::factory()->create();
        $service = app(OnlineSaleOrderService::class);

        $service->verifyCustomerPaymentProof($order, $actor);
        $service->convertToSale($order->fresh(['items']), $actor);

        $this->assertSame(1, BankAccountTransaction::query()
            ->where('type', BankAccountTransaction::TYPE_SALE_RECEIPT)
            ->where('direction', BankAccountTransaction::DIRECTION_CREDIT)
            ->where(function ($query) use ($order): void {
                $query->where('online_sale_order_id', $order->id)
                    ->orWhere('sale_id', $order->fresh()->sale_id);
            })
            ->count());
        $this->assertSame(300.0, app(BankAccountService::class)->balance($bank->fresh()));
        $this->assertSame(0, app(BankAccountService::class)->removeDuplicateConvertedOnlineOrderSaleReceipts());

        // Legacy double-post: sale credit without online_sale_order_id while order credit exists.
        BankAccountTransaction::query()->create([
            'bank_account_id' => $bank->id,
            'type' => BankAccountTransaction::TYPE_SALE_RECEIPT,
            'direction' => BankAccountTransaction::DIRECTION_CREDIT,
            'amount' => 300,
            'transaction_date' => today(),
            'sale_id' => $order->fresh()->sale_id,
            'description' => 'duplicate sale credit',
        ]);
        $this->assertSame(600.0, app(BankAccountService::class)->balance($bank->fresh()));
        $this->assertSame(1, app(BankAccountService::class)->removeDuplicateConvertedOnlineOrderSaleReceipts());
        $this->assertSame(0, app(BankAccountService::class)->removeDuplicateConvertedOnlineOrderSaleReceipts());
        $this->assertSame(300.0, app(BankAccountService::class)->balance($bank->fresh()));
    }

    public function test_convert_unpaid_bank_transfer_does_not_invent_payment_or_bank_credit(): void
    {
        $bank = $this->bank(0);
        $category = Category::query()->create([
            'slug' => 'phase2-unpaid-cat',
            'status' => 'active',
            'name_en' => 'Phase2 Unpaid',
            'name_ur' => 'Phase2 Unpaid',
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'slug' => 'phase2-unpaid-product',
            'code' => 'P2-UNPAID',
            'status' => 'active',
            'name_en' => 'Unpaid Item',
            'name_ur' => 'Unpaid Item',
            'stock_qty' => 20,
            'retail_price' => 300,
        ]);
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-PHASE2U',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Unpaid',
            'last_name' => 'Convert',
            'email' => 'phase2-unpaid@example.test',
            'phone' => '03007654322',
            'password' => bcrypt('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-PHASE2-UNPAID',
            'customer_id' => $customer->id,
            'order_date' => today(),
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'payment_status' => 'unpaid',
            'customer_payment_method' => 'bank_transfer',
            'customer_payment_proof_status' => OnlineSaleOrder::PROOF_SUBMITTED,
            'payment_method' => 'bank_transfer',
            'bank_account_id' => $bank->id,
            'subtotal' => 300,
            'total_amount' => 300,
            'amount_paid' => 0,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => 'Unpaid Item',
            'quantity' => 1,
            'unit_price' => 300,
            'line_total' => 300,
        ]);

        $actor = User::factory()->create();
        $sale = app(OnlineSaleOrderService::class)->convertToSale($order->fresh(['items']), $actor);

        $order->refresh();
        $this->assertSame(OnlineSaleOrder::STATUS_CONVERTED, $order->order_status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(0.0, (float) $order->amount_paid);
        $this->assertSame(OnlineSaleOrder::PROOF_SUBMITTED, $order->customer_payment_proof_status);
        $this->assertSame('unpaid', $sale->payment_status);
        $this->assertSame(0.0, (float) $sale->amount_paid);
        $this->assertSame(0, BankAccountTransaction::query()
            ->where('type', BankAccountTransaction::TYPE_SALE_RECEIPT)
            ->where(function ($query) use ($order, $sale): void {
                $query->where('online_sale_order_id', $order->id)
                    ->orWhere('sale_id', $sale->id);
            })
            ->count());
        $this->assertSame(0.0, app(BankAccountService::class)->balance($bank->fresh()));
    }

    public function test_verify_payment_proof_requires_bank_account(): void
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-PHASE2V',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Verify',
            'last_name' => 'Bank',
            'email' => 'phase2-verify-bank@example.test',
            'phone' => '03007654323',
            'password' => bcrypt('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-PHASE2-NOBANK',
            'customer_id' => $customer->id,
            'order_date' => today(),
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'payment_status' => 'unpaid',
            'customer_payment_method' => 'bank_transfer',
            'customer_payment_proof_status' => OnlineSaleOrder::PROOF_SUBMITTED,
            'payment_method' => 'bank_transfer',
            'bank_account_id' => null,
            'subtotal' => 150,
            'total_amount' => 150,
            'amount_paid' => 0,
        ]);
        $reviewer = User::factory()->create();

        try {
            app(OnlineSaleOrderService::class)->verifyCustomerPaymentProof($order, $reviewer);
            $this->fail('Verify without bank account should fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('bank_account_id', $exception->errors());
        }

        $order->refresh();
        $this->assertSame(OnlineSaleOrder::PROOF_SUBMITTED, $order->customer_payment_proof_status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(0.0, (float) $order->amount_paid);
    }

    public function test_negative_and_nonnumeric_sale_and_purchase_inputs_are_rejected(): void
    {
        $product = $this->product('VALIDATION');

        foreach (['-1', 'not-a-number'] as $quantity) {
            try {
                app(SaleService::class)->complete($this->salePayload($product, quantity: $quantity));
                $this->fail("Invalid sale quantity {$quantity} was accepted.");
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }

        $supplier = Supplier::query()->create([
            'supplier_code' => 'SUP-PHASE2',
            'status' => 'active',
            'business_name_en' => 'Phase 2 Supplier',
            'business_name_ur' => 'Phase 2 Supplier',
            'address_en' => '1 Test Street',
            'city_en' => 'Lahore',
            'opening_balance' => 0,
            'contacts' => [['mobile_number' => '03001112233', 'contact_name_en' => 'Contact']],
        ]);
        $purchaser = User::factory()->create();

        $this->expectException(ValidationException::class);
        app(PurchaseService::class)->validate([
            'supplier_id' => $supplier->id,
            'purchaser_id' => $purchaser->id,
            'locale' => 'en',
            'purchase_date' => today()->toDateString(),
            'delivery_status' => 'pending',
            'payment_status' => 'pending',
            'dispute_status' => 'ok',
            'amount_paid' => 0,
            'items' => [[
                'product_name_en' => 'Invalid',
                'purchase_qty' => '-2',
                'current_rate' => 'bad',
            ]],
        ]);
    }

    /**
     * @return array{Sale, SaleItem, Product}
     */
    private function saleWithItem(float $quantity, float $unitPrice, string $suffix = 'A'): array
    {
        $product = $this->product("RETURN-{$suffix}");
        $sale = Sale::query()->create([
            'sale_number' => "SALE-RETURN-{$suffix}",
            'sale_date' => today(),
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => $quantity * $unitPrice,
            'total_amount' => $quantity * $unitPrice,
            'amount_paid' => $quantity * $unitPrice,
            'status' => Sale::STATUS_COMPLETED,
        ]);
        $item = SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name_en' => 'Trusted product',
            'quantity' => (string) $quantity,
            'unit_price' => (string) $unitPrice,
            'line_total' => (string) ($quantity * $unitPrice),
        ]);

        return [$sale, $item, $product];
    }

    private function returnPayload(Sale $sale, array $rows, float $amountPaid = 0): array
    {
        return [
            'sale_id' => $sale->id,
            'return_date' => today()->toDateString(),
            'payment_status' => 'unpaid',
            'payment_method' => 'cash',
            'amount_paid' => $amountPaid,
            'rows' => $rows,
        ];
    }

    private function salePayload(Product $product, string $quantity = '1', float $amountPaid = 100): array
    {
        return [
            'sale_date' => today()->toDateString(),
            'locale' => 'en',
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'price_display' => 'plain',
            'amount_paid' => $amountPaid,
            'rows' => [[
                'product_id' => $product->id,
                'product_name_en' => $product->name_en,
                'quantity' => $quantity,
                'unit_price' => '100',
            ]],
        ];
    }

    private function product(string $suffix): Product
    {
        $category = Category::query()->create([
            'slug' => 'phase2-'.strtolower($suffix),
            'status' => 'active',
            'name_en' => 'Phase 2',
            'name_ur' => 'Phase 2',
        ]);

        return Product::query()->create([
            'code' => "P2-{$suffix}",
            'slug' => 'p2-'.strtolower($suffix),
            'status' => 'active',
            'name_en' => 'Trusted product',
            'name_ur' => 'Trusted product',
            'category_id' => $category->id,
            'sku' => "P2-{$suffix}",
            'stock_qty' => 0,
            'retail_price' => 100,
        ]);
    }

    private function bank(float $opening, string $suffix = 'PHASE2'): BankAccount
    {
        return BankAccount::query()->create([
            'account_code' => "BANK-{$suffix}",
            'name_en' => 'Phase 2 Bank',
            'account_title' => 'Agricart',
            'account_number' => "P2-{$suffix}",
            'account_identifier_type' => BankAccount::IDENTIFIER_ACCOUNT_NUMBER,
            'opening_balance' => $opening,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);
    }
}
