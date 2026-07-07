<?php

namespace App\Livewire\SaleReturns;

use App\Models\Customer;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\PricingControls\ChannelPricingEngine;
use App\Services\Sales\SaleReturnService;
use App\Services\Sales\SaleService;
use App\Services\Sales\SalesBankAccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $returnId = null;

    public string $mode = 'create';

    public string $return_number = '';

    public ?int $sale_id = null;

    public string $sale_number = '';

    public ?int $customer_id = null;

    public string $return_date = '';

    public string $payment_method = '';

    public ?int $bank_account_id = null;

    public string $payment_status = '';

    public string $amount_paid = '0';

    public string $notes = '';

    public string $refund_subtotal = '0';

    public string $refund_total = '0';

    public string $balance_due = '0';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public bool $saving = false;

    public string $processedByName = '';

    private bool $recalculatingTotals = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('sale-return-form-open')]
    public function openForm(?int $returnId = null, string $mode = 'create'): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->returnId = $returnId;

        if ($returnId) {
            $this->loadReturn($returnId);
        } else {
            $this->return_date = now()->format('Y-m-d');
            $this->processedByName = auth()->user()?->name ?? '—';
        }

        $this->recalculateTotals();
    }

    #[On('sale-return-form-action')]
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

    public function updatedRows(): void
    {
        $this->recalculateTotals();
    }

    public function updated(string $propertyName): void
    {
        if (str_starts_with($propertyName, 'rows')) {
            $this->recalculateTotals();
        }
    }

    public function updatedAmountPaid(): void
    {
        $this->recalculateTotals();
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
            $this->amount_paid = $this->refund_total !== '0' ? $this->refund_total : $service->formatMoney(0);
        } elseif ($this->payment_status === 'unpaid') {
            $this->amount_paid = $service->formatMoney(0);
        }

        $this->recalculateTotals();
    }

    public function clearLoadedInvoice(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->customer_id = null;
        $this->sale_id = null;
        $this->sale_number = '';
        $this->rows = [];
        $this->recalculateTotals();
    }

    public function loadInvoice(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        try {
            $returnService = app(SaleReturnService::class);
            $sale = $returnService->findSaleForReturn(null, $this->sale_number);
            $this->sale_id = $sale->id;
            $this->sale_number = $sale->sale_number;
            $this->customer_id = $sale->customer_id !== null ? (int) $sale->customer_id : null;

            $this->rows = $returnService->rowsFromSale($sale);
            $this->recalculateTotals();
            $this->setStatus(__('sale_returns.messages.invoice_loaded', ['number' => $sale->sale_number]), 'success');
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? __('sale_returns.messages.load_failed');
            $this->setStatus((string) $message, 'error');
        }
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

    public function formattedReturnDate(): string
    {
        try {
            return \Carbon\Carbon::parse($this->return_date)->format('M j, Y');
        } catch (\Throwable) {
            return $this->return_date;
        }
    }

    public function isAmountPaidEditable(): bool
    {
        return $this->payment_status === 'partial' && ! $this->isReadonly();
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function saveAndClose(): void
    {
        $this->persist(close: true);
    }

    public function saveAndPrint(): void
    {
        if ($this->isReadonly()) {
            if ($this->returnId) {
                $this->dispatch('sale-returns-open-print', url: route('admin.sale-returns.print', ['saleReturn' => $this->returnId]));
            }

            return;
        }

        $return = $this->persist(close: false);

        if (! $return instanceof SaleReturn) {
            return;
        }

        $this->dispatch('sale-returns-open-print', url: route('admin.sale-returns.print', $return));
    }

    public function render(): View
    {
        return view('livewire.sale-returns.form', [
            'selectedCustomer' => $this->customer_id ? Customer::query()->find($this->customer_id) : null,
            'paymentOptions' => collect(SaleService::PAYMENT_STATUSES)
                ->mapWithKeys(fn (string $status): array => [$status => __('pos.payment_statuses.' . $status)])
                ->all(),
            'paymentMethodOptions' => collect(SaleService::PAYMENT_METHODS)
                ->mapWithKeys(fn (string $method): array => [$method => __('pos.payment_methods.' . $method)])
                ->all(),
            'bankAccountOptions' => app(SalesBankAccountService::class)->options(),
        ]);
    }

    private function persist(bool $close): ?SaleReturn
    {
        if ($this->isReadonly()) {
            return null;
        }

        if (! $this->userCan($this->returnId ? 'edit' : 'create')) {
            return null;
        }

        if (! $this->validateStatuses()) {
            return null;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $returnService = app(SaleReturnService::class);
            $this->rows = $returnService->recalculateRows($this->rows);
            $payload = $this->buildPayload();

            if ($this->returnId) {
                $return = SaleReturn::query()->findOrFail($this->returnId);
                $return = $returnService->update($return, $payload);
                $this->setStatus(__('sale_returns.messages.updated', ['number' => $return->return_number]), 'success');
            } else {
                $return = $returnService->create($payload);
                $this->returnId = $return->id;
                $this->return_number = $return->return_number;
                $this->setStatus(__('sale_returns.messages.created', ['number' => $return->return_number]), 'success');
            }

            $this->loadReturn($return->id);
            $this->dispatch('sale-returns-saved');

            if ($close) {
                $this->dispatch('close-form-panel');
            }

            return $return;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? __('sale_returns.messages.save_failed');
            $this->setStatus((string) $message, 'error');
        } catch (\Throwable) {
            $this->setStatus(__('sale_returns.messages.save_failed'), 'error');
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(): array
    {
        return [
            'return_number' => filled($this->return_number) ? $this->return_number : null,
            'sale_id' => $this->sale_id,
            'customer_id' => $this->customer_id,
            'return_date' => $this->return_date,
            'payment_method' => $this->payment_method,
            'bank_account_id' => $this->payment_method === 'bank_transfer' ? $this->bank_account_id : null,
            'payment_status' => $this->payment_status,
            'amount_paid' => $this->amount_paid,
            'notes' => $this->notes,
            'rows' => $this->rows,
        ];
    }

    private function loadReturn(int $returnId): void
    {
        $return = SaleReturn::query()->with('processedBy')->find($returnId);

        if ($return === null) {
            return;
        }

        $state = app(SaleReturnService::class)->toFormState($return);
        $this->return_number = (string) $state['return_number'];
        $this->sale_id = (int) $state['sale_id'];
        $this->sale_number = (string) $state['sale_number'];
        $this->customer_id = $state['customer_id'] ? (int) $state['customer_id'] : null;
        $this->return_date = (string) $state['return_date'];
        $this->payment_method = (string) $state['payment_method'];
        $this->bank_account_id = isset($state['bank_account_id']) ? (int) $state['bank_account_id'] : null;
        $this->payment_status = (string) $state['payment_status'];
        $this->refund_subtotal = (string) $state['refund_subtotal'];
        $this->refund_total = (string) $state['refund_total'];
        $this->amount_paid = (string) $state['amount_paid'];
        $this->balance_due = (string) $state['balance_due'];
        $this->notes = (string) $state['notes'];
        $this->rows = $state['rows'];
        $this->processedByName = $return->processedBy?->name ?? '—';
    }

    private function recalculateTotals(): void
    {
        if ($this->recalculatingTotals) {
            return;
        }

        $this->recalculatingTotals = true;

        $saleService = app(SaleService::class);
        $returnService = app(SaleReturnService::class);
        $this->rows = $returnService->recalculateRows($this->rows);
        $subtotal = $returnService->refundSubtotalFromRows($this->rows);
        $total = $subtotal;
        $paid = $saleService->numeric($this->amount_paid !== '' ? $this->amount_paid : '0');

        $this->refund_subtotal = $saleService->formatMoney($subtotal);
        $this->refund_total = $saleService->formatMoney($total);

        if ($this->payment_status === 'paid') {
            $this->amount_paid = $this->refund_total;
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
        if ($this->sale_id === null) {
            $this->setStatus(__('sale_returns.messages.sale_required'), 'warning');

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
        $this->dispatch('sale-return-form-busy', saving: $this->saving);
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
            'returnId',
            'return_number',
            'sale_id',
            'sale_number',
            'customer_id',
            'return_date',
            'payment_method',
            'bank_account_id',
            'payment_status',
            'amount_paid',
            'notes',
            'refund_subtotal',
            'refund_total',
            'balance_due',
            'rows',
            'processedByName',
            'statusMessage',
        ]);
        $this->statusType = 'success';
        $this->amount_paid = '0';
        $this->refund_subtotal = '0';
        $this->refund_total = '0';
        $this->balance_due = '0';
    }
}
