<?php

namespace App\Livewire\PurchasePlanning;

use App\Models\Product;
use App\Models\PurchasePlanningSheet;
use App\Models\User;
use App\Services\Purchasing\PurchasePlanningService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $sheetId = null;

    public string $mode = 'create';

    public string $planning_number = '';

    public string $paper_size = 'a4';

    public string $locale = 'en';

    public string $notes = '';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public string $product_search = '';

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('purchase-planning-form-open')]
    public function openForm(?int $sheetId = null, string $mode = 'create'): void
    {
        $this->resetForm();
        $this->mode = $mode;
        $this->sheetId = $sheetId;

        if ($sheetId) {
            $this->loadSheet($sheetId);
        }
    }

    #[On('purchase-planning-form-action')]
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

    public function updatedProductSearch(): void
    {
        $this->product_search = trim($this->product_search);
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

        $this->rows[] = app(PurchasePlanningService::class)->rowFromProduct($product);
        $this->product_search = '';
    }

    public function scanProduct(): void
    {
        if ($this->isReadonly() || $this->product_search === '') {
            return;
        }

        $product = app(PurchasePlanningService::class)->findProductByScan($this->product_search);

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
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('purchase-planning-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndPrint(): void
    {
        $sheet = $this->persist();

        if (! $sheet instanceof PurchasePlanningSheet) {
            return;
        }

        $this->dispatch('purchase-planning-saved');
        $url = route('admin.purchase-planning.print', $sheet);
        $this->js('window.open(' . json_encode($url) . ", '_blank')");
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        return view('livewire.purchase-planning.form', [
            'productResults' => $this->productSearchResults(),
            'paperSizeOptions' => $this->paperSizeOptions(),
            'localeOptions' => $this->localeOptions(),
        ]);
    }

    /**
     * @return array<int, array{id: int, label: string, image_url: ?string}>
     */
    private function productSearchResults(): array
    {
        if ($this->product_search === '') {
            return [];
        }

        return app(PurchasePlanningService::class)->searchProducts($this->product_search, $this->locale);
    }

    /**
     * @return array<string, string>
     */
    private function localeOptions(): array
    {
        return collect(PurchasePlanningService::LOCALES)
            ->mapWithKeys(fn (string $locale): array => [
                $locale => __('purchase_planning.locales.' . $locale),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function paperSizeOptions(): array
    {
        return collect(PurchasePlanningService::PAPER_SIZES)
            ->mapWithKeys(fn (string $size): array => [
                $size => __('purchase_planning.paper_sizes.' . $size),
            ])
            ->all();
    }

    private function loadSheet(int $sheetId): void
    {
        $sheet = PurchasePlanningSheet::query()->with('items')->find($sheetId);

        if ($sheet === null) {
            return;
        }

        $state = app(PurchasePlanningService::class)->toFormState($sheet);
        $this->planning_number = (string) $state['planning_number'];
        $this->paper_size = (string) $state['paper_size'];
        $this->locale = (string) $state['locale'];
        $this->notes = (string) $state['notes'];
        $this->rows = $state['rows'];
    }

    private function persist(): ?PurchasePlanningSheet
    {
        if ($this->isReadonly()) {
            return null;
        }

        if (! $this->userCan($this->sheetId ? 'edit' : 'create')) {
            return null;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $service = app(PurchasePlanningService::class);
            $payload = [
                'planning_number' => filled($this->planning_number) ? $this->planning_number : null,
                'paper_size' => $this->paper_size,
                'locale' => $this->locale,
                'notes' => $this->notes,
                'items' => $this->rows,
            ];

            if ($this->sheetId) {
                $sheet = PurchasePlanningSheet::query()->findOrFail($this->sheetId);
                $sheet = $service->update($sheet, $payload);
                $this->setStatus(__('purchase_planning.messages.updated', ['number' => $sheet->planning_number]), 'success');
            } else {
                $sheet = $service->create($payload);
                $this->sheetId = $sheet->id;
                $this->planning_number = $sheet->planning_number;
                $this->setStatus(__('purchase_planning.messages.created', ['number' => $sheet->planning_number]), 'success');
            }

            return $sheet;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? __('purchase_planning.messages.save_failed');
            $this->setStatus((string) $message, 'error');
        } catch (\Throwable) {
            $this->setStatus(__('purchase_planning.messages.save_failed'), 'error');
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }

        return null;
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

    public function rowImageUrl(?string $path): ?string
    {
        return filled($path) ? Storage::disk('public')->url($path) : null;
    }

    public function rowDisplayName(array $row): string
    {
        return app(PurchasePlanningService::class)->rowDisplayName($row, $this->locale);
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

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function resetForm(): void
    {
        $this->reset([
            'sheetId',
            'planning_number',
            'paper_size',
            'locale',
            'notes',
            'rows',
            'product_search',
            'saving',
            'statusMessage',
        ]);

        $this->mode = 'create';
        $this->paper_size = 'a4';
        $this->locale = app(PurchasePlanningService::class)->normalizeLocale(app()->getLocale());
        $this->statusType = 'success';
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('purchase-planning-form-busy', saving: $this->saving);
    }
}
