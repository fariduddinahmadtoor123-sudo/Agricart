<?php

namespace App\Livewire\PurchasePaymentSummaries;

use App\Models\CompanyProfile;
use App\Models\PurchasePaymentSummary;
use App\Models\User;
use App\Services\Purchasing\PurchasePaymentSummaryService;
use App\Services\Purchasing\PurchaseService;
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

    public ?int $purchaser_id = null;

    public string $notes = '';

    public string $supplier_search = '';

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

    public function updatedSupplierSearch(): void
    {
        $this->supplier_search = trim($this->supplier_search);
    }

    public function addSupplier(int $supplierId): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $service = app(PurchasePaymentSummaryService::class);
        $row = $service->rowFromSupplierId($supplierId);

        if ($row === null) {
            return;
        }

        $this->rows[] = $row;
        $this->supplier_search = '';
        $this->statusMessage = null;
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
        if ($this->isReadonly() || ! isset($this->rows[$index])) {
            return;
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function addPaymentSource(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        if (count($this->paymentSources) >= PurchasePaymentSummaryService::MAX_PAYMENT_SOURCES) {
            return;
        }

        $this->paymentSources[] = ['label' => '', 'amount' => ''];
    }

    public function removePaymentSource(int $index): void
    {
        if ($this->isReadonly() || ! isset($this->paymentSources[$index])) {
            return;
        }

        if (count($this->paymentSources) <= 1) {
            $this->paymentSources = app(PurchasePaymentSummaryService::class)->defaultPaymentSources();

            return;
        }

        unset($this->paymentSources[$index]);
        $this->paymentSources = array_values($this->paymentSources);
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

    public function encodeRowPayment(int $index): void
    {
        if ($this->isReadonly() || ! isset($this->rows[$index])) {
            return;
        }

        $service = app(PurchasePaymentSummaryService::class);
        $this->rows[$index]['payment_amount'] = $service->encodePaymentAmount(
            (string) ($this->rows[$index]['payment_amount'] ?? ''),
        );
    }

    public function encodeSourcePayment(int $index): void
    {
        if ($this->isReadonly() || ! isset($this->paymentSources[$index])) {
            return;
        }

        $service = app(PurchasePaymentSummaryService::class);
        $this->paymentSources[$index]['amount'] = $service->encodePaymentAmount(
            (string) ($this->paymentSources[$index]['amount'] ?? ''),
        );
    }

    public function rowDisplayName(array $row): string
    {
        if ($this->locale === 'ur' && filled($row['name_ur'] ?? null)) {
            return (string) $row['name_ur'];
        }

        return (string) ($row['name_en'] ?: ($row['name_ur'] ?? ''));
    }

    public function rowPartyMeta(array $row): string
    {
        $parts = array_filter([
            filled($row['phone'] ?? null) ? trim((string) $row['phone']) : null,
            filled($row['address'] ?? null) ? trim((string) $row['address']) : null,
        ]);

        return implode(' · ', $parts);
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
        $company = CompanyProfile::current();
        $locale = app(PurchaseService::class)->normalizeLocale($this->locale);
        $previousLocale = app()->getLocale();
        app()->setLocale($locale);

        $storeName = (string) ($company->localized('business_name') ?? '');
        $storeAddress = $company->formattedPrimaryAddress() ?? '';
        $primaryPhone = $company->primaryPhone();
        $storePhone = (string) ($primaryPhone['phone_number'] ?? '');

        app()->setLocale($previousLocale);

        $service = app(PurchasePaymentSummaryService::class);
        $supplierTotal = $service->totalSupplierPayments($this->rows);
        $sourceTotal = $service->totalSourcePayments($this->paymentSources);
        $balanceTotal = $sourceTotal - $supplierTotal;

        return view('livewire.purchase-payment-summaries.form', [
            'paperSizeOptions' => $this->paperSizeOptions(),
            'localeOptions' => $this->localeOptions(),
            'supplierResults' => $this->supplierSearchResults(),
            'storeName' => $storeName,
            'storeAddress' => $storeAddress,
            'storePhone' => $storePhone,
            'formattedSummaryDate' => $this->formattedSummaryDate(),
            'totalSupplierPayments' => $service->formatEncodedAmount($supplierTotal),
            'totalSourcePayments' => $service->formatEncodedAmount($sourceTotal),
            'balanceRemaining' => $service->formatEncodedAmount(abs($balanceTotal)),
            'canAddPaymentSource' => count($this->paymentSources) < PurchasePaymentSummaryService::MAX_PAYMENT_SOURCES,
            'purchaserOptions' => $this->purchaserOptions(),
            'creatorName' => $this->creatorName(),
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

    /**
     * @return array<int, array{id: int, label: string}>
     */
    private function supplierSearchResults(): array
    {
        if ($this->isReadonly() || $this->supplier_search === '') {
            return [];
        }

        return app(PurchasePaymentSummaryService::class)->searchSuppliers($this->supplier_search);
    }

    private function formattedSummaryDate(): string
    {
        if ($this->summary_date === '') {
            return now()->format('d M Y');
        }

        try {
            return \Illuminate\Support\Carbon::parse($this->summary_date)->format('d M Y');
        } catch (\Throwable) {
            return $this->summary_date;
        }
    }

    /**
     * @return array<int, string>
     */
    private function purchaserOptions(): array
    {
        return User::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private function creatorName(): string
    {
        if (! $this->summaryId) {
            $user = auth()->user();

            return $user instanceof User ? (string) $user->name : '—';
        }

        $summary = PurchasePaymentSummary::query()->with('creator')->find($this->summaryId);

        return (string) ($summary?->creator?->name ?? '—');
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
        $this->purchaser_id = filled($state['purchaser_id'] ?? null) ? (int) $state['purchaser_id'] : null;
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
                'purchaser_id' => $this->purchaser_id,
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
        $this->purchaser_id = auth()->id();
        $this->notes = '';
        $this->supplier_search = '';
        $this->rows = [];
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

        return \App\Services\Access\ModulePageAccess::can($user, 'purchasing', 'purchase-payment-summary', $action);
    }
}
