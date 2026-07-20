<?php

namespace App\Livewire\Categories;

use App\Enums\CategoryAiStatus;
use App\Filament\Forms\CategoryForm;
use App\Models\Category;
use App\Models\User;
use App\Services\Catalog\CategoryEnrichmentService;
use App\Services\Catalog\CategoryService;
use App\Services\Catalog\CategoryVisualMappingService;
use App\Services\Media\MediaLibraryService;
use App\Services\Media\OptimizedImageStorage;
use App\Support\FriendlyError;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $categoryId = null;

    #[Locked]
    public string $mode = 'create';

    /** @var array<int, int|null> Hierarchy category IDs for visual boxes 1–4 */
    #[Locked]
    public array $hierarchySlots = [null, null, null, null];

    public ?TemporaryUploadedFile $imageUpload = null;

    public ?string $imagePreviewUrl = null;

    #[Locked]
    public ?string $storedImagePath = null;

    #[Locked]
    public string $visual_map_code = '';

    public string $enrichmentPrompt = '';

    public string $name_en = '';

    public string $name_ur = '';

    public ?int $parent_id = null;

    public string $parent_search = '';

    public string $display_category_search = '';

    /** @var list<int> */
    public array $display_category_ids = [];

    public string $slug = '';

    public string $code = '';

    public string $status = 'active';

    public string $short_description_en = '';

    public string $short_description_ur = '';

    public string $full_description_en = '';

    public string $full_description_ur = '';

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

    /** @var list<array{question_en: string, question_ur: string, answer_en: string, answer_ur: string}> */
    public array $faqs = [];

    #[Locked]
    public string $ai_status = 'none';

    public bool $saving = false;

    public bool $enriching = false;

    public ?string $statusMessage = null;

    public string $statusType = 'info';

    public function mount(): void
    {
        $this->enrichmentPrompt = CategoryEnrichmentService::defaultPrompt();
    }

    public function openForm(?int $categoryId = null, string $mode = 'create'): void
    {
        $normalizedMode = in_array($mode, ['create', 'edit', 'view'], true) ? $mode : 'create';
        $this->authorizeAction(
            $categoryId === null
                ? 'create'
                : ($normalizedMode === 'view' ? 'view' : 'edit'),
        );

        $this->mode = $normalizedMode;
        $this->categoryId = $categoryId;
        $this->statusMessage = null;

        if ($categoryId) {
            $this->loadCategory($categoryId);
        } else {
            $this->resetForm();
        }

        $this->enriching = false;
        $this->saving = false;
        $this->dispatchBusyState();
    }

    public function handlePanelAction(string $action): void
    {
        if ($this->mode === 'view' && in_array($action, ['save_close', 'save_next', 'ai'], true)) {
            $this->dispatchBusyState();

            return;
        }

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

        $this->validate([
            'imageUpload' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($this->imageUpload === null) {
            return;
        }

        $filename = pathinfo($this->imageUpload->getClientOriginalName(), PATHINFO_FILENAME);
        $this->imagePreviewUrl = \App\Support\LivewireImagePreview::url($this->imageUpload);

        $cleanName = Str::title(str_replace(['-', '_'], ' ', $filename));

        if (filled($cleanName) && ! filled($this->name_en)) {
            $this->name_en = $cleanName;

            if (! filled($this->slug)) {
                $this->slug = Str::slug($cleanName);
            }
        }
    }

    public function selectParent(int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        if (
            $this->categoryId !== null
            && app(CategoryService::class)->parentWouldCreateCycle($this->categoryId, $id)
        ) {
            $this->setStatus(__('agricart.categories.messages.hierarchy_cycle'), 'danger');

            return;
        }

        $this->parent_id = $id;
        $this->parent_search = '';
        $this->syncHierarchyFromParent($id);
        $this->refreshVisualMapCode();
    }

    public function clearParent(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->parent_id = null;
        $this->parent_search = '';
        $this->hierarchySlots = [null, null, null, null];
        $this->refreshVisualMapCode();
    }

    public function addDisplayCategory(int $id): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $id = (int) $id;

        if ($id === $this->categoryId || in_array($id, $this->display_category_ids, true)) {
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

    public function addFaq(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->faqs[] = [
            'question_en' => '',
            'question_ur' => '',
            'answer_en' => '',
            'answer_ur' => '',
        ];
    }

    public function removeFaq(int $index): void
    {
        if ($this->isReadonly()) {
            return;
        }

        unset($this->faqs[$index]);
        $this->faqs = array_values($this->faqs);
    }

    public function enrichWithAi(): void
    {
        $this->authorizeAction($this->mutationAction());

        if ($this->isReadonly() || $this->enriching) {
            return;
        }

        if (! filled($this->name_en)) {
            $this->setStatus(__('agricart.categories.ai.name_required'), 'warning');
            $this->enriching = false;
            $this->dispatchBusyState();

            return;
        }

        $lockKey = 'category-ai-enrichment:'.(auth()->id() ?? 'guest').':'.($this->categoryId ?? 'new');
        $aiLock = Cache::lock($lockKey, 120);

        if (! $aiLock->get()) {
            $this->setStatus(__('agricart.categories.ai.already_running'), 'warning');
            $this->dispatchBusyState();

            return;
        }

        $this->enriching = true;
        $this->ai_status = CategoryAiStatus::Pending->value;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $imagePath = $this->resolveImagePathForAi();
            $result = app(CategoryEnrichmentService::class)->enrich(
                $this->name_en,
                $imagePath,
                $this->enrichmentPrompt,
            );

            if (! $result['success']) {
                $this->ai_status = CategoryAiStatus::Failed->value;
                $this->setStatus($result['message'], 'danger');

                return;
            }

            $this->applyEnrichmentData($result['data'] ?? []);
            $this->ai_status = CategoryAiStatus::Enriched->value;
            $this->setStatus($result['message'], 'success');
            $this->dispatch('category-form-scroll', step: 1);
        } catch (\Throwable $exception) {
            $this->ai_status = CategoryAiStatus::Failed->value;
            $this->setStatus(FriendlyError::message($exception), 'danger');
        } finally {
            $aiLock->release();
            $this->enriching = false;
            $this->dispatchBusyState();
        }
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('categories-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('categories-saved');
            $keepParentId = $this->parent_id;
            $this->resetForm();

            if ($keepParentId) {
                $this->parent_id = $keepParentId;
                $this->syncHierarchyFromParent($keepParentId);
            }

            $this->refreshVisualMapCode();
            $this->dispatch('category-form-scroll', step: 1);
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        $canAccess = $this->canAccessCatalogForm();

        return view('livewire.categories.form', [
            'sections' => CategoryForm::sections(),
            'detailSectionKeys' => CategoryForm::detailSectionKeys(),
            'scrollAnchors' => CategoryForm::stepScrollAnchors(),
            'filteredDisplayCategoryOptions' => $canAccess
                ? CategoryForm::searchCategoryLabels(
                    $this->display_category_search,
                    $this->categoryId,
                    array_merge($this->display_category_ids, [$this->categoryId]),
                )
                : [],
            'filteredParentOptions' => $canAccess
                ? CategoryForm::searchCategoryLabels(
                    $this->parent_search,
                    $this->categoryId,
                    array_filter([$this->categoryId, $this->parent_id]),
                )
                : [],
            'selectedDisplayCategories' => $canAccess
                ? CategoryForm::categoryLabelsByIds($this->display_category_ids)
                : collect(),
            'selectedParentLabel' => $canAccess
                ? CategoryForm::labelForId($this->parent_id)
                : null,
            'hierarchySlotPreviews' => $canAccess ? $this->hierarchySlotPreviews() : [],
            'statusOptions' => CategoryForm::statusOptions(),
            'aiStatusOptions' => CategoryForm::aiStatusOptions(),
        ]);
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

        $originalImagePath = $this->categoryId
            ? Category::query()->whereKey($this->categoryId)->value('image_path')
            : null;
        $this->storedImagePath = $originalImagePath;
        $missing = [];

        if (! filled(trim($this->name_en))) {
            $missing[] = __('agricart.form_panel.categories.english_name');
        }

        if (! filled(trim($this->name_ur))) {
            $missing[] = __('agricart.form_panel.categories.urdu_name');
        }

        if (! filled($this->storedImagePath) && $this->imageUpload === null) {
            $missing[] = __('agricart.categories.fields.image');
        }

        if ($missing !== []) {
            $this->setStatus(
                __('agricart.categories.messages.required_fields', ['fields' => implode(', ', $missing)]),
                'danger',
            );
            $this->dispatchBusyState();

            return false;
        }

        $searchTerms = json_decode($this->search_terms_json, true);

        if (
            ! is_array($searchTerms)
            || ! array_is_list($searchTerms)
            || collect($searchTerms)->contains(fn ($term): bool => ! is_string($term))
        ) {
            $this->setStatus(__('agricart.categories.messages.search_terms_invalid'), 'danger');
            $this->dispatchBusyState();

            return false;
        }

        $this->saving = true;
        $this->dispatchBusyState();
        $newImagePath = null;
        $committed = false;

        try {
            $newImagePath = $this->persistUploadedImage();
            $service = app(CategoryService::class);
            $payload = $this->buildPayload($service);
            $displayIds = array_map('intval', $this->display_category_ids);

            if ($this->categoryId) {
                $category = Category::query()->findOrFail($this->categoryId);
                $service->update($category, $payload, $displayIds);
            } else {
                $category = $service->create($payload, $displayIds);
                $this->categoryId = $category->id;
                $this->mode = 'edit';
            }

            $committed = true;

            if (filled($originalImagePath) && $originalImagePath !== $this->storedImagePath) {
                app(MediaLibraryService::class)->deleteFileIfUnused($originalImagePath);
            }

            $this->storedImagePath = $payload['image_path'] ?? $this->storedImagePath;
            $this->imagePreviewUrl = filled($this->storedImagePath)
                ? Storage::disk('public')->url($this->storedImagePath)
                : null;
            $this->setStatus(__('agricart.categories.messages.saved'), 'success');

            return true;
        } catch (ValidationException $exception) {
            if (! $committed && filled($newImagePath)) {
                app(MediaLibraryService::class)->deleteFileIfUnused($newImagePath);
                $this->restoreOriginalImage($originalImagePath);
            }

            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('agricart.categories.messages.validation_failed'), 'danger');

            return false;
        } catch (\Throwable $exception) {
            if (! $committed && filled($newImagePath)) {
                app(MediaLibraryService::class)->deleteFileIfUnused($newImagePath);
                $this->restoreOriginalImage($originalImagePath);
            }

            $this->setStatus(FriendlyError::message($exception), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('category-form-busy', enriching: $this->enriching, saving: $this->saving);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(CategoryService $service): array
    {
        if (! filled($this->slug) && filled($this->name_en)) {
            $this->slug = $service->uniqueSlug($service->slugFromName($this->name_en), $this->categoryId);
        }

        $searchTerms = json_decode($this->search_terms_json, true);
        if (! is_array($searchTerms)) {
            $searchTerms = [];
        }

        $imagePath = $this->storedImagePath;
        $mapCode = $this->resolvedMapCode();
        $this->visual_map_code = $mapCode;

        return [
            'parent_id' => $this->parent_id ?: null,
            'slug' => $this->slug,
            'code' => $this->code ?: null,
            'status' => $this->status ?: 'active',
            'name_en' => $this->name_en,
            'name_ur' => $this->name_ur,
            'image_path' => $imagePath,
            'short_description_en' => $this->short_description_en ?: null,
            'short_description_ur' => $this->short_description_ur ?: null,
            'full_description_en' => $this->full_description_en ?: null,
            'full_description_ur' => $this->full_description_ur ?: null,
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
            'hs_code' => $this->hs_code ?: null,
            'customs_notes_en' => $this->customs_notes_en ?: null,
            'customs_notes_ur' => $this->customs_notes_ur ?: null,
            'import_notes_en' => $this->import_notes_en ?: null,
            'import_notes_ur' => $this->import_notes_ur ?: null,
            'export_notes_en' => $this->export_notes_en ?: null,
            'export_notes_ur' => $this->export_notes_ur ?: null,
            'visual_mapping' => [
                'hierarchy' => $this->hierarchySlots,
                'hijri' => $this->hierarchySlots,
                'map_code' => $mapCode,
            ],
            'faqs' => $this->faqs,
            'ai_status' => $this->ai_status ?: 'none',
        ];
    }

    private function persistUploadedImage(): ?string
    {
        if ($this->imageUpload === null) {
            return null;
        }

        $path = app(OptimizedImageStorage::class)->store($this->imageUpload, 'categories');
        $this->storedImagePath = $path;
        $this->imagePreviewUrl = Storage::disk('public')->url($path);
        $this->imageUpload = null;

        return $path;
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
        foreach ($data as $key => $value) {
            if ($key === 'search_terms' && is_array($value)) {
                $this->search_terms_json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                continue;
            }

            if ($key === 'faqs' && is_array($value)) {
                $this->faqs = $value;

                continue;
            }

            if (property_exists($this, $key)) {
                if (is_string($value) && filled(trim($value))) {
                    $this->{$key} = $value;
                } elseif (is_numeric($value) && $key === 'parent_id') {
                    $this->parent_id = (int) $value;
                    $this->syncHierarchyFromParent($this->parent_id);
                }
            }
        }

        if (filled($this->name_en) && ! filled($this->slug)) {
            $this->slug = Str::slug($this->name_en);
        }
    }

    private function loadCategory(int $categoryId): void
    {
        $category = Category::query()->with('displayInCategories')->findOrFail($categoryId);

        $this->categoryId = $category->id;
        $this->name_en = $category->name_en;
        $this->name_ur = $category->name_ur;
        $this->parent_id = $category->parent_id;
        $this->parent_search = '';
        $this->display_category_ids = $category->displayInCategories->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->display_category_search = '';
        $this->slug = $category->slug;
        $this->code = $category->code ?? '';
        $this->status = $category->status;
        $this->storedImagePath = $category->image_path;
        $this->imagePreviewUrl = filled($category->image_path)
            ? Storage::disk('public')->url($category->image_path)
            : null;

        foreach ([
            'short_description', 'full_description', 'usage_guidelines', 'benefits', 'warnings', 'buying_guide',
            'seo_title', 'seo_description', 'seo_keywords', 'seo_focus_keyword',
            'customs_notes', 'import_notes', 'export_notes',
        ] as $base) {
            $this->{$base.'_en'} = $category->{$base.'_en'} ?? '';
            $this->{$base.'_ur'} = $category->{$base.'_ur'} ?? '';
        }

        $this->hs_code = $category->hs_code ?? '';
        $this->search_terms_json = json_encode($category->search_terms ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $this->faqs = $category->faqs ?? [];
        $this->ai_status = $category->ai_status?->value ?? CategoryAiStatus::None->value;
        $this->hierarchySlots = $this->normalizeHierarchySlots(
            $category->visual_mapping['hierarchy'] ?? $category->visual_mapping['hijri'] ?? [],
        );

        if ($this->parent_id) {
            $this->syncHierarchyFromParent($this->parent_id);
        }

        $this->parent_search = '';
        $this->visual_map_code = CategoryVisualMappingService::formatMapCode(
            (string) ($category->visual_map_code ?: ($category->visual_mapping['map_code'] ?? '')),
        );

        if ($this->visual_map_code === '') {
            $this->refreshVisualMapCode();
        }

        $this->enrichmentPrompt = CategoryEnrichmentService::defaultPrompt();
    }

    private function refreshVisualMapCode(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->visual_map_code = $this->resolvedMapCode();
        $this->visual_map_code = CategoryVisualMappingService::formatMapCode($this->visual_map_code);
    }

    private function resolvedMapCode(): string
    {
        if ($this->categoryId !== null) {
            $category = Category::query()->find($this->categoryId);
            $existingCode = CategoryVisualMappingService::formatMapCode(
                (string) ($category?->visual_map_code ?: ($category?->visual_mapping['map_code'] ?? '')),
            );

            if (
                $category !== null
                && (int) ($category->parent_id ?? 0) === (int) ($this->parent_id ?? 0)
                && $existingCode !== ''
            ) {
                return $existingCode;
            }
        }

        return app(CategoryVisualMappingService::class)
            ->generateMapCode($this->parent_id ?: null, $this->categoryId);
    }

    private function syncHierarchyFromParent(?int $parentId): void
    {
        $this->hierarchySlots = [null, null, null, null];

        if ($parentId === null) {
            return;
        }

        $chain = $this->ancestorChainIds($parentId);

        foreach ($chain as $index => $categoryId) {
            if ($index > 3) {
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

        for ($slot = 0; $slot < 4; $slot++) {
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

    private function resetForm(): void
    {
        $this->categoryId = null;
        $this->mode = 'create';
        $this->hierarchySlots = [null, null, null, null];
        $this->imageUpload = null;
        $this->imagePreviewUrl = null;
        $this->storedImagePath = null;
        $this->enrichmentPrompt = CategoryEnrichmentService::defaultPrompt();
        $this->name_en = '';
        $this->name_ur = '';
        $this->parent_id = null;
        $this->parent_search = '';
        $this->display_category_ids = [];
        $this->display_category_search = '';
        $this->slug = '';
        $this->code = '';
        $this->status = 'active';
        $this->short_description_en = '';
        $this->short_description_ur = '';
        $this->full_description_en = '';
        $this->full_description_ur = '';
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
        $this->faqs = [];
        $this->ai_status = CategoryAiStatus::None->value;
        $this->enriching = false;
        $this->saving = false;
        $this->refreshVisualMapCode();
        $this->statusMessage = null;
    }

    /**
     * @return array<int, int|null>
     */
    private function normalizeHierarchySlots(mixed $raw): array
    {
        $slots = [null, null, null, null];

        if (! is_array($raw)) {
            return $slots;
        }

        foreach ($raw as $index => $value) {
            if (! is_int($index) && ! is_numeric($index)) {
                continue;
            }

            $slot = (int) $index;

            if ($slot < 0 || $slot > 3) {
                continue;
            }

            if (is_numeric($value) && (int) $value > 0) {
                $slots[$slot] = (int) $value;
            }
        }

        return $slots;
    }

    private function restoreOriginalImage(?string $imagePath): void
    {
        $this->imageUpload = null;
        $this->storedImagePath = $imagePath;
        $this->imagePreviewUrl = filled($imagePath)
            ? Storage::disk('public')->url($imagePath)
            : null;
    }

    private function mutationAction(): string
    {
        return $this->categoryId === null ? 'create' : 'edit';
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

    private function canAccessCatalogForm(): bool
    {
        return $this->userCan('view')
            || $this->userCan('create')
            || $this->userCan('edit');
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
