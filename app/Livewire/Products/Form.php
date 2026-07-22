<?php

namespace App\Livewire\Products;

use App\Enums\CategoryAiStatus;
use App\Filament\Forms\CategoryForm;
use App\Models\Brand;
use App\Models\CatalogAttribute;
use App\Models\CatalogControl;
use App\Models\Category;
use App\Models\DeliveryMethod;
use App\Models\PackingUnit;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Services\Catalog\CategoryVisualMappingService;
use App\Services\Catalog\ProductEnrichmentService;
use App\Services\Catalog\ProductService;
use App\Services\Media\MediaLibraryService;
use App\Services\Media\OptimizedImageStorage;
use App\Services\PricingControls\QuantityPricingEngine;
use App\Support\FriendlyError;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    private const GALLERY_SLOT_COUNT = 4;

    public ?int $productId = null;

    public string $mode = 'create';

    public ?int $parent_id = null;

    public string $parent_search = '';

    /** @var list<int|null> */
    public array $hierarchySlots = [null, null, null, null, null];

    /** @var list<int> */
    public array $display_category_ids = [];

    public string $display_category_search = '';

    public ?TemporaryUploadedFile $imageUpload = null;

    public ?string $imagePreviewUrl = null;

    public ?string $storedImagePath = null;

    /** @var array<int, TemporaryUploadedFile|null> */
    public array $galleryUploads = [];

    /** @var array<int, string|null> */
    public array $galleryPreviewUrls = [];

    /** @var array<int, string|null> */
    public array $storedGalleryPaths = [];

    /** @var list<string> */
    public array $galleryOverflowPaths = [];

    public string $slug = '';

    public string $name_en = '';

    public string $name_ur = '';

    public ?int $brand_id = null;

    public string $brand_search = '';

    /** @var list<array{attribute_id: int|null, value: string}> */
    public array $attributeRows = [
        ['attribute_id' => null, 'value' => ''],
    ];

    /** @var array<int, string> */
    public array $attributeSearches = [''];

    public ?int $unit_id = null;

    public string $unit_search = '';

    public string $unit_qty = '';

    public ?int $pack_unit_id = null;

    public string $pack_unit_search = '';

    public string $pack_qty = '';

    public string $required_qty = '';

    public string $alert_qty = '';

    public string $retail_price = '';

    public string $wholesale_price = '';

    public string $super_wholesale_price = '';

    public string $distributor_price = '';

    /** @var array<string, int|string> */
    public array $price_tier_quantities = [];

    public string $control_search = '';

    /** @var list<array{id: int, name_en: string, name_ur: string, value_en: string, value_ur: string}> */
    public array $selectedControls = [];

    public string $delivery_search = '';

    /** @var list<array{id: int, name_en: string, name_ur: string}> */
    public array $selectedDeliveryMethods = [];

    public string $short_description_en = '';

    public string $short_description_ur = '';

    public string $description_en = '';

    public string $description_ur = '';

    public string $usage_guidelines_en = '';

    public string $usage_guidelines_ur = '';

    public string $benefits_en = '';

    public string $benefits_ur = '';

    public string $warnings_en = '';

    public string $warnings_ur = '';

    public string $buying_guide_en = '';

    public string $buying_guide_ur = '';

    public string $seo_title_en = '';

    public string $seo_title_ur = '';

    public string $seo_description_en = '';

    public string $seo_description_ur = '';

    public string $seo_keywords_en = '';

    public string $seo_keywords_ur = '';

    public string $seo_focus_keyword_en = '';

    public string $seo_focus_keyword_ur = '';

    public string $search_terms_json = '[]';

    public string $hs_code = '';

    public string $customs_notes_en = '';

    public string $customs_notes_ur = '';

    public string $import_notes_en = '';

    public string $import_notes_ur = '';

    public string $export_notes_en = '';

    public string $export_notes_ur = '';

    public string $code = '';

    public string $sku = '';

    public string $status = 'active';

    public string $label_code = '';

    public string $ai_status = 'none';

    public ?string $ai_generated_at = null;

    public string $ai_version = '';

    public string $enrichmentPrompt = '';

    public bool $saving = false;

    public bool $enriching = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        $this->code = app(ProductService::class)->nextCode();
        $this->enrichmentPrompt = ProductEnrichmentService::defaultPrompt();
        $this->resetGallerySlots();
    }

    public function openForm(?int $productId = null, string $mode = 'create'): void
    {
        $this->authorizeAction(
            $productId === null
                ? 'create'
                : ($mode === 'view' ? 'view' : 'edit'),
        );

        $this->resetForm();
        $this->mode = $mode;
        $this->productId = $productId;

        if ($productId) {
            $this->loadProduct($productId);
        } else {
            $this->applyDefaultTierQuantities();
        }
    }

    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'ai' => $this->enrichWithAi(),
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
    }

    public function updatedImageUpload(): void
    {
        $this->authorizeAction($this->mutationAction());

        if ($this->isReadonly()) {
            return;
        }

        try {
            $this->validate([
                'imageUpload' => ['required', 'image', 'max:5120'],
            ]);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('agricart.products.messages.image_invalid'), 'danger');
            $this->imageUpload = null;

            return;
        }

        if ($this->imageUpload === null) {
            return;
        }

        $cleanName = $this->nameFromImageFilename($this->imageUpload);
        $this->imagePreviewUrl = $this->imageUpload->temporaryUrl();

        if (filled($cleanName) && ! filled(trim($this->name_en))) {
            $this->name_en = $cleanName;

            if (! filled($this->slug)) {
                $this->slug = Str::slug($cleanName);
            }
        }
    }

    private function nameFromImageFilename(TemporaryUploadedFile $file): string
    {
        $original = trim($file->getClientOriginalName());
        $base = pathinfo($original, PATHINFO_FILENAME);

        if ($base === '' || str_contains($base, '.')) {
            $base = preg_replace('/\.(jpe?g|png|gif|webp|avif|svg|bmp|ico|heic|jfif|tiff?)$/iu', '', $original) ?? $original;
        }

        $base = trim(str_replace(['-', '_'], ' ', $base));

        return Str::title($base);
    }

    public function updatedGalleryUploads(mixed $value, string $key): void
    {
        $this->authorizeAction($this->mutationAction());

        if ($this->isReadonly()) {
            return;
        }

        $index = (int) $key;

        try {
            $this->validate([
                "galleryUploads.{$index}" => ['required', 'image', 'max:5120'],
            ]);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('agricart.products.messages.image_invalid'), 'danger');
            $this->galleryUploads[$index] = null;

            return;
        }

        $upload = $this->galleryUploads[$index] ?? null;

        if (! $upload instanceof TemporaryUploadedFile) {
            return;
        }

        $this->galleryPreviewUrls[$index] = $upload->temporaryUrl();
    }

    public function removeGalleryImage(int $index): void
    {
        $this->authorizeAction($this->mutationAction());

        if ($this->isReadonly()) {
            return;
        }

        $this->storedGalleryPaths[$index] = null;
        $this->galleryPreviewUrls[$index] = null;
        $this->galleryUploads[$index] = null;
    }

    public function selectParent(int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->parent_id = $id;
        $this->parent_search = '';
        $this->syncHierarchyFromParent($id);
    }

    public function clearParent(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->parent_id = null;
        $this->parent_search = '';
        $this->hierarchySlots = [null, null, null, null, null];
    }

    public function addDisplayCategory(int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $id = (int) $id;

        if ($id === $this->parent_id || in_array($id, $this->display_category_ids, true)) {
            $this->display_category_search = '';

            return;
        }

        $this->display_category_ids[] = $id;
        $this->display_category_search = '';
    }

    public function removeDisplayCategory(int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->display_category_ids = array_values(array_filter(
            $this->display_category_ids,
            fn (int $selectedId): bool => $selectedId !== (int) $id,
        ));
    }

    public function selectBrand(int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->brand_id = $id;
        $this->brand_search = '';
    }

    public function clearBrand(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->brand_id = null;
        $this->brand_search = '';
    }

    public function selectUnit(int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->unit_id = $id;
        $this->unit_search = '';
    }

    public function clearUnit(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->unit_id = null;
        $this->unit_search = '';
    }

    public function selectPackUnit(int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        if (! PackingUnit::query()->whereKey($id)->where('status', 'active')->exists()) {
            return;
        }

        $this->pack_unit_id = $id;
        $this->pack_unit_search = '';
    }

    public function clearPackUnit(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->pack_unit_id = null;
        $this->pack_unit_search = '';
    }

    public function addAttributeRow(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->attributeRows[] = ['attribute_id' => null, 'value' => ''];
        $this->syncAttributeSearches();
    }

    public function removeAttributeRow(int $index): void
    {
        if ($this->isReadonly()) {
            return;
        }

        if (count($this->attributeRows) <= 1) {
            $this->attributeRows = [['attribute_id' => null, 'value' => '']];
            $this->syncAttributeSearches();

            return;
        }

        unset($this->attributeRows[$index]);
        $this->attributeRows = array_values($this->attributeRows);
        $this->syncAttributeSearches();
    }

    public function selectAttribute(int $index, int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        if (! isset($this->attributeRows[$index])) {
            return;
        }

        $currentId = (int) ($this->attributeRows[$index]['attribute_id'] ?? 0);

        if ($currentId !== $id
            && ! CatalogAttribute::query()->whereKey($id)->where('status', 'active')->exists()) {
            return;
        }

        $alreadyUsed = collect($this->attributeRows)
            ->except($index)
            ->pluck('attribute_id')
            ->contains($id);

        if ($alreadyUsed) {
            return;
        }

        $this->attributeRows[$index]['attribute_id'] = $id;
        $this->attributeSearches[$index] = '';
    }

    public function clearAttribute(int $index): void
    {
        if ($this->isReadonly()) {
            return;
        }

        if (! isset($this->attributeRows[$index])) {
            return;
        }

        $this->attributeRows[$index]['attribute_id'] = null;
        $this->attributeSearches[$index] = '';
    }

    public function addControl(int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $id = (int) $id;

        foreach ($this->selectedControls as $control) {
            if ($control['id'] === $id) {
                $this->control_search = '';

                return;
            }
        }

        $control = CatalogControl::query()
            ->whereKey($id)
            ->where('status', 'active')
            ->first();

        if ($control === null) {
            return;
        }

        $this->selectedControls[] = [
            'id' => $control->id,
            'name_en' => $control->name_en,
            'name_ur' => $control->name_ur,
            'value_en' => $control->value_en,
            'value_ur' => $control->value_ur,
        ];
        $this->control_search = '';
    }

    public function removeControl(int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->selectedControls = array_values(array_filter(
            $this->selectedControls,
            fn (array $control): bool => $control['id'] !== $id,
        ));
    }

    public function addDeliveryMethod(int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $id = (int) $id;

        foreach ($this->selectedDeliveryMethods as $method) {
            if ($method['id'] === $id) {
                $this->delivery_search = '';

                return;
            }
        }

        $method = DeliveryMethod::query()
            ->whereKey($id)
            ->where('status', DeliveryMethod::STATUS_ACTIVE)
            ->first();

        if ($method === null) {
            return;
        }

        $this->selectedDeliveryMethods[] = [
            'id' => $method->id,
            'name_en' => $method->name_en,
            'name_ur' => $method->name_ur,
        ];
        $this->delivery_search = '';
    }

    public function removeDeliveryMethod(int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->selectedDeliveryMethods = array_values(array_filter(
            $this->selectedDeliveryMethods,
            fn (array $method): bool => $method['id'] !== $id,
        ));
    }

    public function enrichWithAi(): void
    {
        $this->authorizeAction($this->mutationAction());

        if ($this->isReadonly()) {
            return;
        }

        if (! filled(trim($this->name_en))) {
            $this->setStatus(__('agricart.products.ai.name_required'), 'warning');
            $this->enriching = false;
            $this->dispatchBusyState();

            return;
        }

        $this->enriching = true;
        $this->ai_status = CategoryAiStatus::Pending->value;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $result = app(ProductEnrichmentService::class)->enrich(
                $this->name_en,
                $this->resolveImagePathForAi(),
                $this->enrichmentPrompt,
            );

            if (! $result['success']) {
                $this->ai_status = CategoryAiStatus::Failed->value;
                $this->setStatus($result['message'], 'danger');

                return;
            }

            $this->applyEnrichmentData($result['data'] ?? []);
            $this->ai_status = CategoryAiStatus::Enriched->value;
            $this->ai_generated_at = now()->format('Y-m-d H:i');
            $this->ai_version = (string) ($result['model_id'] ?? $this->ai_version);
            $this->setStatus($result['message'], 'success');
            $this->dispatch('product-form-scroll', step: 1);
        } catch (\Throwable $exception) {
            $this->ai_status = CategoryAiStatus::Failed->value;
            $this->setStatus(FriendlyError::message($exception), 'danger');
        } finally {
            $this->enriching = false;
            $this->dispatchBusyState();
        }
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('products-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('products-saved');

            $keepParentId = $this->parent_id;
            $keepDisplayCategoryIds = $this->display_category_ids;

            $this->resetForm();
            $this->applyDefaultTierQuantities();

            // Keep hierarchy / display categories so the next product can be added under the same tree.
            if ($keepParentId) {
                $this->parent_id = $keepParentId;
                $this->syncHierarchyFromParent($keepParentId);
            }

            $this->display_category_ids = array_values(array_unique(array_filter(array_map(
                static fn ($id): int => (int) $id,
                $keepDisplayCategoryIds,
            ))));

            $this->setStatus(__('agricart.products.messages.saved_next'), 'success');
            $this->dispatch('product-form-scroll', step: 1);
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        return view('livewire.products.form', [
            'filteredParentOptions' => CategoryForm::searchCategoryLabels(
                $this->parent_search,
                null,
                array_filter(array_merge($this->display_category_ids, [$this->parent_id])),
            ),
            'filteredDisplayCategoryOptions' => CategoryForm::searchCategoryLabels(
                $this->display_category_search,
                null,
                array_merge($this->display_category_ids, array_filter([$this->parent_id])),
            ),
            'selectedDisplayCategories' => CategoryForm::categoryLabelsByIds($this->display_category_ids),
            'selectedParentLabel' => CategoryForm::labelForId($this->parent_id),
            'hierarchySlotPreviews' => $this->hierarchySlotPreviews(),
            'filteredBrandOptions' => $this->searchBrands($this->brand_search),
            'selectedBrand' => $this->selectedBrand(),
            'attributeLabelsByRow' => $this->attributeLabelsByRow(),
            'filteredAttributeOptionsByRow' => $this->filteredAttributeOptionsByRow(),
            'filteredUnitOptions' => $this->searchUnits($this->unit_search),
            'filteredPackUnitOptions' => $this->searchPackUnits($this->pack_unit_search),
            'selectedUnitLabel' => $this->labelForUnit($this->unit_id),
            'selectedPackUnitLabel' => $this->labelForPackUnit($this->pack_unit_id),
            'filteredControlOptions' => $this->searchControls($this->control_search),
            'filteredDeliveryOptions' => $this->searchDeliveryMethods($this->delivery_search),
            'statusOptions' => [
                'active' => __('agricart.form_panel.statuses.active'),
                'inactive' => __('agricart.form_panel.statuses.inactive'),
                'draft' => __('agricart.form_panel.statuses.draft'),
            ],
            'aiStatusOptions' => collect(CategoryAiStatus::cases())
                ->mapWithKeys(fn (CategoryAiStatus $status): array => [$status->value => $status->label()])
                ->all(),
            'labelCodePreview' => $this->labelCodePreview(),
            'categoryVisualMapCode' => $this->categoryVisualMapCode(),
            'pricingTiers' => $this->pricingTierRows(),
        ]);
    }

    private function labelCodePreview(): ?string
    {
        if (filled($this->label_code)) {
            return $this->label_code;
        }

        $mapCode = $this->categoryVisualMapCode();

        if ($mapCode === null) {
            return null;
        }

        return $mapCode.'-P?';
    }

    private function categoryVisualMapCode(): ?string
    {
        if ($this->parent_id === null) {
            return null;
        }

        $category = Category::query()->find($this->parent_id);

        if ($category === null) {
            return null;
        }

        $mapCode = CategoryVisualMappingService::formatMapCode(
            (string) ($category->visual_mapping['map_code'] ?? ''),
        );

        return $mapCode !== '' ? $mapCode : null;
    }

    private function persist(): bool
    {
        if ($this->saving) {
            return false;
        }

        $this->authorizeAction($this->mutationAction());

        if ($this->isReadonly()) {
            return false;
        }

        $missing = [];

        if (! filled(trim($this->name_en))) {
            $missing[] = __('agricart.form_panel.categories.english_name');
        }

        if (! filled(trim($this->name_ur))) {
            $missing[] = __('agricart.form_panel.categories.urdu_name');
        }

        if ($this->parent_id === null) {
            $missing[] = __('agricart.products.fields.parent_category');
        }

        if ($this->brand_id === null) {
            $missing[] = __('agricart.products.fields.brand');
        }

        if ($this->unit_id === null) {
            $missing[] = __('agricart.products.fields.unit');
        }

        if (! filled(trim($this->unit_qty))) {
            $missing[] = __('agricart.products.fields.qty');
        }

        if ($this->pack_unit_id === null) {
            $missing[] = __('agricart.products.fields.pack_unit');
        }

        if ($this->normalizedAttributeRows() === []) {
            $missing[] = __('agricart.products.fields.attributes');
        }

        if ($this->selectedControls === []) {
            $missing[] = __('agricart.products.fields.controls');
        }

        if ($this->selectedDeliveryMethods === []) {
            $missing[] = __('agricart.products.fields.delivery_options');
        }

        if ($missing !== []) {
            $this->setStatus(
                __('agricart.products.messages.required_fields', ['fields' => implode(', ', $missing)]),
                'danger',
            );
            $this->dispatchBusyState();

            return false;
        }

        if (! json_validate($this->search_terms_json)) {
            $this->setStatus(__('agricart.products.messages.search_terms_invalid'), 'danger');
            $this->dispatchBusyState();

            return false;
        }

        $this->saving = true;
        $this->dispatchBusyState();
        $originalProduct = $this->productId
            ? Product::query()->findOrFail($this->productId)
            : null;
        $originalImagePath = $originalProduct?->image_path;
        $originalGalleryPaths = $originalProduct?->gallery_images ?? [];
        $newlyStoredPaths = [];
        $committed = false;

        try {
            $mainImagePath = $this->persistUploadedImage();

            if ($mainImagePath !== null) {
                $newlyStoredPaths[] = $mainImagePath;
            }

            $newlyStoredPaths = array_merge($newlyStoredPaths, $this->persistGalleryUploads());

            $service = app(ProductService::class);
            $payload = $this->buildPayload($service);
            $displayIds = array_map('intval', $this->display_category_ids);
            $attributes = $this->normalizedAttributeRows();
            $controlIds = collect($this->selectedControls)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $deliveryMethodIds = collect($this->selectedDeliveryMethods)->pluck('id')->map(fn ($id) => (int) $id)->all();

            if ($this->productId) {
                $product = Product::query()->findOrFail($this->productId);
                $product = $service->update($product, $payload, $displayIds, $attributes, $controlIds, $deliveryMethodIds);
            } else {
                $product = $service->create($payload, $displayIds, $attributes, $controlIds, $deliveryMethodIds);
                $this->productId = $product->id;
                $this->mode = 'edit';
            }

            $committed = true;
            $this->deleteSupersededMedia($originalImagePath, $originalGalleryPaths, $product);
            $this->applyProductToForm($product);
            $this->setStatus(__('agricart.products.messages.saved'), 'success');

            return true;
        } catch (ValidationException $exception) {
            if (! $committed) {
                $this->deleteStoredPaths($newlyStoredPaths);
                $this->restoreOriginalMedia($originalImagePath, $originalGalleryPaths);
            }

            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('agricart.products.messages.validation_failed'), 'danger');

            return false;
        } catch (\Throwable $exception) {
            if (! $committed) {
                $this->deleteStoredPaths($newlyStoredPaths);
                $this->restoreOriginalMedia($originalImagePath, $originalGalleryPaths);
            }

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
    private function buildPayload(ProductService $service): array
    {
        if (! filled($this->slug) && filled($this->name_en)) {
            $this->slug = $service->uniqueSlug($service->slugFromName($this->name_en), $this->productId);
        }

        $searchTerms = json_decode($this->search_terms_json, true);

        return [
            'code' => $this->code ?: null,
            'slug' => $this->slug,
            'status' => $this->status ?: 'active',
            'name_en' => $this->name_en,
            'name_ur' => $this->name_ur,
            'category_id' => $this->parent_id,
            'brand_id' => $this->brand_id,
            'image_path' => $this->storedImagePath,
            'gallery_images' => array_values(array_filter(
                array_merge($this->storedGalleryPaths, $this->galleryOverflowPaths),
                fn (?string $path): bool => filled($path),
            )),
            'sku' => filled($this->sku) ? $this->sku : null,
            'unit_id' => $this->unit_id,
            'unit_qty' => filled($this->unit_qty) ? $this->unit_qty : null,
            'pack_unit_id' => $this->pack_unit_id,
            'pack_qty' => filled($this->pack_qty) ? $this->pack_qty : null,
            'required_qty' => filled($this->required_qty) ? $this->required_qty : null,
            'alert_qty' => filled($this->alert_qty) ? $this->alert_qty : null,
            'retail_price' => filled($this->retail_price) ? $this->retail_price : null,
            'wholesale_price' => filled($this->wholesale_price) ? $this->wholesale_price : null,
            'super_wholesale_price' => filled($this->super_wholesale_price) ? $this->super_wholesale_price : null,
            'distributor_price' => filled($this->distributor_price) ? $this->distributor_price : null,
            'price_tier_quantities' => $this->normalizedTierQuantities(),
            'short_description_en' => $this->short_description_en ?: null,
            'short_description_ur' => $this->short_description_ur ?: null,
            'full_description_en' => $this->description_en ?: null,
            'full_description_ur' => $this->description_ur ?: null,
            'usage_guidelines_en' => $this->usage_guidelines_en ?: null,
            'usage_guidelines_ur' => $this->usage_guidelines_ur ?: null,
            'benefits_en' => $this->benefits_en ?: null,
            'benefits_ur' => $this->benefits_ur ?: null,
            'warnings_en' => $this->warnings_en ?: null,
            'warnings_ur' => $this->warnings_ur ?: null,
            'buying_guide_en' => $this->buying_guide_en ?: null,
            'buying_guide_ur' => $this->buying_guide_ur ?: null,
            'seo_title_en' => $this->seo_title_en ?: null,
            'seo_title_ur' => $this->seo_title_ur ?: null,
            'seo_description_en' => $this->seo_description_en ?: null,
            'seo_description_ur' => $this->seo_description_ur ?: null,
            'seo_keywords_en' => $this->seo_keywords_en ?: null,
            'seo_keywords_ur' => $this->seo_keywords_ur ?: null,
            'seo_focus_keyword_en' => $this->seo_focus_keyword_en ?: null,
            'seo_focus_keyword_ur' => $this->seo_focus_keyword_ur ?: null,
            'search_terms' => is_array($searchTerms) ? $searchTerms : null,
            'hs_code' => filled($this->hs_code) ? $this->hs_code : null,
            'customs_notes_en' => $this->customs_notes_en ?: null,
            'customs_notes_ur' => $this->customs_notes_ur ?: null,
            'import_notes_en' => $this->import_notes_en ?: null,
            'import_notes_ur' => $this->import_notes_ur ?: null,
            'export_notes_en' => $this->export_notes_en ?: null,
            'export_notes_ur' => $this->export_notes_ur ?: null,
            'ai_status' => $this->ai_status ?: 'none',
            'ai_generated_at' => $this->ai_generated_at,
            'ai_version' => filled($this->ai_version) ? $this->ai_version : null,
        ];
    }

    /**
     * @return list<array{attribute_id: int, value: string}>
     */
    private function normalizedAttributeRows(): array
    {
        $rows = [];

        foreach ($this->attributeRows as $row) {
            $attributeId = (int) ($row['attribute_id'] ?? 0);
            $value = trim((string) ($row['value'] ?? ''));

            if ($attributeId <= 0 || $value === '') {
                continue;
            }

            if (collect($rows)->contains(fn (array $existing): bool => $existing['attribute_id'] === $attributeId)) {
                continue;
            }

            $rows[] = ['attribute_id' => $attributeId, 'value' => $value];
        }

        return $rows;
    }

    private function persistUploadedImage(): ?string
    {
        if ($this->imageUpload === null) {
            return null;
        }

        $path = app(OptimizedImageStorage::class)->store($this->imageUpload, 'products');
        $this->storedImagePath = $path;
        $this->imagePreviewUrl = Storage::disk('public')->url($path);
        $this->imageUpload = null;

        return $path;
    }

    /**
     * @return list<string>
     */
    private function persistGalleryUploads(): array
    {
        $stored = [];

        foreach ($this->galleryUploads as $index => $upload) {
            if (! $upload instanceof TemporaryUploadedFile) {
                continue;
            }

            $path = app(OptimizedImageStorage::class)->store($upload, 'products/gallery');
            $this->storedGalleryPaths[$index] = $path;
            $this->galleryPreviewUrls[$index] = Storage::disk('public')->url($path);
            $this->galleryUploads[$index] = null;
            $stored[] = $path;
        }

        return $stored;
    }

    /**
     * @param  list<string>  $originalGalleryPaths
     */
    private function deleteSupersededMedia(
        ?string $originalImagePath,
        array $originalGalleryPaths,
        Product $savedProduct,
    ): void {
        $currentPaths = array_values(array_filter(array_merge(
            [$savedProduct->image_path],
            $savedProduct->gallery_images ?? [],
        )));
        $superseded = array_values(array_diff(
            array_values(array_filter(array_merge([$originalImagePath], $originalGalleryPaths))),
            $currentPaths,
        ));

        $this->deleteStoredPaths($superseded);
    }

    /**
     * @param  list<string>  $paths
     */
    private function deleteStoredPaths(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if (filled($path)) {
                app(MediaLibraryService::class)->deleteFileIfUnused($path);
            }
        }
    }

    /**
     * @param  list<string>  $galleryPaths
     */
    private function restoreOriginalMedia(?string $imagePath, array $galleryPaths): void
    {
        $this->imageUpload = null;
        $this->storedImagePath = $imagePath;
        $this->imagePreviewUrl = filled($imagePath)
            ? Storage::disk('public')->url($imagePath)
            : null;
        $this->resetGallerySlots();

        foreach (array_slice($galleryPaths, 0, self::GALLERY_SLOT_COUNT) as $index => $path) {
            if (! is_string($path) || ! filled($path)) {
                continue;
            }

            $this->storedGalleryPaths[$index] = $path;
            $this->galleryPreviewUrls[$index] = Storage::disk('public')->url($path);
        }

        $this->galleryOverflowPaths = array_values(array_filter(
            array_slice($galleryPaths, self::GALLERY_SLOT_COUNT),
            fn (mixed $path): bool => is_string($path) && filled($path),
        ));
    }

    private function resolveImagePathForAi(): ?string
    {
        if ($this->imageUpload !== null) {
            return $this->imageUpload->getRealPath();
        }

        if (filled($this->storedImagePath)) {
            $full = Storage::disk('public')->path($this->storedImagePath);

            return is_readable($full) ? $full : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyEnrichmentData(array $data): void
    {
        $fieldMap = [
            'full_description_en' => 'description_en',
            'full_description_ur' => 'description_ur',
        ];

        foreach ($data as $key => $value) {
            if ($key === 'name_en') {
                continue;
            }

            if ($key === 'search_terms' && is_array($value)) {
                $this->search_terms_json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                continue;
            }

            if (isset($fieldMap[$key])) {
                if (is_string($value) && filled(trim($value))) {
                    $this->{$fieldMap[$key]} = $value;
                }

                continue;
            }

            if (property_exists($this, $key)) {
                if (is_string($value) && filled(trim($value))) {
                    $this->{$key} = $value;
                }
            }
        }

        if (filled($this->name_en) && ! filled($this->slug)) {
            $this->slug = Str::slug($this->name_en);
        }
    }

    private function resetGallerySlots(): void
    {
        $this->galleryUploads = array_fill(0, self::GALLERY_SLOT_COUNT, null);
        $this->galleryPreviewUrls = array_fill(0, self::GALLERY_SLOT_COUNT, null);
        $this->storedGalleryPaths = array_fill(0, self::GALLERY_SLOT_COUNT, null);
        $this->galleryOverflowPaths = [];
    }

    private function loadProduct(int $productId): void
    {
        $product = Product::query()
            ->with(['attributes', 'controls', 'deliveryMethods', 'displayCategories', 'brand', 'category'])
            ->findOrFail($productId);

        $this->applyProductToForm($product);
        $this->syncHierarchyFromParent($product->category_id);
    }

    private function applyProductToForm(Product $product): void
    {
        $this->productId = $product->id;
        $this->name_en = $product->name_en;
        $this->name_ur = $product->name_ur;
        $this->parent_id = $product->category_id;
        $this->brand_id = $product->brand_id;
        $this->storedImagePath = $product->image_path;
        $this->imagePreviewUrl = filled($product->image_path)
            ? Storage::disk('public')->url($product->image_path)
            : null;
        $this->resetGallerySlots();
        $galleryImages = $product->gallery_images ?? [];
        $this->galleryOverflowPaths = array_values(array_filter(
            array_slice($galleryImages, self::GALLERY_SLOT_COUNT),
            fn (mixed $path): bool => is_string($path) && filled($path),
        ));

        foreach ($galleryImages as $index => $path) {
            if ($index >= self::GALLERY_SLOT_COUNT) {
                break;
            }

            if (! is_string($path) || ! filled($path)) {
                continue;
            }

            $this->storedGalleryPaths[$index] = $path;
            $this->galleryPreviewUrls[$index] = Storage::disk('public')->url($path);
        }

        $this->code = $product->code;
        $this->slug = $product->slug;
        $this->sku = $product->sku ?? '';
        $this->status = $product->status;
        $this->unit_id = $product->unit_id;
        $this->unit_qty = $product->unit_qty ?? '';
        $this->pack_unit_id = $product->pack_unit_id;
        $this->pack_qty = $product->pack_qty ?? '';
        $this->required_qty = $product->required_qty ?? '';
        $this->alert_qty = $product->alert_qty ?? '';
        $this->retail_price = $product->retail_price ?? '';
        $this->wholesale_price = $product->wholesale_price ?? '';
        $this->super_wholesale_price = $product->super_wholesale_price ?? '';
        $this->distributor_price = $product->distributor_price ?? '';
        $this->price_tier_quantities = $this->mergeTierQuantities($product);
        $this->short_description_en = $product->short_description_en ?? '';
        $this->short_description_ur = $product->short_description_ur ?? '';
        $this->description_en = $product->full_description_en ?? '';
        $this->description_ur = $product->full_description_ur ?? '';
        $this->usage_guidelines_en = $product->usage_guidelines_en ?? '';
        $this->usage_guidelines_ur = $product->usage_guidelines_ur ?? '';
        $this->benefits_en = $product->benefits_en ?? '';
        $this->benefits_ur = $product->benefits_ur ?? '';
        $this->warnings_en = $product->warnings_en ?? '';
        $this->warnings_ur = $product->warnings_ur ?? '';
        $this->buying_guide_en = $product->buying_guide_en ?? '';
        $this->buying_guide_ur = $product->buying_guide_ur ?? '';
        $this->seo_title_en = $product->seo_title_en ?? '';
        $this->seo_title_ur = $product->seo_title_ur ?? '';
        $this->seo_description_en = $product->seo_description_en ?? '';
        $this->seo_description_ur = $product->seo_description_ur ?? '';
        $this->seo_keywords_en = $product->seo_keywords_en ?? '';
        $this->seo_keywords_ur = $product->seo_keywords_ur ?? '';
        $this->seo_focus_keyword_en = $product->seo_focus_keyword_en ?? '';
        $this->seo_focus_keyword_ur = $product->seo_focus_keyword_ur ?? '';
        $this->search_terms_json = json_encode($product->search_terms ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $this->hs_code = $product->hs_code ?? '';
        $this->customs_notes_en = $product->customs_notes_en ?? '';
        $this->customs_notes_ur = $product->customs_notes_ur ?? '';
        $this->import_notes_en = $product->import_notes_en ?? '';
        $this->import_notes_ur = $product->import_notes_ur ?? '';
        $this->export_notes_en = $product->export_notes_en ?? '';
        $this->export_notes_ur = $product->export_notes_ur ?? '';
        $this->label_code = $product->label_code ?? '';
        $this->ai_status = $product->ai_status?->value ?? 'none';
        $this->ai_generated_at = $product->ai_generated_at?->format('Y-m-d H:i');
        $this->ai_version = $product->ai_version ?? '';
        $this->enrichmentPrompt = ProductEnrichmentService::defaultPrompt();
        $this->display_category_ids = $product->displayCategories->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->attributeRows = $product->attributes->isNotEmpty()
            ? $product->attributes->map(fn ($attribute): array => [
                'attribute_id' => $attribute->id,
                'value' => (string) ($attribute->pivot->value ?? ''),
            ])->all()
            : [['attribute_id' => null, 'value' => '']];
        $this->syncAttributeSearches();

        $this->selectedControls = $product->controls->map(fn ($control): array => [
            'id' => $control->id,
            'name_en' => $control->name_en,
            'name_ur' => $control->name_ur,
            'value_en' => $control->value_en,
            'value_ur' => $control->value_ur,
        ])->all();

        $this->selectedDeliveryMethods = $product->deliveryMethods->map(fn (DeliveryMethod $method): array => [
            'id' => $method->id,
            'name_en' => $method->name_en,
            'name_ur' => $method->name_ur,
        ])->all();
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('product-form-busy', enriching: $this->enriching, saving: $this->saving);
    }

    private function setStatus(string $message, string $type = 'success'): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function syncHierarchyFromParent(?int $parentId): void
    {
        $this->hierarchySlots = [null, null, null, null, null];

        if ($parentId === null) {
            return;
        }

        $chain = $this->ancestorChainIds($parentId);

        foreach ($chain as $index => $categoryId) {
            if ($index > 4) {
                break;
            }

            $this->hierarchySlots[$index] = $categoryId;
        }
    }

    /**
     * @return list<int>
     */
    private function ancestorChainIds(int $categoryId): array
    {
        $chain = [];
        $current = Category::query()->find($categoryId);
        $visited = [];

        while ($current !== null && ! isset($visited[$current->id]) && count($chain) < 100) {
            $visited[$current->id] = true;
            array_unshift($chain, $current->id);
            $current = $current->parent_id
                ? Category::query()->find($current->parent_id)
                : null;
        }

        return $chain;
    }

    /**
     * @return array<int, array{url: string|null, name: string|null}>
     */
    private function hierarchySlotPreviews(): array
    {
        $previews = [];

        for ($slot = 0; $slot < 5; $slot++) {
            $categoryId = $this->hierarchySlots[$slot] ?? null;
            $previews[$slot] = ['url' => null, 'name' => null];

            if ($categoryId === null) {
                continue;
            }

            $category = Category::query()->find($categoryId);

            if ($category === null) {
                continue;
            }

            $previews[$slot] = [
                'url' => filled($category->image_path)
                    ? Storage::disk('public')->url($category->image_path)
                    : null,
                'name' => $category->name_en,
            ];
        }

        return $previews;
    }

    /**
     * @return array<int, string>
     */
    private function searchBrands(string $search): array
    {
        $term = trim($search);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $needle = '%'.$term.'%';

        return Brand::query()
            ->when($this->brand_id, fn ($query) => $query->where('id', '!=', $this->brand_id))
            ->where(function ($query) use ($needle): void {
                $query->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle)
                    ->orWhere('code', 'like', $needle);
            })
            ->orderBy('name_en')
            ->limit(20)
            ->get()
            ->mapWithKeys(fn (Brand $brand): array => [
                $brand->id => $brand->name_en.($brand->name_ur ? ' — '.$brand->name_ur : ''),
            ])
            ->all();
    }

    /**
     * @return array{label: string|null, logo_url: string|null}
     */
    private function selectedBrand(): array
    {
        if ($this->brand_id === null) {
            return ['label' => null, 'logo_url' => null];
        }

        $brand = Brand::query()->find($this->brand_id);

        if ($brand === null) {
            return ['label' => null, 'logo_url' => null];
        }

        return [
            'label' => $brand->name_en,
            'logo_url' => filled($brand->image_path)
                ? Storage::disk('public')->url($brand->image_path)
                : null,
        ];
    }

    /**
     * @return array<int, string|null>
     */
    private function attributeLabelsByRow(): array
    {
        $labels = [];

        foreach ($this->attributeRows as $index => $row) {
            $attributeId = (int) ($row['attribute_id'] ?? 0);

            if ($attributeId <= 0) {
                continue;
            }

            $attribute = CatalogAttribute::query()->find($attributeId);

            if ($attribute === null) {
                continue;
            }

            $labels[$index] = $attribute->name_en.($attribute->name_ur ? ' — '.$attribute->name_ur : '');
        }

        return $labels;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function filteredAttributeOptionsByRow(): array
    {
        $optionsByRow = [];

        foreach ($this->attributeRows as $index => $row) {
            $term = trim($this->attributeSearches[$index] ?? '');

            if (mb_strlen($term) < 2) {
                $optionsByRow[$index] = [];

                continue;
            }

            $usedElsewhere = collect($this->attributeRows)
                ->except($index)
                ->pluck('attribute_id')
                ->filter()
                ->map(fn ($id): int => (int) $id)
                ->all();

            $needle = '%'.$term.'%';

            $optionsByRow[$index] = CatalogAttribute::query()
                ->when($usedElsewhere !== [], fn ($query) => $query->whereNotIn('id', $usedElsewhere))
                ->where('status', 'active')
                ->where(function ($query) use ($needle): void {
                    $query->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('code', 'like', $needle);
                })
                ->orderBy('name_en')
                ->limit(20)
                ->get()
                ->mapWithKeys(fn (CatalogAttribute $attribute): array => [
                    $attribute->id => $attribute->name_en.($attribute->name_ur ? ' — '.$attribute->name_ur : ''),
                ])
                ->all();
        }

        return $optionsByRow;
    }

    private function syncAttributeSearches(): void
    {
        $searches = [];

        foreach ($this->attributeRows as $index => $row) {
            $searches[$index] = $this->attributeSearches[$index] ?? '';
        }

        $this->attributeSearches = $searches;
    }

    /**
     * @return array<int, string>
     */
    private function searchUnits(string $search): array
    {
        $term = trim($search);

        if (mb_strlen($term) < 1) {
            return [];
        }

        $needle = '%'.$term.'%';

        return Unit::query()
            ->when($this->unit_id, fn ($query) => $query->where('id', '!=', $this->unit_id))
            ->where('status', 'active')
            ->where(function ($query) use ($needle): void {
                $query->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle)
                    ->orWhere('abbreviation_en', 'like', $needle)
                    ->orWhere('abbreviation_ur', 'like', $needle);
            })
            ->orderBy('name_en')
            ->limit(20)
            ->get()
            ->mapWithKeys(fn (Unit $unit): array => [
                $unit->id => $unit->name_en.($unit->abbreviation_en ? ' ('.$unit->abbreviation_en.')' : ''),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function searchPackUnits(string $search): array
    {
        $term = trim($search);

        if (mb_strlen($term) < 1) {
            return [];
        }

        $needle = '%'.$term.'%';

        return PackingUnit::query()
            ->when($this->pack_unit_id, fn ($query) => $query->where('id', '!=', $this->pack_unit_id))
            ->where('status', 'active')
            ->where(function ($query) use ($needle): void {
                $query->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle);
            })
            ->orderBy('name_en')
            ->limit(20)
            ->get()
            ->mapWithKeys(fn (PackingUnit $packingUnit): array => [
                $packingUnit->id => $packingUnit->name_en.($packingUnit->name_ur ? ' — '.$packingUnit->name_ur : ''),
            ])
            ->all();
    }

    private function labelForUnit(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        $unit = Unit::query()->find($id);

        return $unit?->name_en;
    }

    private function labelForPackUnit(?int $id): ?string
    {
        if ($id === null) {
            return null;
        }

        $packUnit = PackingUnit::query()->find($id);

        return $packUnit?->name_en;
    }

    /**
     * @return array<int, string>
     */
    private function searchControls(string $search): array
    {
        $term = trim($search);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $needle = '%'.$term.'%';
        $exclude = collect($this->selectedControls)->pluck('id')->all();

        return CatalogControl::query()
            ->when($exclude !== [], fn ($query) => $query->whereNotIn('id', $exclude))
            ->where('status', 'active')
            ->where(function ($query) use ($needle): void {
                $query->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle);
            })
            ->orderBy('name_en')
            ->limit(20)
            ->get()
            ->mapWithKeys(fn (CatalogControl $control): array => [
                $control->id => $control->name_en,
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function searchDeliveryMethods(string $search): array
    {
        $term = trim($search);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $needle = '%'.$term.'%';
        $exclude = collect($this->selectedDeliveryMethods)->pluck('id')->all();

        return DeliveryMethod::query()
            ->when($exclude !== [], fn ($query) => $query->whereNotIn('id', $exclude))
            ->where('status', DeliveryMethod::STATUS_ACTIVE)
            ->where(function ($query) use ($needle): void {
                $query->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle);
            })
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->limit(20)
            ->get()
            ->mapWithKeys(fn (DeliveryMethod $method): array => [
                $method->id => $method->name_en,
            ])
            ->all();
    }

    private function resetForm(): void
    {
        $this->productId = null;
        $this->mode = 'create';
        $this->parent_id = null;
        $this->parent_search = '';
        $this->hierarchySlots = [null, null, null, null, null];
        $this->display_category_ids = [];
        $this->display_category_search = '';
        $this->imageUpload = null;
        $this->imagePreviewUrl = null;
        $this->storedImagePath = null;
        $this->resetGallerySlots();
        $this->slug = '';
        $this->name_en = '';
        $this->name_ur = '';
        $this->brand_id = null;
        $this->brand_search = '';
        $this->attributeRows = [['attribute_id' => null, 'value' => '']];
        $this->attributeSearches = [''];
        $this->unit_id = null;
        $this->unit_search = '';
        $this->unit_qty = '';
        $this->pack_unit_id = null;
        $this->pack_unit_search = '';
        $this->pack_qty = '';
        $this->required_qty = '';
        $this->alert_qty = '';
        $this->retail_price = '';
        $this->wholesale_price = '';
        $this->super_wholesale_price = '';
        $this->distributor_price = '';
        $this->price_tier_quantities = [];
        $this->control_search = '';
        $this->selectedControls = [];
        $this->delivery_search = '';
        $this->selectedDeliveryMethods = [];
        $this->short_description_en = '';
        $this->short_description_ur = '';
        $this->description_en = '';
        $this->description_ur = '';
        $this->usage_guidelines_en = '';
        $this->usage_guidelines_ur = '';
        $this->benefits_en = '';
        $this->benefits_ur = '';
        $this->warnings_en = '';
        $this->warnings_ur = '';
        $this->buying_guide_en = '';
        $this->buying_guide_ur = '';
        $this->seo_title_en = '';
        $this->seo_title_ur = '';
        $this->seo_description_en = '';
        $this->seo_description_ur = '';
        $this->seo_keywords_en = '';
        $this->seo_keywords_ur = '';
        $this->seo_focus_keyword_en = '';
        $this->seo_focus_keyword_ur = '';
        $this->search_terms_json = '[]';
        $this->hs_code = '';
        $this->customs_notes_en = '';
        $this->customs_notes_ur = '';
        $this->import_notes_en = '';
        $this->import_notes_ur = '';
        $this->export_notes_en = '';
        $this->export_notes_ur = '';
        $this->code = app(ProductService::class)->nextCode();
        $this->sku = '';
        $this->status = 'active';
        $this->label_code = '';
        $this->ai_status = 'none';
        $this->ai_generated_at = null;
        $this->ai_version = '';
        $this->enrichmentPrompt = ProductEnrichmentService::defaultPrompt();
        $this->saving = false;
        $this->enriching = false;
        $this->statusMessage = null;
        $this->statusType = 'success';
    }

    private function applyDefaultTierQuantities(): void
    {
        $this->price_tier_quantities = app(QuantityPricingEngine::class)->defaultQuantitiesByPriceField();
    }

    private function mutationAction(): string
    {
        return $this->productId === null ? 'create' : 'edit';
    }

    private function authorizeAction(string $action): void
    {
        abort_unless($this->userCan($action), 403);
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->canUseAdmin()) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasPermission('catalog.'.$action);
    }

    /**
     * @return array<string, int>
     */
    private function mergeTierQuantities(Product $product): array
    {
        $defaults = app(QuantityPricingEngine::class)->defaultQuantitiesByPriceField();
        $stored = is_array($product->price_tier_quantities) ? $product->price_tier_quantities : [];

        return array_merge($defaults, array_map('intval', $stored));
    }

    /**
     * @return array<string, int>|null
     */
    private function normalizedTierQuantities(): ?array
    {
        if ($this->price_tier_quantities === []) {
            return null;
        }

        $normalized = [];

        foreach ($this->price_tier_quantities as $field => $quantity) {
            if ($quantity === '' || $quantity === null) {
                continue;
            }

            $normalized[(string) $field] = (int) $quantity;
        }

        return $normalized === [] ? null : $normalized;
    }

    /**
     * @return list<array{price_field: string, label: string}>
     */
    private function pricingTierRows(): array
    {
        $rows = [];

        foreach (app(QuantityPricingEngine::class)->activeTiers() as $tier) {
            $rows[] = [
                'price_field' => $tier->price_field,
                'tier_key' => $tier->tier_key,
                'label' => $tier->localizedName(),
            ];
        }

        return $rows;
    }
}
