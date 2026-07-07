<?php

namespace App\Livewire\OnlineSaleOrders;

use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\User;
use App\Services\PricingControls\ChannelPricingEngine;
use App\Services\Sales\OnlineSaleOrderService;
use App\Services\Sales\SaleService;
use App\Services\Sales\SalesBankAccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $orderId = null;

    public string $mode = 'view';

    public string $order_number = '';

    public string $order_date = '';

    public string $locale = 'en';

    public string $order_status = '';

    public string $payment_status = '';

    public string $customer_payment_method = '';

    public string $customer_payment_note = '';

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

    public string $customer_notes = '';

    public string $admin_notes = '';

    public string $linked_sale_number = '';

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
            $this->amount_paid = $this->total_amount !== '0' ? $this->total_amount : $service->formatMoney(0);
        } elseif ($this->payment_status === 'unpaid') {
            $this->amount_paid = $service->formatMoney(0);
        }

        $this->recalculateBalance();
    }

    public function updatedAmountPaid(): void
    {
        $this->recalculateBalance();
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
        return $this->payment_status === 'partial' && $this->canEditFields();
    }

    public function canEditFields(): bool
    {
        return $this->mode === 'edit' && ! $this->isLocked();
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
        return $this->mode === 'view' || ! $this->canEditFields();
    }

    public function saveAndClose(): void
    {
        if ($this->persistAdmin()) {
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndPrint(): void
    {
        if ($this->mode === 'view' || ! $this->canEditFields()) {
            if ($this->orderId) {
                $this->dispatch('online-sale-orders-open-print', url: route('admin.online-sale-orders.print', ['order' => $this->orderId]));
            }

            return;
        }

        if ($this->persistAdmin()) {
            $this->dispatch('online-sale-orders-open-print', url: route('admin.online-sale-orders.print', ['order' => $this->orderId]));
        }
    }

    private function persistAdmin(): bool
    {
        if (! $this->canEditFields()) {
            return false;
        }

        if (! $this->userCan('edit') || ! $this->orderId) {
            return false;
        }

        if (! $this->validateStatuses()) {
            return false;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $order = OnlineSaleOrder::query()->findOrFail($this->orderId);
            app(OnlineSaleOrderService::class)->updateAdmin($order, [
                'order_status' => $this->order_status,
                'payment_status' => $this->payment_status,
                'payment_method' => $this->payment_method,
                'bank_account_id' => $this->bank_account_id,
                'amount_paid' => $this->amount_paid,
                'admin_notes' => $this->admin_notes,
            ]);

            $this->setStatus(__('online_sale_orders.messages.updated', ['number' => $order->order_number]), 'success');
            $this->loadOrder($order->id);
            $this->dispatch('online-sale-orders-saved');

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

        return view('livewire.online-sale-orders.form', [
            'selectedCustomer' => $customer,
            'paymentOptions' => collect(SaleService::PAYMENT_STATUSES)
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
        $this->payment_status = (string) $state['payment_status'];
        $this->customer_payment_method = (string) $state['customer_payment_method'];
        $this->customer_payment_note = (string) $state['customer_payment_note'];
        $this->payment_method = (string) $state['payment_method'];
        $this->bank_account_id = $state['bank_account_id'] ? (int) $state['bank_account_id'] : null;
        $this->amount_paid = (string) $state['amount_paid'];
        $this->subtotal = (string) $state['subtotal'];
        $this->total_amount = (string) $state['total_amount'];
        $this->balance_due = (string) $state['balance_due'];
        $this->ship_contact_name = (string) $state['ship_contact_name'];
        $this->ship_phone = (string) $state['ship_phone'];
        $this->ship_address = (string) $state['ship_address'];
        $this->ship_city = (string) $state['ship_city'];
        $this->customer_notes = (string) $state['customer_notes'];
        $this->admin_notes = (string) $state['admin_notes'];
        $this->linked_sale_number = (string) $state['sale_number'];
        $this->rows = $state['rows'];

        if ($this->payment_method === '' && $this->customer_payment_method !== '') {
            $this->payment_method = $this->customer_payment_method;
        }
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
            'payment_status',
            'customer_payment_method',
            'customer_payment_note',
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
            'rows',
            'statusMessage',
        ]);
        $this->statusType = 'success';
        $this->amount_paid = '0';
    }
}
