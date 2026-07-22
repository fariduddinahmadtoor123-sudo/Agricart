<?php

namespace App\Livewire\OnlineSaleOrders;

use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderSupplierAssignment;
use App\Models\User;
use App\Services\PricingControls\ChannelPricingEngine;
use App\Services\Sales\OnlineOrderDispatchService;
use App\Services\Sales\OnlineSaleOrderService;
use App\Services\Sales\SaleService;
use App\Services\Sales\SalesBankAccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;
    public ?int $orderId = null;

    public string $mode = 'view';

    public string $order_number = '';

    public string $order_date = '';

    public string $locale = 'en';

    public string $order_status = '';

    public string $fulfillment_status = '';

    public ?int $assigned_user_id = null;

    public string $payment_status = '';

    public string $customer_payment_method = '';

    public string $customer_payment_note = '';

    public string $customer_payment_reference = '';

    public string $customer_payment_proof_status = '';

    public string $customer_bank_receipt_url = '';

    public string $payment_proof_rejection_note = '';

    public string $payment_proof_reject_input = '';

    public string $payment_method = '';

    public ?int $bank_account_id = null;

    public string $amount_paid = '0';

    public string $subtotal = '0';

    public string $total_amount = '0';

    public string $balance_due = '0';

    public string $ship_contact_name = '';

    public string $ship_phone = '';

    public string $ship_address = '';

    public string $ship_city = '';

    public string $delivery_method_name = '';

    public string $shipping_charge_mode_label = '';

    public string $cod_min_advance_percent = '';

    public bool $full_advance_required = false;

    public string $delivery_customer_notice = '';

    public string $customer_notes = '';

    public string $admin_notes = '';

    public string $linked_sale_number = '';

    public string $converted_by_name = '';

    public string $converted_at = '';

    public string $queued_at_display = '';

    public string $queue_document = '';

    /** @var list<int> */
    public array $selectedSupplierIds = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    public array $supplierFulfillment = [];

    /**
     * @var array<int|string, mixed>
     */
    public array $supplierProofUploads = [];

    /** @var list<array<string, mixed>> */
    public array $supplierAssignmentRows = [];

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    private bool $recalculatingTotals = false;

    #[On('online-order-form-open')]
    public function openForm(?int $orderId = null, string $mode = 'view'): void
    {
        $this->resetForm();
        $this->orderId = $orderId;
        $this->mode = $mode;

        if ($orderId) {
            $this->loadOrder($orderId);
        }
    }

    #[On('online-order-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            'print' => $this->saveAndPrint(),
            'packing_print' => $this->printPackingSlip(),
            'convert_invoice' => $this->convertToSale(),
            default => null,
        };
    }

    public function updatedSaving(): void
    {
        $this->dispatchBusyState();
    }

    public function updatedPaymentMethod(): void
    {
        if ($this->payment_method !== 'bank_transfer') {
            $this->bank_account_id = null;
        }
    }

    public function updatedPaymentStatus(): void
    {
        $service = app(SaleService::class);

        if ($this->payment_status === 'paid') {
            $total = $service->numeric($this->total_amount);
            $this->amount_paid = $service->formatMoney($total > 0 ? $total : 0);
        } elseif ($this->payment_status === 'partial') {
            // Staff enters how much was received.
            if ($service->numeric($this->amount_paid) <= 0 || $service->numeric($this->amount_paid) >= $service->numeric($this->total_amount)) {
                $this->amount_paid = $service->formatMoney(0);
            }
        } else {
            $this->amount_paid = $service->formatMoney(0);
        }

        $this->recalculateBalance();
    }

    public function updatedAmountPaid(): void
    {
        $this->recalculateBalance();
    }

    public function updatedSelectedSupplierIds(): void
    {
        foreach ($this->selectedSupplierIds as $supplierId) {
            $this->ensureSupplierFulfillmentRow((int) $supplierId);
        }
    }

    public function ensureSupplierFulfillmentRow(int $supplierId): void
    {
        $key = (string) $supplierId;

        if (isset($this->supplierFulfillment[$key])) {
            return;
        }

        $this->supplierFulfillment[$key] = $this->defaultSupplierFulfillmentRow();
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSupplierFulfillmentRow(): array
    {
        return [
            'status' => OnlineSaleOrderSupplierAssignment::STATUS_PENDING,
            'carrier_name' => '',
            'tracking_number' => '',
            'shipping_reference' => '',
            'supplier_response_notes' => '',
            'shipping_proof_path' => null,
            'acknowledged_at' => null,
            'shipped_at' => null,
        ];
    }

    public function updatedAssignedUserId($value): void
    {
        if ($value === '' || $value === null) {
            $this->assigned_user_id = null;
        } elseif (is_numeric($value)) {
            $this->assigned_user_id = (int) $value;
        }

        if ($this->assigned_user_id !== null && $this->fulfillment_status === OnlineSaleOrder::FULFILLMENT_AWAITING) {
            $this->fulfillment_status = OnlineSaleOrder::FULFILLMENT_ASSIGNED;
        }
    }

    public function sendSupplierWhatsApp(int $supplierId): void
    {
        if (! $this->canConfirmPayment() || ! $this->orderId) {
            return;
        }

        $order = OnlineSaleOrder::query()->with(['supplierAssignments.supplier', 'items'])->find($this->orderId);

        if ($order === null) {
            return;
        }

        $dispatch = app(OnlineOrderDispatchService::class);
        $assignment = $order->supplierAssignments->firstWhere('supplier_id', $supplierId);

        if ($assignment === null) {
            $dispatch->syncSupplierAssignments($order, [$supplierId], auth()->user());
            $order->load('supplierAssignments.supplier');
            $assignment = $order->supplierAssignments->firstWhere('supplier_id', $supplierId);
        }

        if ($assignment === null) {
            return;
        }

        $dispatch->markAssignmentSent($assignment);
        $url = $dispatch->whatsAppUrlForSupplier($order, $assignment->supplier);

        if ($url !== null) {
            $this->dispatch('online-order-open-external', url: $url);
        }

        $this->loadOrder($this->orderId);
        $this->dispatch('online-sale-orders-saved');
        $this->dispatch('order-dispatch-saved');
    }

    public function sendSupplierEmail(int $supplierId): void
    {
        if (! $this->canConfirmPayment() || ! $this->orderId) {
            return;
        }

        $order = OnlineSaleOrder::query()->with(['supplierAssignments.supplier', 'items'])->find($this->orderId);

        if ($order === null) {
            return;
        }

        $dispatch = app(OnlineOrderDispatchService::class);
        $supplier = $order->supplierAssignments->firstWhere('supplier_id', $supplierId)?->supplier
            ?? $dispatch->suggestSuppliersForOrder($order)->firstWhere('id', $supplierId);

        if ($supplier === null) {
            return;
        }

        $dispatch->syncSupplierAssignments($order, [$supplierId], auth()->user());
        $assignment = $order->supplierAssignments()->where('supplier_id', $supplierId)->first();

        if ($assignment !== null) {
            $dispatch->markAssignmentSent($assignment);
        }

        $url = $dispatch->mailToUrlForSupplier($order, $supplier);

        if ($url !== null) {
            $this->dispatch('online-order-open-external', url: $url);
        }

        $this->loadOrder($this->orderId);
    }

    public function displayPrice(string $amount): string
    {
        $amount = trim($amount);

        if ($amount === '') {
            return $amount;
        }

        $numeric = app(SaleService::class)->numeric($amount);

        return app(ChannelPricingEngine::class)->formatPrice($numeric);
    }

    public function rowImageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    public function formattedOrderDate(): string
    {
        try {
            return \Carbon\Carbon::parse($this->order_date)->format('M j, Y');
        } catch (\Throwable) {
            return $this->order_date;
        }
    }

    public function isAmountPaidEditable(): bool
    {
        return $this->payment_status === 'partial' && $this->canConfirmPayment();
    }

    public function canConfirmPayment(): bool
    {
        if ($this->orderId === null || $this->isLocked()) {
            return false;
        }

        return $this->userCan('edit');
    }

    public function isInvoice(): bool
    {
        return $this->order_status === OnlineSaleOrder::STATUS_CONVERTED;
    }

    public function showFulfillmentSection(): bool
    {
        // Fulfillment / suppliers only on the dispatch queue (after Send to Queue).
        return $this->isInvoice() && $this->isQueued();
    }

    public function isQueued(): bool
    {
        if ($this->orderId === null) {
            return false;
        }

        $order = OnlineSaleOrder::query()->find($this->orderId);

        return $order?->isInDispatchQueue() ?? false;
    }

    public function canManageFulfillment(): bool
    {
        if ($this->orderId === null || ! $this->showFulfillmentSection() || $this->mode === 'view') {
            return false;
        }

        return $this->userCan('edit');
    }

    public function canEditFields(): bool
    {
        return $this->canConfirmPayment() && $this->mode === 'edit';
    }

    public function isLocked(): bool
    {
        if ($this->orderId === null) {
            return false;
        }

        $order = OnlineSaleOrder::query()->find($this->orderId);

        return $order?->isLocked() ?? false;
    }

    public function isReadonly(): bool
    {
        return ! $this->canConfirmPayment() && ! $this->canManageFulfillment();
    }

    /** Order lines and header are view-only unless full edit mode (reserved for future). */
    public function isOrderDetailsReadonly(): bool
    {
        return $this->mode === 'view' || ! $this->canConfirmPayment();
    }

    public function customerBankReceiptIsPreviewable(): bool
    {
        if ($this->orderId === null) {
            return false;
        }

        $order = OnlineSaleOrder::query()->find($this->orderId);

        return $order?->customerBankReceiptIsImage() ?? false;
    }

    public function saveAndClose(): void
    {
        if ($this->persistAdmin()) {
            $this->dispatch('close-form-panel');
        }
    }

    public function printPackingSlip(): void
    {
        if ($this->orderId) {
            $this->dispatch('online-sale-orders-open-print', url: route('admin.online-sale-orders.packing-slip', ['order' => $this->orderId]));
        }
    }

    public function convertToSale(): void
    {
        if (! $this->canConfirmPayment() || ! $this->orderId) {
            return;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            if (! $this->persistAdmin()) {
                return;
            }

            $order = OnlineSaleOrder::query()->findOrFail($this->orderId);
            app(OnlineSaleOrderService::class)->convertToSale($order->fresh(['items']), auth()->user());
            session()->flash(
                'online_order_status',
                __('online_sale_orders.messages.converted_to_invoice', ['number' => $order->order_number]),
            );
            $this->dispatch('online-sale-orders-saved');
            $this->dispatch('online-quotations-saved');
            $this->dispatch('close-form-panel');
            $this->redirect(\App\Filament\Clusters\Sales\OrderDispatchPage::getUrl(), navigate: true);
        } catch (ValidationException $exception) {
            $this->setStatus((string) (collect($exception->errors())->flatten()->first()
                ?? __('online_sale_orders.messages.save_failed')), 'error');
        } catch (\Throwable) {
            $this->setStatus(__('online_sale_orders.messages.save_failed'), 'error');
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    public function saveAndPrint(): void
    {
        if (! $this->canConfirmPayment()) {
            if ($this->orderId) {
                $this->dispatch('online-sale-orders-open-print', url: route('admin.online-sale-orders.print', ['order' => $this->orderId]));
            }

            return;
        }

        if ($this->persistAdmin()) {
            $this->dispatch('online-sale-orders-open-print', url: route('admin.online-sale-orders.print', ['order' => $this->orderId]));
        }
    }

    public function verifyCustomerPaymentProof(): void
    {
        if (! $this->canConfirmPayment() || ! $this->orderId) {
            return;
        }

        if ($this->customer_payment_proof_status !== OnlineSaleOrder::PROOF_SUBMITTED) {
            $this->setStatus(__('online_sale_orders.messages.proof_not_submitted'), 'warning');

            return;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $order = OnlineSaleOrder::query()->findOrFail($this->orderId);
            app(OnlineSaleOrderService::class)->verifyCustomerPaymentProof($order, auth()->user());
            $this->setStatus(__('online_sale_orders.messages.proof_verified'), 'success');
            $this->loadOrder($this->orderId);
            $this->dispatch('online-sale-orders-saved');
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?? __('online_sale_orders.messages.save_failed');
            $this->setStatus((string) $message, 'error');
        } catch (\Throwable) {
            $this->setStatus(__('online_sale_orders.messages.save_failed'), 'error');
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    public function rejectCustomerPaymentProof(): void
    {
        if (! $this->canConfirmPayment() || ! $this->orderId) {
            return;
        }

        if ($this->customer_payment_proof_status !== OnlineSaleOrder::PROOF_SUBMITTED) {
            $this->setStatus(__('online_sale_orders.messages.proof_not_submitted'), 'warning');

            return;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $order = OnlineSaleOrder::query()->findOrFail($this->orderId);
            app(OnlineSaleOrderService::class)->rejectCustomerPaymentProof(
                $order,
                auth()->user(),
                $this->payment_proof_reject_input !== '' ? $this->payment_proof_reject_input : null,
            );
            $this->payment_proof_reject_input = '';
            $this->setStatus(__('online_sale_orders.messages.proof_rejected'), 'success');
            $this->loadOrder($this->orderId);
            $this->dispatch('online-sale-orders-saved');
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?? __('online_sale_orders.messages.save_failed');
            $this->setStatus((string) $message, 'error');
        } catch (\Throwable) {
            $this->setStatus(__('online_sale_orders.messages.save_failed'), 'error');
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    private function persistAdmin(): bool
    {
        if (! $this->orderId) {
            return false;
        }

        if ($this->canManageFulfillment() && $this->isLocked()) {
            return $this->persistFulfillment();
        }

        if (! $this->canConfirmPayment()) {
            return false;
        }

        if (! $this->validateStatuses()) {
            return false;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $order = OnlineSaleOrder::query()->findOrFail($this->orderId);
            $fulfillmentPayload = [];

            foreach ($this->selectedSupplierIds as $supplierId) {
                $key = (string) $supplierId;

                if (isset($this->supplierFulfillment[$key])) {
                    $fulfillmentPayload[$key] = $this->supplierFulfillment[$key];
                }
            }

            app(OnlineSaleOrderService::class)->updateAdmin($order, [
                'order_status' => $this->order_status,
                'fulfillment_status' => $this->fulfillment_status,
                'assigned_user_id' => $this->assigned_user_id,
                'payment_status' => $this->payment_status,
                'payment_method' => $this->payment_method,
                'bank_account_id' => $this->bank_account_id,
                'amount_paid' => $this->amount_paid,
                'admin_notes' => $this->admin_notes,
                'supplier_ids' => $this->selectedSupplierIds,
                'supplier_fulfillment' => $fulfillmentPayload,
                'supplier_proof_uploads' => $this->supplierProofUploads,
            ]);

            $this->setStatus(__('online_sale_orders.messages.updated', ['number' => $order->order_number]), 'success');
            $this->supplierProofUploads = [];
            $this->loadOrder($order->id);
            $this->dispatch('online-sale-orders-saved');
            $this->dispatch('online-quotations-saved');
            $this->dispatch('order-dispatch-saved');

            return true;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? __('online_sale_orders.messages.save_failed');
            $this->setStatus((string) $message, 'error');
        } catch (\Throwable) {
            $this->setStatus(__('online_sale_orders.messages.save_failed'), 'error');
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }

        return false;
    }

    private function persistFulfillment(): bool
    {
        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $order = OnlineSaleOrder::query()->findOrFail($this->orderId);
            $fulfillmentPayload = [];

            foreach ($this->selectedSupplierIds as $supplierId) {
                $key = (string) $supplierId;

                if (isset($this->supplierFulfillment[$key])) {
                    $fulfillmentPayload[$key] = $this->supplierFulfillment[$key];
                }
            }

            app(OnlineSaleOrderService::class)->updateFulfillment($order, [
                'fulfillment_status' => $this->fulfillment_status,
                'assigned_user_id' => $this->assigned_user_id,
                'admin_notes' => $this->admin_notes,
                'supplier_ids' => $this->selectedSupplierIds,
                'supplier_fulfillment' => $fulfillmentPayload,
                'supplier_proof_uploads' => $this->supplierProofUploads,
            ]);

            $this->setStatus(__('online_sale_orders.messages.updated', ['number' => $order->order_number]), 'success');
            $this->supplierProofUploads = [];
            $this->loadOrder($order->id);
            $this->dispatch('online-sale-orders-saved');
            $this->dispatch('order-dispatch-saved');

            return true;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? __('online_sale_orders.messages.save_failed');
            $this->setStatus((string) $message, 'error');
        } catch (\Throwable) {
            $this->setStatus(__('online_sale_orders.messages.save_failed'), 'error');
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }

        return false;
    }

    public function render(): View
    {
        $customer = null;

        if ($this->orderId) {
            $customer = OnlineSaleOrder::query()->with('customer')->find($this->orderId)?->customer;
        }

        $staffOptions = User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'label' => $user->name,
            ])
            ->all();

        $suggestedSuppliers = [];

        if ($this->orderId) {
            $orderModel = OnlineSaleOrder::query()->with(['supplierAssignments.supplier'])->find($this->orderId);

            if ($orderModel !== null) {
                $dispatch = app(OnlineOrderDispatchService::class);
                $suggested = $dispatch->suggestSuppliersForOrder($orderModel)->keyBy('id');

                foreach ($orderModel->supplierAssignments as $assignment) {
                    $suggested->put($assignment->supplier_id, $assignment->supplier);
                }

                foreach ($suggested as $supplier) {
                    $assignment = $orderModel->supplierAssignments->firstWhere('supplier_id', $supplier->id);
                    $isAssigned = $assignment !== null
                        || in_array($supplier->id, $this->selectedSupplierIds, true);
                    $suggestedSuppliers[] = [
                        'id' => $supplier->id,
                        'label' => $supplier->displayBusinessName(),
                        'is_assigned' => $isAssigned,
                        'status' => $assignment?->status,
                        'proof_url' => $assignment?->shippingProofUrl(),
                    ];
                }
            }
        }

        return view('livewire.online-sale-orders.form', [
            'selectedCustomer' => $customer,
            'staffOptions' => $staffOptions,
            'suggestedSuppliers' => $suggestedSuppliers,
            'supplierStatusOptions' => collect(OnlineSaleOrderSupplierAssignment::ADMIN_STATUSES)
                ->mapWithKeys(fn (string $status): array => [$status => __('order_dispatch.supplier_statuses.' . $status)])
                ->all(),
            'fulfillmentStatusOptions' => collect(OnlineSaleOrder::FULFILLMENT_STATUSES)
                ->mapWithKeys(fn (string $status): array => [$status => __('order_dispatch.fulfillment_statuses.' . $status)])
                ->all(),
            'paymentOptions' => collect(['paid', 'partial'])
                ->mapWithKeys(fn (string $status): array => [$status => __('pos.payment_statuses.' . $status)])
                ->all(),
            'paymentMethodOptions' => collect(SaleService::PAYMENT_METHODS)
                ->mapWithKeys(fn (string $method): array => [$method => __('pos.payment_methods.' . $method)])
                ->all(),
            'orderStatusOptions' => collect(OnlineSaleOrder::STATUSES)
                ->mapWithKeys(fn (string $status): array => [$status => __('online_sale_orders.order_statuses.' . $status)])
                ->all(),
            'bankAccountOptions' => app(SalesBankAccountService::class)->options(),
        ]);
    }

    private function loadOrder(int $orderId): void
    {
        $order = OnlineSaleOrder::query()->find($orderId);

        if ($order === null) {
            return;
        }

        $state = app(OnlineSaleOrderService::class)->toFormState($order);
        $this->order_number = (string) $state['order_number'];
        $this->order_date = (string) $state['order_date'];
        $this->locale = (string) $state['locale'];
        $this->order_status = (string) $state['order_status'];
        $this->fulfillment_status = (string) ($state['fulfillment_status'] ?? OnlineSaleOrder::FULFILLMENT_AWAITING);
        $this->assigned_user_id = $state['assigned_user_id'] ? (int) $state['assigned_user_id'] : null;
        $this->payment_status = (string) $state['payment_status'];
        $this->customer_payment_method = (string) $state['customer_payment_method'];
        $this->customer_payment_note = (string) $state['customer_payment_note'];
        $this->customer_payment_reference = (string) ($state['customer_payment_reference'] ?? '');
        $this->customer_payment_proof_status = (string) ($state['customer_payment_proof_status'] ?? '');
        $this->customer_bank_receipt_url = (string) ($state['customer_bank_receipt_url'] ?? '');
        $this->payment_proof_rejection_note = (string) ($state['payment_proof_rejection_note'] ?? '');
        $this->payment_method = (string) $state['payment_method'];
        if ($this->payment_method === '' && filled($state['customer_payment_method'] ?? null)) {
            $this->payment_method = (string) $state['customer_payment_method'];
        }
        $this->bank_account_id = $state['bank_account_id'] ? (int) $state['bank_account_id'] : null;
        $this->amount_paid = (string) $state['amount_paid'];
        $this->subtotal = (string) $state['subtotal'];
        $this->total_amount = (string) $state['total_amount'];
        if ($this->payment_status === 'paid' && app(SaleService::class)->numeric($this->amount_paid) <= 0) {
            $this->amount_paid = $this->total_amount;
        }
        $this->balance_due = (string) $state['balance_due'];
        $this->ship_contact_name = (string) $state['ship_contact_name'];
        $this->ship_phone = (string) $state['ship_phone'];
        $this->ship_address = (string) $state['ship_address'];
        $this->ship_city = (string) $state['ship_city'];
        $this->delivery_method_name = (string) ($state['delivery_method_name'] ?? '');
        $this->shipping_charge_mode_label = (string) ($state['shipping_charge_mode_label'] ?? '');
        $this->cod_min_advance_percent = (string) ($state['cod_min_advance_percent'] ?? '');
        $this->full_advance_required = (bool) ($state['full_advance_required'] ?? false);
        $this->delivery_customer_notice = (string) ($state['delivery_customer_notice'] ?? '');
        $this->customer_notes = (string) $state['customer_notes'];
        $this->admin_notes = (string) $state['admin_notes'];
        $this->linked_sale_number = (string) $state['sale_number'];
        $this->converted_by_name = (string) ($state['converted_by_name'] ?? '');
        $this->converted_at = (string) ($state['converted_at'] ?? '');
        $this->queued_at_display = (string) ($state['queued_at'] ?? '');
        $this->queue_document = (string) ($state['queue_document'] ?? '');
        $this->selectedSupplierIds = array_map('intval', $state['supplier_assignment_ids'] ?? []);
        $this->rows = $state['rows'];

        $order->load('supplierAssignments');
        $this->supplierFulfillment = [];

        foreach ($order->supplierAssignments as $assignment) {
            $key = (string) $assignment->supplier_id;
            $status = $assignment->status;

            if ($status === OnlineSaleOrderSupplierAssignment::STATUS_SENT) {
                $status = OnlineSaleOrderSupplierAssignment::STATUS_ACKNOWLEDGED;
            }

            if (! in_array($status, OnlineSaleOrderSupplierAssignment::ADMIN_STATUSES, true)) {
                $status = OnlineSaleOrderSupplierAssignment::STATUS_PENDING;
            }

            $this->supplierFulfillment[$key] = [
                'status' => $status,
                'carrier_name' => (string) ($assignment->carrier_name ?? ''),
                'tracking_number' => (string) ($assignment->tracking_number ?? ''),
                'shipping_reference' => (string) ($assignment->shipping_reference ?? ''),
                'supplier_response_notes' => (string) ($assignment->supplier_response_notes ?? ''),
                'shipping_proof_path' => $assignment->shipping_proof_path,
                'acknowledged_at' => $assignment->acknowledged_at?->format('Y-m-d H:i'),
                'shipped_at' => $assignment->shipped_at?->format('Y-m-d H:i'),
            ];
        }

        foreach ($this->selectedSupplierIds as $supplierId) {
            $this->ensureSupplierFulfillmentRow((int) $supplierId);
        }

        if ($this->payment_method === '' && $this->customer_payment_method !== '') {
            $this->payment_method = $this->customer_payment_method;
        }

        $this->recalculateBalance();
    }

    private function recalculateBalance(): void
    {
        if ($this->recalculatingTotals) {
            return;
        }

        $this->recalculatingTotals = true;
        $saleService = app(SaleService::class);
        $total = $saleService->numeric($this->total_amount !== '' ? $this->total_amount : '0');
        $paid = $saleService->numeric($this->amount_paid !== '' ? $this->amount_paid : '0');

        if ($this->payment_status === 'paid') {
            $this->amount_paid = $this->total_amount;
            $paid = $total;
        } elseif ($this->payment_status === 'unpaid') {
            $this->amount_paid = $saleService->formatMoney(0);
            $paid = 0.0;
        }

        $this->balance_due = $saleService->formatMoney(max(0, $total - $paid));
        $this->recalculatingTotals = false;
    }

    private function validateStatuses(): bool
    {
        if ($this->order_status === '') {
            $this->setStatus(__('online_sale_orders.messages.order_status_required'), 'warning');

            return false;
        }

        if ($this->payment_method === '') {
            $this->setStatus(__('pos.messages.payment_method_required'), 'warning');

            return false;
        }

        if ($this->payment_status === '') {
            $this->setStatus(__('pos.messages.payment_status_required'), 'warning');

            return false;
        }

        if ($this->payment_method === 'bank_transfer') {
            $banks = app(SalesBankAccountService::class)->options();

            if ($banks !== [] && $this->bank_account_id === null) {
                $this->setStatus(__('pos.messages.bank_account_required'), 'warning');

                return false;
            }
        }

        return true;
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('online-order-form-busy', saving: $this->saving);
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->hasPermission('sales.' . $action);
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function resetForm(): void
    {
        $this->reset([
            'orderId',
            'order_number',
            'order_date',
            'locale',
            'order_status',
            'fulfillment_status',
            'assigned_user_id',
            'payment_status',
            'customer_payment_method',
            'customer_payment_note',
            'customer_payment_reference',
            'customer_payment_proof_status',
            'customer_bank_receipt_url',
            'payment_proof_rejection_note',
            'payment_proof_reject_input',
            'payment_method',
            'bank_account_id',
            'amount_paid',
            'subtotal',
            'total_amount',
            'balance_due',
            'ship_contact_name',
            'ship_phone',
            'ship_address',
            'ship_city',
            'customer_notes',
            'admin_notes',
            'linked_sale_number',
            'converted_by_name',
            'converted_at',
            'queued_at_display',
            'queue_document',
            'selectedSupplierIds',
            'supplierFulfillment',
            'supplierProofUploads',
            'supplierAssignmentRows',
            'rows',
            'statusMessage',
        ]);
        $this->statusType = 'success';
        $this->amount_paid = '0';
    }
}
