<?php

namespace App\Livewire\PurchasePaymentSummaries;

use App\Models\PurchasePaymentSummary;
use App\Models\User;
use App\Services\Purchasing\PurchasePaymentSummaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $summaryId = null;

    public string $mode = 'create';

    public string $summary_number = '';

    public string $summary_date = '';

    public string $paper_size = 'a4';

    public string $locale = 'en';

    public string $notes = '';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /** @var list<array{label: string, amount: string}> */
    public array $paymentSources = [];

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('purchase-payment-summary-form-open')]
    public function openForm(?int $summaryId = null, string $mode = 'create'): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->summaryId = $summaryId;

        if ($summaryId) {
            $this->loadSummary($summaryId);
        }
    }

    #[On('purchase-payment-summary-form-action')]
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

    public function addManualRow(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->rows[] = app(PurchasePaymentSummaryService::class)->emptyManualRow();
    }

    public function removeRow(int $index): void
    {
        if ($this->isReadonly()) {
            return;
        }

        if (! isset($this->rows[$index]) || ! ($this->rows[$index]['is_manual'] ?? false)) {
            return;
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function setLineStatus(int $index, string $status): void
    {
        if ($this->isReadonly() || ! isset($this->rows[$index])) {
            return;
        }

        if (! in_array($status, PurchasePaymentSummaryService::LINE_STATUSES, true)) {
            return;
        }

        $current = (string) ($this->rows[$index]['line_status'] ?? '');
        $this->rows[$index]['line_status'] = $current === $status ? '' : $status;
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('purchase-payment-summaries-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndPrint(): void
    {
        $summary = $this->persist();

        if (! $summary instanceof PurchasePaymentSummary) {
            return;
        }

        $this->dispatch('purchase-payment-summaries-saved');
        $url = route('admin.purchase-payment-summaries.print', $summary);
        $this->js('window.open(' . json_encode($url) . ", '_blank')");
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        return view('livewire.purchase-payment-summaries.form', [
            'paperSizeOptions' => $this->paperSizeOptions(),
            'localeOptions' => $this->localeOptions(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function localeOptions(): array
    {
        return collect(PurchasePaymentSummaryService::LOCALES)
            ->mapWithKeys(fn (string $locale): array => [
                $locale => __('purchase_payment_summary.locales.' . $locale),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function paperSizeOptions(): array
    {
        return collect(PurchasePaymentSummaryService::PAPER_SIZES)
            ->mapWithKeys(fn (string $size): array => [
                $size => __('purchase_payment_summary.paper_sizes.' . $size),
            ])
            ->all();
    }

    private function loadSummary(int $summaryId): void
    {
        $summary = PurchasePaymentSummary::query()->with('items')->find($summaryId);

        if ($summary === null) {
            return;
        }

        $state = app(PurchasePaymentSummaryService::class)->toFormState($summary);
        $this->summary_number = (string) $state['summary_number'];
        $this->summary_date = (string) $state['summary_date'];
        $this->paper_size = (string) $state['paper_size'];
        $this->locale = (string) $state['locale'];
        $this->notes = (string) $state['notes'];
        $this->rows = $state['rows'];
        $this->paymentSources = $state['payment_sources'];
    }

    private function persist(): ?PurchasePaymentSummary
    {
        if ($this->isReadonly()) {
            return null;
        }

        if (! $this->userCan($this->summaryId ? 'edit' : 'create')) {
            return null;
        }

        $this->saving = true;
        $this->statusMessage = null;

        try {
            $service = app(PurchasePaymentSummaryService::class);
            $payload = [
                'summary_number' => $this->summary_number,
                'summary_date' => $this->summary_date,
                'paper_size' => $this->paper_size,
                'locale' => $this->locale,
                'notes' => $this->notes,
                'payment_sources' => $this->paymentSources,
                'items' => $this->rows,
            ];

            if ($this->summaryId) {
                $summary = PurchasePaymentSummary::query()->findOrFail($this->summaryId);
                $summary = $service->update($summary, $payload);
                $this->setStatus(__('purchase_payment_summary.messages.updated', ['number' => $summary->summary_number]), 'success');

                return $summary;
            }

            $summary = $service->create($payload);
            $this->summary_number = $summary->summary_number;
            $this->summaryId = $summary->id;
            $this->setStatus(__('purchase_payment_summary.messages.created', ['number' => $summary->summary_number]), 'success');

            return $summary;
        } catch (ValidationException $exception) {
            $this->setStatus(collect($exception->errors())->flatten()->first() ?? __('purchase_payment_summary.messages.save_failed'), 'error');

            return null;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    private function resetForm(): void
    {
        $service = app(PurchasePaymentSummaryService::class);

        $this->summaryId = null;
        $this->mode = 'create';
        $this->summary_number = $service->nextSummaryNumber();
        $this->summary_date = now()->format('Y-m-d');
        $this->paper_size = 'a4';
        $this->locale = app()->getLocale() === 'ur' ? 'ur' : 'en';
        $this->notes = '';
        $this->rows = $service->supplierRowsForNewForm();
        $this->paymentSources = $service->defaultPaymentSources();
        $this->statusMessage = null;
        $this->statusType = 'success';
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('purchase-payment-summary-form-busy', saving: $this->saving);
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

        return $user->hasPermission('purchasing.' . $action);
    }
}
