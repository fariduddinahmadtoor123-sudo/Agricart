<?php

namespace App\Livewire\Purchases;

use App\Models\CompanyProfile;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Purchasing\PurchaseService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?int $purchaseId = null;

    public string $mode = 'create';

    public string $purchase_number = '';

    public ?int $supplier_id = null;

    public string $supplier_search = '';

    public ?int $purchaser_id = null;

    public string $locale = 'en';

    public string $purchase_date = '';

    public string $delivery_status = '';

    public string $payment_status = '';

    public string $dispute_status = '';

    public string $supplier_balance_snapshot = '0';

    public string $supplier_balance_display = '0';

    public string $subtotal = '0';

    public string $amount_paid = '0';

    public string $total_amount = '0';

    public string $balance_due = '0';

    public string $notes = '';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public string $product_search = '';

    public ?TemporaryUploadedFile $vendorBillUpload = null;

    public ?string $storedVendorBillPath = null;

    public ?string $vendorBillPreviewUrl = null;

    public bool $remove_vendor_bill = false;

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('purchase-form-open')]
    public function openForm(?int $purchaseId = null, string $mode = 'create'): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->purchaseId = $purchaseId;

        if ($purchaseId) {
            $this->loadPurchase($purchaseId);
            $this->syncSupplierBalance();
        } else {
            $this->purchase_date = now()->format('Y-m-d');
            $this->purchaser_id = auth()->id();
        }
    }

    #[On('purchase-form-action')]
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

    public function updatedSupplierId(): void
    {
        $this->syncSupplierBalance();

        if (! $this->purchaseId) {
            $this->supplier_balance_snapshot = $this->supplier_balance_display;
        }
    }

    public function selectSupplier(int $supplierId): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->supplier_id = $supplierId;
        $this->supplier_search = '';
        $this->syncSupplierBalance();

        if (! $this->purchaseId) {
            $this->supplier_balance_snapshot = $this->supplier_balance_display;
        }
    }

    public function clearSupplier(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->supplier_id = null;
        $this->supplier_search = '';
        $this->supplier_balance_snapshot = '0';
        $this->supplier_balance_display = '0';
    }

    public function updatedProductSearch(): void
    {
        $this->product_search = trim($this->product_search);
    }

    public function updatedAmountPaid(): void
    {
        $this->recalculateTotals();
    }

    public function updatedPaymentStatus(): void
    {
        if ($this->payment_status === Purchase::PAYMENT_PAID) {
            $this->recalculateTotals();
            $this->amount_paid = $this->total_amount;
        }

        $this->recalculateTotals();
    }

    public function updatedRows(): void
    {
        $this->recalculateTotals();
    }

    public function updatedVendorBillUpload(): void
    {
        $this->validateOnly('vendorBillUpload', [
            'vendorBillUpload' => ['nullable', 'image', 'max:10240'],
        ]);

        $this->vendorBillPreviewUrl = $this->vendorBillUpload?->temporaryUrl();
        $this->remove_vendor_bill = false;
    }

    public function removeVendorBill(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->vendorBillUpload = null;
        $this->vendorBillPreviewUrl = null;
        $this->storedVendorBillPath = null;
        $this->remove_vendor_bill = true;
    }

    public function addProduct(int $productId): void
    {
        if ($this->isReadonly()) {
            return;
        }

        if ($this->hasProduct($productId)) {
            $this->product_search = '';

            return;
        }

        $product = Product::query()->where('status', 'active')->find($productId);

        if ($product === null) {
            return;
        }

        $this->rows[] = app(PurchaseService::class)->rowFromProduct($product);
        $this->product_search = '';
        $this->recalculateTotals();
    }

    public function scanProduct(): void
    {
        if ($this->isReadonly() || $this->product_search === '') {
            return;
        }

        $product = app(PurchaseService::class)->findProductByScan($this->product_search);

        if ($product !== null) {
            $this->addProduct($product->id);

            return;
        }

        $results = $this->productSearchResults();

        if (count($results) === 1) {
            $this->addProduct($results[0]['id']);
        }
    }

    public function removeRow(int $index): void
    {
        if ($this->isReadonly()) {
            return;
        }

        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
        $this->recalculateTotals();
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('purchases-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndPrint(): void
    {
        $purchase = $this->persist();

        if (! $purchase instanceof Purchase) {
            return;
        }

        $this->dispatch('purchases-saved');
        $url = route('admin.purchases.print', $purchase);
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
        $buyerAddress = $company->formattedPrimaryAddress() ?? '';
        $buyerName = (string) ($company->localized('business_name') ?? '');
        $primaryPhone = $company->primaryPhone();
        $buyerPhone = (string) ($primaryPhone['phone_number'] ?? '');
        app()->setLocale($previousLocale);

        return view('livewire.purchases.form', [
            'productResults' => $this->productSearchResults(),
            'supplierResults' => $this->supplierSearchResults(),
            'selectedSupplier' => $this->selectedSupplier(),
            'localeOptions' => $this->localeOptions(),
            'deliveryOptions' => $this->statusOptions('delivery'),
            'paymentOptions' => $this->statusOptions('payment'),
            'disputeOptions' => $this->statusOptions('dispute'),
            'purchaserOptions' => $this->purchaserOptions(),
            'creatorName' => $this->creatorName(),
            'formattedPurchaseDate' => $this->formattedPurchaseDate(),
            'buyerName' => $buyerName,
            'buyerAddress' => $buyerAddress,
            'buyerPhone' => $buyerPhone,
        ]);
    }

    public function rowImageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    public function rowDisplayName(array $row): string
    {
        return app(PurchaseService::class)->rowDisplayName($row, $this->locale);
    }

    /**
     * @return array<int, array{id: int, label: string, image_url: ?string}>
     */
    private function productSearchResults(): array
    {
        if ($this->product_search === '') {
            return [];
        }

        return app(PurchaseService::class)->searchProducts($this->product_search, $this->locale);
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    private function supplierSearchResults(): array
    {
        if ($this->supplier_id !== null || $this->supplier_search === '') {
            return [];
        }

        return app(PurchaseService::class)->searchSuppliers($this->supplier_search);
    }

    private function selectedSupplier(): ?Supplier
    {
        if ($this->supplier_id === null) {
            return null;
        }

        return Supplier::query()->find($this->supplier_id);
    }

    /**
     * @return array<string, string>
     */
    private function localeOptions(): array
    {
        return collect(PurchaseService::LOCALES)
            ->mapWithKeys(fn (string $locale): array => [
                $locale => __('purchases.locales.' . $locale),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(string $type): array
    {
        $statuses = match ($type) {
            'delivery' => PurchaseService::DELIVERY_STATUSES,
            'payment' => PurchaseService::PAYMENT_STATUSES,
            'dispute' => PurchaseService::DISPUTE_STATUSES,
            default => [],
        };

        return collect($statuses)
            ->mapWithKeys(fn (string $status): array => [
                $status => __('purchases.statuses_short.' . $type . '.' . $status),
            ])
            ->all();
    }

    private function creatorName(): string
    {
        if ($this->purchaseId) {
            $purchase = Purchase::query()->with('creator')->find($this->purchaseId);

            if ($purchase?->creator) {
                return (string) $purchase->creator->name;
            }
        }

        $user = auth()->user();

        return $user instanceof User ? (string) $user->name : '—';
    }

    private function formattedPurchaseDate(): string
    {
        if ($this->purchase_date === '') {
            return now()->format('d M Y');
        }

        try {
            return \Illuminate\Support\Carbon::parse($this->purchase_date)->format('d M Y');
        } catch (\Throwable) {
            return $this->purchase_date;
        }
    }

    public function supplierDisplayName(?Supplier $supplier): string
    {
        if ($supplier === null) {
            return '—';
        }

        if ($this->locale === 'ur' && filled($supplier->business_name_ur)) {
            return (string) $supplier->business_name_ur;
        }

        return (string) $supplier->business_name_en;
    }

    public function supplierAddressLine(Supplier $supplier): string
    {
        $address = $this->locale === 'ur' && filled($supplier->address_ur)
            ? trim((string) $supplier->address_ur)
            : trim((string) ($supplier->address_en ?: $supplier->address_ur));

        $city = $this->supplierCity($supplier);

        return implode(', ', array_filter([$address, $city]));
    }

    public function supplierCity(Supplier $supplier): string
    {
        if ($this->locale === 'ur' && filled($supplier->city_ur)) {
            return trim((string) $supplier->city_ur);
        }

        return trim((string) ($supplier->city_en ?: $supplier->city_ur));
    }

    public function supplierPhone(Supplier $supplier): ?string
    {
        $contact = $supplier->primaryContact();
        $phone = $contact['mobile_number'] ?? null;

        return filled($phone) ? (string) $phone : null;
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

    private function loadPurchase(int $purchaseId): void
    {
        $purchase = Purchase::query()->with('items')->find($purchaseId);

        if ($purchase === null) {
            return;
        }

        $state = app(PurchaseService::class)->toFormState($purchase);
        $this->purchase_number = (string) $state['purchase_number'];
        $this->supplier_id = (int) $state['supplier_id'];
        $this->purchaser_id = $state['purchaser_id'] ? (int) $state['purchaser_id'] : null;
        $this->locale = (string) $state['locale'];
        $this->purchase_date = (string) $state['purchase_date'];
        $this->delivery_status = (string) $state['delivery_status'];
        $this->payment_status = (string) $state['payment_status'];
        $this->dispute_status = (string) ($state['dispute_status'] ?? Purchase::DISPUTE_OK);
        $this->supplier_balance_snapshot = (string) $state['supplier_balance_snapshot'];
        $this->subtotal = (string) $state['subtotal'];
        $this->amount_paid = (string) $state['amount_paid'];
        $this->total_amount = (string) $state['total_amount'];
        $this->balance_due = (string) $state['balance_due'];
        $this->storedVendorBillPath = $state['vendor_bill_path'];
        $this->vendorBillPreviewUrl = filled($this->storedVendorBillPath)
            ? Storage::disk('public')->url($this->storedVendorBillPath)
            : null;
        $this->notes = (string) $state['notes'];
        $this->rows = $state['rows'];
    }

    private function persist(): ?Purchase
    {
        if ($this->isReadonly()) {
            return null;
        }

        if (! $this->userCan($this->purchaseId ? 'edit' : 'create')) {
            return null;
        }

        $this->recalculateTotals();

        if (! $this->validateStatuses()) {
            return null;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $service = app(PurchaseService::class);
            $payload = [
                'purchase_number' => filled($this->purchase_number) ? $this->purchase_number : null,
                'supplier_id' => $this->supplier_id,
                'purchaser_id' => $this->purchaser_id,
                'locale' => $this->locale,
                'purchase_date' => $this->purchase_date,
                'delivery_status' => $this->delivery_status,
                'payment_status' => $this->payment_status,
                'dispute_status' => $this->dispute_status,
                'supplier_balance_snapshot' => $this->supplier_balance_snapshot,
                'subtotal' => $this->subtotal,
                'amount_paid' => $this->amount_paid,
                'total_amount' => $this->total_amount,
                'balance_due' => $this->balance_due,
                'notes' => $this->notes,
                'items' => $this->rows,
            ];

            if ($this->purchaseId) {
                $purchase = Purchase::query()->findOrFail($this->purchaseId);
                $purchase = $service->update(
                    $purchase,
                    $payload,
                    $this->vendorBillUpload,
                    $this->remove_vendor_bill,
                );
                $this->setStatus(__('purchases.messages.updated', ['number' => $purchase->purchase_number]), 'success');
            } else {
                $purchase = $service->create($payload, $this->vendorBillUpload);
                $this->purchaseId = $purchase->id;
                $this->purchase_number = $purchase->purchase_number;
                $this->setStatus(__('purchases.messages.created', ['number' => $purchase->purchase_number]), 'success');
            }

            $this->vendorBillUpload = null;
            $this->remove_vendor_bill = false;
            $this->storedVendorBillPath = $purchase->vendor_bill_path;
            $this->vendorBillPreviewUrl = $purchase->vendorBillUrl();
            $this->syncSupplierBalance();

            return $purchase;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? __('purchases.messages.save_failed');
            $this->setStatus((string) $message, 'error');
        } catch (\Throwable) {
            $this->setStatus(__('purchases.messages.save_failed'), 'error');
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }

        return null;
    }

    private function recalculateTotals(): void
    {
        $service = app(PurchaseService::class);
        $subtotal = 0.0;

        foreach ($this->rows as $index => $row) {
            $row['line_total'] = $service->calculateLineTotal($row);
            $row['cost_price'] = $service->calculateCostPrice($row);
            $this->rows[$index] = $row;

            $line = (float) str_replace(',', '', (string) ($row['line_total'] ?: '0'));
            $subtotal += $line;
        }

        $this->subtotal = number_format($subtotal, 2, '.', '');
        $this->total_amount = $this->subtotal;

        if ($this->payment_status === Purchase::PAYMENT_PAID) {
            $this->amount_paid = $this->total_amount;
        }

        $paid = (float) str_replace(',', '', $this->amount_paid ?: '0');
        $this->balance_due = number_format(max(0, $subtotal - $paid), 2, '.', '');
    }

    private function validateStatuses(): bool
    {
        $missing = [];

        if ($this->delivery_status === '') {
            $missing[] = __('purchases.fields.delivery_status');
        }

        if ($this->payment_status === '') {
            $missing[] = __('purchases.fields.payment_status');
        }

        if ($this->dispute_status === '') {
            $missing[] = __('purchases.fields.dispute_status');
        }

        if ($missing === []) {
            return true;
        }

        $this->setStatus(__('purchases.messages.status_required', [
            'fields' => implode(', ', $missing),
        ]), 'warning');

        return false;
    }

    private function syncSupplierBalance(): void
    {
        $supplier = $this->selectedSupplier();

        if ($supplier === null) {
            $this->supplier_balance_display = '0';

            return;
        }

        $this->supplier_balance_display = number_format(
            app(PurchaseService::class)->supplierBalance($supplier),
            2,
            '.',
            '',
        );
    }

    private function hasProduct(int $productId): bool
    {
        foreach ($this->rows as $row) {
            if ((int) ($row['product_id'] ?? 0) === $productId) {
                return true;
            }
        }

        return false;
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

        return \App\Services\Access\ModulePageAccess::can($user, 'purchasing', 'purchases', $action);
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function resetForm(): void
    {
        $this->reset([
            'purchaseId',
            'purchase_number',
            'supplier_id',
            'supplier_search',
            'purchaser_id',
            'locale',
            'purchase_date',
            'delivery_status',
            'payment_status',
            'dispute_status',
            'supplier_balance_snapshot',
            'supplier_balance_display',
            'subtotal',
            'amount_paid',
            'total_amount',
            'balance_due',
            'notes',
            'rows',
            'product_search',
            'vendorBillUpload',
            'storedVendorBillPath',
            'vendorBillPreviewUrl',
            'remove_vendor_bill',
            'saving',
            'statusMessage',
        ]);

        $this->mode = 'create';
        $this->locale = app(PurchaseService::class)->normalizeLocale(app()->getLocale());
        $this->delivery_status = '';
        $this->payment_status = '';
        $this->dispute_status = '';
        $this->statusType = 'success';
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('purchase-form-busy', saving: $this->saving);
    }
}
