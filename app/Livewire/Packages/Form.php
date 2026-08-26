<?php

namespace App\Livewire\Packages;

use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPackage;
use App\Services\Catalog\ProductPackageService;
use App\Support\FriendlyError;
use App\Support\LivewireImagePreview;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Form extends Component
{
    use AuthorizesSensitiveActions;
    use WithFileUploads;

    #[Locked]
    public ?int $packageId = null;

    #[Locked]
    public string $mode = 'create';

    #[Locked]
    public string $package_number = '';

    public string $name_en = '';

    public string $name_ur = '';

    public string $slug = '';

    public string $status = ProductPackage::STATUS_ACTIVE;

    public string $discount_percent = '0';

    public string $sort_order = '0';

    public string $notes = '';

    public ?TemporaryUploadedFile $imageUpload = null;

    public ?string $imagePreviewUrl = null;

    #[Locked]
    public ?string $storedImagePath = null;

    public string $category_search = '';

    /** @var list<int> */
    public array $category_ids = [];

    public string $product_search = '';

    /** @var list<array{product_id: int, quantity: string, product_name: string, product_code: string}> */
    public array $items = [];

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function openForm(?int $packageId = null, string $mode = 'create'): void
    {
        $normalizedMode = in_array($mode, ['create', 'edit', 'view'], true) ? $mode : 'create';
        $this->authorizeAny(
            $packageId === null
                ? 'catalog.packages.create'
                : ($normalizedMode === 'view' ? ['catalog.packages.view', 'catalog.packages.edit'] : 'catalog.packages.edit'),
        );

        $this->resetForm();
        $this->mode = $normalizedMode;
        $this->packageId = $packageId;

        if ($packageId) {
            $this->loadPackage($packageId);
        } else {
            $this->package_number = app(ProductPackageService::class)->nextPackageNumber();
        }

        $this->dispatchBusyState();
    }

    public function handlePanelAction(string $action): void
    {
        if ($this->mode === 'view' && in_array($action, ['save_close', 'save_next'], true)) {
            $this->dispatchBusyState();

            return;
        }

        match ($action) {
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
    }

    public function updatedImageUpload(): void
    {
        $this->authorizeAny($this->mutationPermission());

        $this->validate([
            'imageUpload' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($this->imageUpload === null) {
            return;
        }

        $this->imagePreviewUrl = LivewireImagePreview::url($this->imageUpload, true);

        $filename = pathinfo($this->imageUpload->getClientOriginalName(), PATHINFO_FILENAME);
        $cleanName = Str::title(str_replace(['-', '_'], ' ', $filename));

        if (filled($cleanName) && ! filled(trim($this->name_en))) {
            $this->name_en = $cleanName;

            if (! filled(trim($this->slug))) {
                $this->slug = Str::slug($cleanName);
            }
        }
    }

    public function addCategory(int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $id = (int) $id;

        if ($id <= 0 || in_array($id, $this->category_ids, true)) {
            $this->category_search = '';

            return;
        }

        if (! Category::query()->whereKey($id)->where('status', 'active')->exists()) {
            $this->category_search = '';

            return;
        }

        $this->category_ids[] = $id;
        $this->category_search = '';
    }

    public function removeCategory(int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $removeId = (int) $id;
        $this->category_ids = array_values(array_filter(
            array_map(static fn ($selectedId): int => (int) $selectedId, $this->category_ids),
            static fn (int $selectedId): bool => $selectedId !== $removeId,
        ));
    }

    public function addProduct(int $productId): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $productId = (int) $productId;

        if ($productId <= 0 || $this->hasProduct($productId)) {
            $this->product_search = '';

            return;
        }

        $product = Product::query()->where('status', 'active')->find($productId);

        if ($product === null) {
            $this->setStatus(__('agricart.packages.messages.product_inactive'), 'danger');
            $this->product_search = '';

            return;
        }

        $this->items[] = [
            'product_id' => $product->id,
            'quantity' => '1',
            'product_name' => (string) $product->name_en,
            'product_code' => (string) ($product->code ?? $product->sku ?? ''),
        ];
        $this->product_search = '';
    }

    public function removeItem(int $index): void
    {
        if ($this->isReadonly()) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('packages-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('packages-saved');
            $this->resetForm();
            $this->package_number = app(ProductPackageService::class)->nextPackageNumber();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        return view('livewire.packages.form', [
            'categoryOptions' => $this->searchCategoryOptions(),
            'selectedCategories' => $this->selectedCategoryLabels(),
            'productResults' => $this->searchProductResults(),
        ]);
    }

    private function persist(): bool
    {
        if ($this->saving || $this->isReadonly()) {
            return false;
        }

        $this->authorizeAny($this->mutationPermission());

        $missing = [];

        if (! filled(trim($this->name_en))) {
            $missing[] = __('agricart.packages.fields.name_en');
        }

        if ($this->normalizedItems() === []) {
            $missing[] = __('agricart.packages.fields.items');
        }

        if ($missing !== []) {
            $this->setStatus(
                __('agricart.packages.messages.required_fields', ['fields' => implode(', ', $missing)]),
                'danger',
            );
            $this->dispatchBusyState();

            return false;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $service = app(ProductPackageService::class);
            $payload = $this->buildPayload();
            $items = $this->normalizedItems();
            $categoryIds = array_map('intval', $this->category_ids);
            $image = $this->imageUpload;

            if ($this->packageId) {
                $package = ProductPackage::query()->findOrFail($this->packageId);
                $package = $service->update($package, $payload, $items, $categoryIds, $image);
            } else {
                $package = $service->create($payload, $items, $categoryIds, $image);
                $this->packageId = $package->id;
                $this->mode = 'edit';
            }

            $this->package_number = $package->package_number;
            $this->slug = $package->slug;
            $this->storedImagePath = $package->image_path;
            $this->imagePreviewUrl = $package->imageUrl();
            $this->imageUpload = null;
            $this->setStatus(__('agricart.packages.messages.saved'), 'success');

            return true;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('agricart.packages.messages.validation_failed'), 'danger');

            return false;
        } catch (\Throwable $exception) {
            $this->setStatus(FriendlyError::message($exception), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(): array
    {
        return [
            'package_number' => filled($this->package_number) ? $this->package_number : null,
            'slug' => filled(trim($this->slug)) ? trim($this->slug) : null,
            'name_en' => trim($this->name_en),
            'name_ur' => filled(trim($this->name_ur)) ? trim($this->name_ur) : null,
            'status' => $this->status ?: ProductPackage::STATUS_ACTIVE,
            'discount_percent' => is_numeric($this->discount_percent) ? (float) $this->discount_percent : 0,
            'sort_order' => is_numeric($this->sort_order) ? (int) $this->sort_order : 0,
            'notes' => filled(trim($this->notes)) ? trim($this->notes) : null,
        ];
    }

    /**
     * @return list<array{product_id: int, quantity: float|string}>
     */
    private function normalizedItems(): array
    {
        $rows = [];

        foreach ($this->items as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $quantity = (string) ($row['quantity'] ?? '0');

            if ($productId <= 0) {
                continue;
            }

            $rows[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
            ];
        }

        return $rows;
    }

    private function loadPackage(int $packageId): void
    {
        $package = ProductPackage::query()
            ->with(['items.product', 'categories'])
            ->findOrFail($packageId);

        $this->package_number = $package->package_number;
        $this->name_en = $package->name_en;
        $this->name_ur = $package->name_ur ?? '';
        $this->slug = $package->slug;
        $this->status = $package->status;
        $this->discount_percent = (string) $package->discount_percent;
        $this->sort_order = (string) $package->sort_order;
        $this->notes = $package->notes ?? '';
        $this->storedImagePath = $package->image_path;
        $this->imagePreviewUrl = $package->imageUrl();
        $this->category_ids = $package->categories->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $this->items = $package->items->map(static function ($item): array {
            $product = $item->product;

            return [
                'product_id' => (int) $item->product_id,
                'quantity' => (string) $item->quantity,
                'product_name' => (string) ($product?->name_en ?? ''),
                'product_code' => (string) ($product?->code ?? $product?->sku ?? ''),
            ];
        })->all();
    }

    private function resetForm(): void
    {
        $this->packageId = null;
        $this->mode = 'create';
        $this->package_number = '';
        $this->name_en = '';
        $this->name_ur = '';
        $this->slug = '';
        $this->status = ProductPackage::STATUS_ACTIVE;
        $this->discount_percent = '0';
        $this->sort_order = '0';
        $this->notes = '';
        $this->imageUpload = null;
        $this->imagePreviewUrl = null;
        $this->storedImagePath = null;
        $this->category_search = '';
        $this->category_ids = [];
        $this->product_search = '';
        $this->items = [];
        $this->saving = false;
        $this->statusMessage = null;
        $this->statusType = 'success';
    }

    private function hasProduct(int $productId): bool
    {
        foreach ($this->items as $row) {
            if ((int) ($row['product_id'] ?? 0) === $productId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function searchCategoryOptions(): array
    {
        $term = trim($this->category_search);

        if (mb_strlen($term) < 2 || ! $this->canAccessForm()) {
            return [];
        }

        $needle = '%'.$term.'%';

        return Category::query()
            ->where('status', 'active')
            ->where(function ($query) use ($needle): void {
                $query->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle)
                    ->orWhere('slug', 'like', $needle)
                    ->orWhere('code', 'like', $needle);
            })
            ->whereNotIn('id', $this->category_ids)
            ->orderBy('name_en')
            ->limit(12)
            ->get()
            ->mapWithKeys(fn (Category $category): array => [
                $category->id => $category->name_en.(filled($category->code) ? ' ('.$category->code.')' : ''),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function selectedCategoryLabels(): array
    {
        if ($this->category_ids === [] || ! $this->canAccessForm()) {
            return [];
        }

        return Category::query()
            ->whereIn('id', $this->category_ids)
            ->orderBy('name_en')
            ->get()
            ->mapWithKeys(fn (Category $category): array => [
                $category->id => $category->name_en.(filled($category->code) ? ' ('.$category->code.')' : ''),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, label: string}>
     */
    private function searchProductResults(): array
    {
        $term = trim($this->product_search);

        if (mb_strlen($term) < 2 || ! $this->canAccessForm()) {
            return [];
        }

        $needle = '%'.$term.'%';
        $excludeIds = collect($this->items)->pluck('product_id')->map(fn ($id): int => (int) $id)->all();

        return Product::query()
            ->where('status', 'active')
            ->where(function ($query) use ($needle): void {
                $query->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle)
                    ->orWhere('code', 'like', $needle)
                    ->orWhere('sku', 'like', $needle)
                    ->orWhere('slug', 'like', $needle);
            })
            ->when($excludeIds !== [], fn ($query) => $query->whereNotIn('id', $excludeIds))
            ->orderBy('name_en')
            ->limit(12)
            ->get(['id', 'name_en', 'code', 'sku'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'label' => $product->name_en.(filled($product->code ?? $product->sku)
                    ? ' ('.($product->code ?: $product->sku).')'
                    : ''),
            ])
            ->all();
    }

    private function mutationPermission(): string
    {
        return $this->packageId === null ? 'catalog.packages.create' : 'catalog.packages.edit';
    }

    private function canAccessForm(): bool
    {
        return $this->canAccessAny([
            'catalog.packages.view',
            'catalog.packages.create',
            'catalog.packages.edit',
        ]);
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('packages-form-busy', saving: $this->saving);
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
