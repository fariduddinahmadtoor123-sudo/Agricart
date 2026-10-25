<?php

namespace App\Livewire\Labels;

use App\Models\CompanyProfile;
use App\Models\Product;
use App\Models\User;
use App\Services\Printing\PrintProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PrintLabelsForm extends Component
{
    private const MAX_SELECTED_PRODUCTS = 50;

    public string $search = '';

    #[Locked]
    public array $selectedProductIds = [];

    #[Locked]
    public array $storeNames = ['en' => '', 'ur' => ''];

    public array $copies = [];

    public array $enabledVariants = [];

    public string $profileKey = '';

    public string $labelLanguage = 'en';

    public array $fields = [
        'store_name',
        'product_name',
        'system_code',
        'brand',
        'unit',
        'packing_unit',
        'attributes',
        'controls',
        'category',
        'required_qty',
        'reorder_qty',
        'printed_code',
    ];

    public function mount(): void
    {
        $this->profileKey = app(PrintProfileService::class)->defaultProductLabel()?->key ?? '';
    }

    public function openForm(): void
    {
        $this->authorizeAccess();
        $this->reset('search', 'selectedProductIds', 'copies', 'enabledVariants');
        $this->labelLanguage = 'en';
        $this->fields = array_keys($this->fieldOptions());
        $this->profileKey = app(PrintProfileService::class)->defaultProductLabel()?->key ?? '';
        $company = CompanyProfile::current();
        $this->storeNames = [
            'en' => (string) ($company->business_name_en ?: $company->business_name_ur),
            'ur' => (string) ($company->business_name_ur ?: $company->business_name_en),
        ];
    }

    public function addProduct(int $productId): void
    {
        $this->authorizeAccess();

        if (in_array($productId, $this->selectedProductIds, true)
            || count($this->selectedProductIds) >= self::MAX_SELECTED_PRODUCTS) {
            return;
        }

        $exists = Product::query()
            ->whereKey($productId)
            ->whereNotNull('label_code')
            ->exists();

        if (! $exists) {
            return;
        }

        $this->selectedProductIds[] = $productId;
        $this->enabledVariants[$productId] = $this->defaultVariants();
        $this->copies[$productId] = [
            'qr_en' => 1,
            'barcode_en' => 1,
            'qr_ur' => 1,
            'barcode_ur' => 1,
        ];
        $this->search = '';
    }

    public function updatedLabelLanguage(string $language): void
    {
        $this->authorizeAccess();
        $this->labelLanguage = in_array($language, ['en', 'ur', 'both'], true) ? $language : 'en';

        foreach ($this->selectedProductIds as $productId) {
            $this->enabledVariants[$productId] = $this->defaultVariants();
        }
    }

    public function removeProduct(int $productId): void
    {
        $this->authorizeAccess();
        $this->selectedProductIds = array_values(array_filter(
            $this->selectedProductIds,
            fn (int $id): bool => $id !== $productId,
        ));
        unset($this->enabledVariants[$productId], $this->copies[$productId]);
    }

    #[Computed]
    public function searchResults(): Collection
    {
        $this->authorizeAccess();
        $needle = trim($this->search);

        if ($needle === '') {
            return collect();
        }

        return Product::query()
            ->whereNotNull('label_code')
            ->whereNotIn('id', $this->selectedProductIds)
            ->where(function ($query) use ($needle): void {
                $like = '%'.$needle.'%';
                $query->where('name_en', 'like', $like)
                    ->orWhere('name_ur', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('label_code', 'like', $like);
            })
            ->orderBy('name_en')
            ->limit(12)
            ->get(['id', 'name_en', 'name_ur', 'code', 'label_code']);
    }

    #[Computed]
    public function selectedProducts(): Collection
    {
        $products = Product::query()
            ->whereIn('id', $this->selectedProductIds)
            ->get(['id', 'name_en', 'name_ur', 'code', 'label_code'])
            ->keyBy('id');

        return collect($this->selectedProductIds)
            ->map(fn (int $id) => $products->get($id))
            ->filter()
            ->values();
    }

    public function render(): View
    {
        return view('livewire.labels.print-labels-form', [
            'profiles' => app(PrintProfileService::class)->productLabels(),
            'variantOptions' => $this->variantOptions(),
            'fieldOptions' => $this->fieldOptions(),
            'storeNames' => $this->storeNames,
        ]);
    }

    private function authorizeAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof User
                && (
                    $user->isSuperAdmin()
                    || \App\Services\Access\ModulePageAccess::can($user, 'catalog', 'labels', 'view')
                ),
            403,
        );
    }

    private function variantOptions(): array
    {
        $variants = [
            'qr_en' => __('agricart.labels.sticker.qr_en'),
            'barcode_en' => __('agricart.labels.sticker.barcode_en'),
            'qr_ur' => __('agricart.labels.sticker.qr_ur'),
            'barcode_ur' => __('agricart.labels.sticker.barcode_ur'),
        ];

        return array_filter(
            $variants,
            fn (string $key): bool => $this->labelLanguage === 'both'
                || str_ends_with($key, '_'.$this->labelLanguage),
            ARRAY_FILTER_USE_KEY,
        );
    }

    private function defaultVariants(): array
    {
        return match ($this->labelLanguage) {
            'ur' => ['barcode_ur'],
            'both' => ['barcode_en', 'barcode_ur'],
            default => ['barcode_en'],
        };
    }

    private function fieldOptions(): array
    {
        return [
            'store_name' => __('agricart.labels.batch.fields.store_name'),
            'product_name' => __('agricart.labels.batch.fields.product_name'),
            'system_code' => __('agricart.labels.batch.fields.system_code'),
            'brand' => __('agricart.labels.batch.fields.brand'),
            'unit' => __('agricart.labels.batch.fields.unit'),
            'packing_unit' => __('agricart.labels.batch.fields.packing_unit'),
            'attributes' => __('agricart.labels.batch.fields.attributes'),
            'controls' => __('agricart.labels.batch.fields.controls'),
            'category' => __('agricart.labels.batch.fields.category'),
            'required_qty' => __('agricart.labels.batch.fields.required_qty'),
            'reorder_qty' => __('agricart.labels.batch.fields.reorder_qty'),
            'printed_code' => __('agricart.labels.batch.fields.printed_code'),
        ];
    }
}
