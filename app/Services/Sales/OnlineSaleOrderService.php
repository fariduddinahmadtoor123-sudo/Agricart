<?php

namespace App\Services\Sales;

use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Finance\BankAccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OnlineSaleOrderService
{
    /** @var list<string> */
    public const LOCALES = ['en', 'ur'];

    public function __construct(
        private readonly SaleService $saleService,
    ) {}

    public function nextOrderNumber(): string
    {
        $latest = OnlineSaleOrder::query()
            ->where('order_number', 'like', 'WEB-%')
            ->orderByDesc('id')
            ->value('order_number');

        $sequence = 1;

        if (is_string($latest) && preg_match('/^WEB-(\d+)$/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return 'WEB-' . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createFromCheckout(array $payload): OnlineSaleOrder
    {
        $validated = validator($payload, [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'locale' => ['nullable', 'string', Rule::in(self::LOCALES)],
            'customer_payment_method' => ['nullable', 'string', Rule::in(SaleService::PAYMENT_METHODS)],
            'customer_payment_note' => ['nullable', 'string', 'max:2000'],
            'customer_payment_reference' => ['nullable', 'string', 'max:128'],
            'customer_notes' => ['nullable', 'string', 'max:5000'],
            'ship_contact_name' => ['nullable', 'string', 'max:255'],
            'ship_phone' => ['nullable', 'string', 'max:64'],
            'ship_address' => ['nullable', 'string', 'max:2000'],
            'ship_city' => ['nullable', 'string', 'max:128'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'rows.*.quantity' => ['required', 'string'],
            'rows.*.unit_price' => ['nullable', 'string'],
            'rows.*.price_tier_key' => ['nullable', 'string', 'max:32'],
        ])->validate();

        $rows = $this->normalizeCheckoutRows($validated['rows']);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'rows' => [__('online_sale_orders.validation.items_required')],
            ]);
        }

        return DB::transaction(function () use ($validated, $rows): OnlineSaleOrder {
            $subtotal = $this->subtotalFromRows($rows);
            $total = $subtotal;
            $paymentMethod = $validated['customer_payment_method'] ?? null;
            $proofStatus = $paymentMethod === 'bank_transfer'
                ? OnlineSaleOrder::PROOF_AWAITING
                : OnlineSaleOrder::PROOF_NONE;

            $order = OnlineSaleOrder::query()->create([
                'order_number' => $this->nextOrderNumber(),
                'customer_id' => (int) $validated['customer_id'],
                'order_date' => now()->toDateString(),
                'locale' => $this->normalizeLocale((string) ($validated['locale'] ?? 'en')),
                'order_status' => OnlineSaleOrder::STATUS_PENDING,
                'payment_status' => 'unpaid',
                'customer_payment_method' => $paymentMethod,
                'customer_payment_note' => $validated['customer_payment_note'] ?? null,
                'customer_payment_reference' => $validated['customer_payment_reference'] ?? null,
                'customer_payment_proof_status' => $proofStatus,
                'subtotal' => $subtotal,
                'total_amount' => $total,
                'amount_paid' => 0,
                'ship_contact_name' => $validated['ship_contact_name'] ?? null,
                'ship_phone' => $validated['ship_phone'] ?? null,
                'ship_address' => $validated['ship_address'] ?? null,
                'ship_city' => $validated['ship_city'] ?? null,
                'customer_notes' => $validated['customer_notes'] ?? null,
            ]);

            foreach ($rows as $index => $row) {
                OnlineSaleOrderItem::query()->create([
                    'online_sale_order_id' => $order->id,
                    ...$row,
                    'sort_order' => $index,
                ]);
            }

            $order = $order->fresh(['items', 'customer']);

            app(\App\Services\Store\OnlineStoreOrderNotifier::class)->orderPlaced($order);

            return $order;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function submitCustomerPaymentProof(OnlineSaleOrder $order, Customer $customer, array $payload): OnlineSaleOrder
    {
        if ((int) $order->customer_id !== (int) $customer->id) {
            throw ValidationException::withMessages([
                'order' => [__('agricart.customer.orders.payment_forbidden')],
            ]);
        }

        if ($order->isLocked() || $order->order_status === OnlineSaleOrder::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'order' => [__('agricart.customer.orders.payment_closed')],
            ]);
        }

        if ($order->customer_payment_proof_status === OnlineSaleOrder::PROOF_VERIFIED) {
            throw ValidationException::withMessages([
                'order' => [__('agricart.customer.orders.payment_already_verified')],
            ]);
        }

        if (! in_array($order->customer_payment_proof_status, [
            OnlineSaleOrder::PROOF_AWAITING,
            OnlineSaleOrder::PROOF_SUBMITTED,
            OnlineSaleOrder::PROOF_REJECTED,
        ], true)) {
            throw ValidationException::withMessages([
                'order' => [__('agricart.customer.orders.payment_closed')],
            ]);
        }

        if ((string) $order->customer_payment_method !== 'bank_transfer') {
            throw ValidationException::withMessages([
                'payment' => [__('agricart.customer.orders.payment_not_bank')],
            ]);
        }

        $validated = validator($payload, [
            'customer_payment_reference' => ['nullable', 'string', 'max:128'],
            'bank_receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ])->validate();

        $reference = trim((string) ($validated['customer_payment_reference'] ?? ''));
        $upload = $validated['bank_receipt'] ?? null;

        if ($reference === '' && $upload === null && ! filled($order->customer_bank_receipt_path)) {
            throw ValidationException::withMessages([
                'bank_receipt' => [__('agricart.customer.orders.payment_proof_required')],
            ]);
        }

        if ($reference !== '') {
            $order->customer_payment_reference = $reference;
        }

        if ($upload !== null) {
            $path = $upload->store('online-orders/customer-receipts/' . $order->id, 'public');
            $order->customer_bank_receipt_path = $path;
        }

        $order->customer_payment_proof_status = OnlineSaleOrder::PROOF_SUBMITTED;
        $order->payment_proof_rejection_note = null;
        $order->save();

        return $order->fresh();
    }

    public function verifyCustomerPaymentProof(OnlineSaleOrder $order, User $reviewer): OnlineSaleOrder
    {
        if ($order->isLocked()) {
            throw ValidationException::withMessages([
                'order' => [__('online_sale_orders.validation.converted_locked')],
            ]);
        }

        $order->customer_payment_proof_status = OnlineSaleOrder::PROOF_VERIFIED;
        $order->payment_proof_rejection_note = null;
        $order->payment_proof_reviewed_at = now();
        $order->payment_proof_reviewed_by_id = $reviewer->id;
        $order->payment_status = 'paid';
        $order->amount_paid = $order->total_amount;

        if ($order->payment_method === '' || $order->payment_method === null) {
            $order->payment_method = $order->customer_payment_method ?: 'bank_transfer';
        }

        if ($order->order_status === OnlineSaleOrder::STATUS_PENDING) {
            $order->order_status = OnlineSaleOrder::STATUS_PAYMENT_RECEIVED;
        }

        $order->save();

        app(OnlineOrderDispatchService::class)->enterDispatchQueueIfPaid($order->fresh());
        app(BankAccountService::class)->recordOnlineOrderReceiptIfApplicable($order->fresh());

        return $order->fresh(['items', 'customer', 'paymentProofReviewedBy']);
    }

    public function rejectCustomerPaymentProof(OnlineSaleOrder $order, User $reviewer, ?string $note = null): OnlineSaleOrder
    {
        if ($order->isLocked()) {
            throw ValidationException::withMessages([
                'order' => [__('online_sale_orders.validation.converted_locked')],
            ]);
        }

        $order->customer_payment_proof_status = OnlineSaleOrder::PROOF_REJECTED;
        $order->payment_proof_rejection_note = filled($note) ? trim($note) : null;
        $order->payment_proof_reviewed_at = now();
        $order->payment_proof_reviewed_by_id = $reviewer->id;
        $order->save();

        return $order->fresh(['paymentProofReviewedBy']);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateAdmin(OnlineSaleOrder $order, array $payload): OnlineSaleOrder
    {
        if ($order->isLocked()) {
            throw ValidationException::withMessages([
                'order' => [__('online_sale_orders.validation.converted_locked')],
            ]);
        }

        $validated = validator($payload, [
            'order_status' => ['required', 'string', Rule::in(OnlineSaleOrder::STATUSES)],
            'fulfillment_status' => ['nullable', 'string', Rule::in(OnlineSaleOrder::FULFILLMENT_STATUSES)],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'payment_status' => ['required', 'string', Rule::in(SaleService::PAYMENT_STATUSES)],
            'payment_method' => ['required', 'string', Rule::in(SaleService::PAYMENT_METHODS)],
            'bank_account_id' => [
                'nullable',
                'integer',
                'exists:bank_accounts,id',
                Rule::requiredIf(($payload['payment_method'] ?? '') === 'bank_transfer'),
            ],
            'amount_paid' => ['nullable', 'string'],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
            'supplier_ids' => ['nullable', 'array'],
            'supplier_ids.*' => ['integer', 'exists:suppliers,id'],
        ])->validate();

        if ($validated['payment_method'] !== 'bank_transfer') {
            $validated['bank_account_id'] = null;
        }

        $amountPaid = $this->saleService->numeric((string) ($validated['amount_paid'] ?? '0'));
        $total = (float) $order->total_amount;

        if ($validated['payment_status'] === 'paid') {
            $amountPaid = $total;
        } elseif ($validated['payment_status'] === 'unpaid') {
            $amountPaid = 0.0;
        }

        if ($amountPaid > $total + 0.001) {
            throw ValidationException::withMessages([
                'amount_paid' => [__('online_sale_orders.validation.amount_paid_exceeds_total')],
            ]);
        }

        $orderStatus = $validated['order_status'];

        if ($validated['payment_status'] === 'paid' && $orderStatus === OnlineSaleOrder::STATUS_PENDING) {
            $orderStatus = OnlineSaleOrder::STATUS_PAYMENT_RECEIVED;
        }

        $order->fill([
            'order_status' => $orderStatus,
            'fulfillment_status' => $validated['fulfillment_status'] ?? $order->fulfillment_status,
            'assigned_user_id' => $validated['assigned_user_id'] ?? null,
            'payment_status' => $validated['payment_status'],
            'payment_method' => $validated['payment_method'],
            'bank_account_id' => $validated['bank_account_id'],
            'amount_paid' => $amountPaid,
            'admin_notes' => $validated['admin_notes'] ?? $order->admin_notes,
        ]);

        if (
            filled($validated['assigned_user_id'] ?? null)
            && $order->fulfillment_status === OnlineSaleOrder::FULFILLMENT_AWAITING
        ) {
            $order->fulfillment_status = OnlineSaleOrder::FULFILLMENT_ASSIGNED;
        }

        $order->save();

        $supplierIds = $validated['supplier_ids'] ?? null;

        if (is_array($supplierIds)) {
            app(OnlineOrderDispatchService::class)->syncSupplierAssignments(
                $order,
                $supplierIds,
                auth()->user(),
            );
        }

        $order = $order->fresh(['supplierAssignments']);

        if (isset($payload['supplier_fulfillment']) && is_array($payload['supplier_fulfillment'])) {
            app(OnlineOrderDispatchService::class)->applySupplierFulfillmentUpdates(
                $order,
                $payload['supplier_fulfillment'],
                is_array($payload['supplier_proof_uploads'] ?? null) ? $payload['supplier_proof_uploads'] : [],
            );
        }

        app(OnlineOrderDispatchService::class)->enterDispatchQueueIfPaid($order->fresh());

        $order = $order->fresh(['items', 'customer', 'sale', 'supplierAssignments.supplier', 'assignedUser']);

        app(BankAccountService::class)->recordOnlineOrderReceiptIfApplicable($order);

        return $order;
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormState(OnlineSaleOrder $order): array
    {
        $order->loadMissing(['items', 'customer', 'sale', 'supplierAssignments']);

        $rows = [];

        foreach ($order->items as $item) {
            $rows[] = [
                'product_id' => $item->product_id,
                'product_name_en' => $item->product_name_en,
                'product_name_ur' => $item->product_name_ur,
                'brand_name_en' => $item->brand_name_en,
                'brand_name_ur' => $item->brand_name_ur,
                'unit_label' => $item->unit_label,
                'image_path' => $item->image_path,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ];
        }

        return [
            'order_number' => $order->order_number,
            'order_date' => $order->order_date?->format('Y-m-d') ?? '',
            'locale' => $order->locale,
            'order_status' => $order->order_status,
            'fulfillment_status' => (string) ($order->fulfillment_status ?? OnlineSaleOrder::FULFILLMENT_AWAITING),
            'assigned_user_id' => $order->assigned_user_id,
            'payment_status' => $order->payment_status,
            'customer_payment_method' => (string) ($order->customer_payment_method ?? ''),
            'customer_payment_note' => (string) ($order->customer_payment_note ?? ''),
            'customer_payment_reference' => (string) ($order->customer_payment_reference ?? ''),
            'customer_payment_proof_status' => (string) ($order->customer_payment_proof_status ?? OnlineSaleOrder::PROOF_NONE),
            'customer_bank_receipt_url' => $order->customerBankReceiptUrl(),
            'payment_proof_rejection_note' => (string) ($order->payment_proof_rejection_note ?? ''),
            'payment_method' => (string) ($order->payment_method ?? ''),
            'bank_account_id' => $order->bank_account_id,
            'subtotal' => $this->saleService->formatMoney((float) $order->subtotal),
            'total_amount' => $this->saleService->formatMoney((float) $order->total_amount),
            'amount_paid' => $this->saleService->formatMoney((float) $order->amount_paid),
            'balance_due' => $this->saleService->formatMoney(max(0, (float) $order->total_amount - (float) $order->amount_paid)),
            'ship_contact_name' => (string) ($order->ship_contact_name ?? ''),
            'ship_phone' => (string) ($order->ship_phone ?? ''),
            'ship_address' => (string) ($order->ship_address ?? ''),
            'ship_city' => (string) ($order->ship_city ?? ''),
            'customer_notes' => (string) ($order->customer_notes ?? ''),
            'admin_notes' => (string) ($order->admin_notes ?? ''),
            'sale_number' => $order->sale?->sale_number ?? '',
            'supplier_assignment_ids' => $order->supplierAssignments->pluck('supplier_id')->all(),
            'rows' => $rows,
        ];
    }

    public function normalizeLocale(string $locale): string
    {
        return in_array($locale, self::LOCALES, true) ? $locale : 'en';
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function normalizeCheckoutRows(array $rows): array
    {
        $normalized = [];
        $products = Product::query()
            ->whereIn('id', collect($rows)->pluck('product_id')->filter()->all())
            ->with('brand')
            ->get()
            ->keyBy('id');

        foreach ($rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $product = $products->get($productId);

            if ($product === null) {
                continue;
            }

            $qty = $this->saleService->numeric((string) ($row['quantity'] ?? '0'));

            if ($qty <= 0) {
                continue;
            }

            $unitPrice = filled($row['unit_price'] ?? null)
                ? $this->saleService->numeric((string) $row['unit_price'])
                : 0.0;

            $lineTotal = $qty * $unitPrice;

            $normalized[] = [
                'product_id' => $product->id,
                'product_name_en' => (string) $product->name_en,
                'product_name_ur' => $product->name_ur,
                'brand_name_en' => $product->brand?->name_en,
                'brand_name_ur' => $product->brand?->name_ur,
                'unit_label' => null,
                'image_path' => $product->image_path ?? null,
                'quantity' => $this->saleService->formatQty($qty),
                'unit_price' => $this->saleService->formatMoney($unitPrice),
                'price_tier_key' => $row['price_tier_key'] ?? 'retail',
                'line_total' => $this->saleService->formatMoney($lineTotal),
            ];
        }

        return $normalized;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function subtotalFromRows(array $rows): float
    {
        $sum = 0.0;

        foreach ($rows as $row) {
            $sum += $this->saleService->numeric((string) ($row['line_total'] ?? '0'));
        }

        return $sum;
    }
}
