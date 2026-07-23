<?php

namespace App\Livewire\Brands;

use App\Enums\CategoryAiStatus;
use App\Models\Brand;
use App\Models\User;
use App\Models\StoreSetting;
use App\Services\Catalog\BrandAgricartReviewAiService;
use App\Services\Catalog\BrandAgricartReviewMessageService;
use App\Services\Catalog\BrandEnrichmentService;
use App\Services\Catalog\BrandService;
use App\Services\Media\MediaLibraryService;
use App\Services\Media\OptimizedImageStorage;
use App\Support\Catalog\BrandAgricartReviewCriteria;
use App\Support\Catalog\BrandAgricartReviewNotice;
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
    public ?int $brandId = null;

    #[Locked]
    public string $mode = 'create';

    public ?TemporaryUploadedFile $imageUpload = null;

    public ?string $imagePreviewUrl = null;

    #[Locked]
    public ?string $storedImagePath = null;

    public string $enrichmentPrompt = '';

    public string $name_en = '';

    public string $name_ur = '';

    public string $description_en = '';

    public string $description_ur = '';

    public string $website_url = '';

    #[Locked]
    public string $code = '';

    #[Locked]
    public string $slug = '';

    public string $status = 'active';

    public string $inactive_reason_en = '';

    public string $inactive_reason_ur = '';

    public string $agricart_rating = '';

    /** @var array<string, array{rating: int|null, reason_en: string, reason_ur: string}> */
    public array $agricart_rating_criteria = [];

    /** @var array<string, bool> */
    public array $saveCriterionMessageAsDefault = [];

    public string $agricart_rating_reason_en = '';

    public string $agricart_rating_reason_ur = '';

    public string $agricart_rating_revision_en = '';

    public string $agricart_rating_revision_ur = '';

    public string $brand_review_notice_en = '';

    public string $brand_review_notice_ur = '';

    #[Locked]
    public string $ai_status = 'none';

    public bool $saving = false;

    public bool $enriching = false;

    public bool $preserveAgricartAiReview = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        $this->enrichmentPrompt = BrandEnrichmentService::defaultPrompt();
        $this->agricart_rating_criteria = BrandAgricartReviewCriteria::normalizeDetailed(null);
        $this->saveCriterionMessageAsDefault = $this->emptySaveAsDefaultFlags();
    }

    public function setAgricartCriterion(string $key, int|string $value): void
    {
        $this->authorizeAction($this->mutationAction());

        if ($this->isReadonly() || ! in_array($key, BrandAgricartReviewCriteria::keys(), true)) {
            return;
        }

        $this->preserveAgricartAiReview = false;
        $this->agricart_rating_criteria = BrandAgricartReviewCriteria::normalizeDetailed($this->agricart_rating_criteria);

        $rating = null;

        if ($value !== '' && $value !== 'clear' && $value !== null) {
            $asInt = (int) $value;

            if ($asInt >= BrandAgricartReviewCriteria::MIN_RATING && $asInt <= BrandAgricartReviewCriteria::MAX_RATING) {
                $rating = BrandAgricartReviewCriteria::clampRating($asInt);
            }
        }

        $this->agricart_rating_criteria[$key]['rating'] = $rating;

        if ($rating !== null) {
            $suggestion = app(BrandAgricartReviewMessageService::class)->suggestionFor($key, $rating);
            $this->agricart_rating_criteria[$key]['reason_en'] = $suggestion['en'];
            $this->agricart_rating_criteria[$key]['reason_ur'] = $suggestion['ur'];
        } else {
            $this->agricart_rating_criteria[$key]['reason_en'] = '';
            $this->agricart_rating_criteria[$key]['reason_ur'] = '';
        }

        $this->saveCriterionMessageAsDefault[$key] = false;
        $this->syncAgricartReviewFromCriteria();
    }

    public function updatedAgricartRatingCriteria(): void
    {
        if ($this->isReadonly()) {
            return;
        }

        $this->agricart_rating_criteria = BrandAgricartReviewCriteria::normalizeDetailed($this->agricart_rating_criteria);

        if ($this->preserveAgricartAiReview) {
            $score = BrandAgricartReviewCriteria::score($this->agricart_rating_criteria);
            $this->agricart_rating = $score === null ? '' : (string) $score;

            return;
        }

        $this->syncAgricartReviewFromCriteria();
    }

    public function clearAgricartReview(): void
    {
        $this->authorizeAction($this->mutationAction());

        if ($this->isReadonly()) {
            return;
        }

        $this->preserveAgricartAiReview = false;
        $this->agricart_rating_criteria = BrandAgricartReviewCriteria::normalizeDetailed(null);
        $this->saveCriterionMessageAsDefault = $this->emptySaveAsDefaultFlags();
        $this->agricart_rating = '';
        $this->agricart_rating_reason_en = '';
        $this->agricart_rating_reason_ur = '';
        $this->agricart_rating_revision_en = '';
        $this->agricart_rating_revision_ur = '';
    }

    public function openPrintSheet(): void
    {
        $this->authorizeAction('view');

        if ($this->brandId === null) {
            $this->setStatus(__('agricart.brands.print.save_first'), 'warning');
            $this->dispatchBusyState();

            return;
        }

        $this->dispatch(
            'brands-open-print',
            url: route('admin.brands.print', [
                'brand' => $this->brandId,
                'locale' => app()->getLocale() === 'ur' ? 'ur' : 'en',
            ]),
        );
    }

    public function generateAgricartReviewWithAi(): void
    {
        $this->authorizeAction($this->mutationAction());

        if ($this->isReadonly() || $this->enriching) {
            return;
        }

        if (! filled(trim($this->name_en))) {
            $this->setStatus(__('agricart.brands.ai.review_name_required'), 'warning');
            $this->dispatchBusyState();

            return;
        }

        $this->agricart_rating_criteria = BrandAgricartReviewCriteria::normalizeDetailed($this->agricart_rating_criteria);

        if (! BrandAgricartReviewCriteria::allOutcomesSet($this->agricart_rating_criteria)) {
            $this->setStatus(__('agricart.brands.ai.review_outcomes_required'), 'warning');
            $this->dispatchBusyState();

            return;
        }

        $lockKey = 'brand-ai-agricart-review:'.(auth()->id() ?? 'guest').':'.($this->brandId ?? 'new');
        $aiLock = Cache::lock($lockKey, 180);

        if (! $aiLock->get()) {
            $this->setStatus(__('agricart.brands.ai.review_already_running'), 'warning');
            $this->dispatchBusyState();

            return;
        }

        $this->enriching = true;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $result = app(BrandAgricartReviewAiService::class)->generate(
                $this->name_en,
                $this->name_ur,
                $this->agricart_rating_criteria,
            );

            if (! $result['success']) {
                $this->setStatus($result['message'], 'danger');

                return;
            }

            $data = $result['data'] ?? [];
            $this->applyAgricartReviewAiData(is_array($data) ? $data : []);
            $this->setStatus($result['message'], 'success');
        } catch (\Throwable $exception) {
            $this->setStatus(FriendlyError::message($exception), 'danger');
        } finally {
            $aiLock->release();
            $this->enriching = false;
            $this->dispatchBusyState();
        }
    }

    /**
     * @param  array{criteria?: array<string, array{reason_en?: string, reason_ur?: string}>, review_en?: string, review_ur?: string}  $data
     */
    private function applyAgricartReviewAiData(array $data): void
    {
        $reviewEn = trim((string) ($data['review_en'] ?? ''));
        $reviewUr = trim((string) ($data['review_ur'] ?? ''));
        $hasPolished = $reviewEn !== '' && $reviewUr !== '';

        // Set first so any Livewire updated* hooks during criteria mutation keep the AI text.
        $this->preserveAgricartAiReview = $hasPolished;

        $next = BrandAgricartReviewCriteria::normalizeDetailed($this->agricart_rating_criteria);
        $reasons = is_array($data['criteria'] ?? null) ? $data['criteria'] : [];

        foreach (BrandAgricartReviewCriteria::keys() as $key) {
            $row = $reasons[$key] ?? null;

            if (! is_array($row)) {
                continue;
            }

            if (isset($row['reason_en']) && is_string($row['reason_en'])) {
                $next[$key]['reason_en'] = trim($row['reason_en']);
            }

            if (isset($row['reason_ur']) && is_string($row['reason_ur'])) {
                $next[$key]['reason_ur'] = trim($row['reason_ur']);
            }
        }

        $this->agricart_rating_criteria = $next;

        $score = BrandAgricartReviewCriteria::score($this->agricart_rating_criteria);
        $this->agricart_rating = $score === null ? '' : (string) $score;

        if ($hasPolished) {
            $this->agricart_rating_reason_en = $reviewEn;
            $this->agricart_rating_reason_ur = $reviewUr;
            $this->preserveAgricartAiReview = true;

            return;
        }

        $this->preserveAgricartAiReview = false;
        $composed = BrandAgricartReviewCriteria::composeReview($this->agricart_rating_criteria);
        $this->agricart_rating_reason_en = $composed['en'] ?? '';
        $this->agricart_rating_reason_ur = $composed['ur'] ?? '';
    }

    private function syncAgricartReviewFromCriteria(): void
    {
        $score = BrandAgricartReviewCriteria::score($this->agricart_rating_criteria);
        $this->agricart_rating = $score === null ? '' : (string) $score;

        if ($this->preserveAgricartAiReview) {
            return;
        }

        $composed = BrandAgricartReviewCriteria::composeReview($this->agricart_rating_criteria);
        $this->agricart_rating_reason_en = $composed['en'] ?? '';
        $this->agricart_rating_reason_ur = $composed['ur'] ?? '';
    }

    public function openForm(?int $brandId = null, string $mode = 'create'): void
    {
        $normalizedMode = in_array($mode, ['create', 'edit', 'view'], true) ? $mode : 'create';
        $this->authorizeAction(
            $brandId === null
                ? 'create'
                : ($normalizedMode === 'view' ? 'view' : 'edit'),
        );

        $this->resetForm();
        $this->mode = $normalizedMode;
        $this->brandId = $brandId;

        if ($brandId) {
            $this->loadBrand($brandId);
        }
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

        if (filled($cleanName) && ! filled(trim($this->name_en))) {
            $this->name_en = $cleanName;

            if (! filled($this->slug)) {
                $this->slug = Str::slug($cleanName);
            }
        }
    }

    public function enrichWithAi(): void
    {
        $this->authorizeAction($this->mutationAction());

        if ($this->isReadonly() || $this->enriching) {
            return;
        }

        if (! filled(trim($this->name_en))) {
            $this->setStatus(__('agricart.brands.ai.name_required'), 'warning');
            $this->enriching = false;
            $this->dispatchBusyState();

            return;
        }

        if (! filled(trim($this->enrichmentPrompt))) {
            $this->setStatus(__('agricart.brands.ai.prompt_required'), 'warning');
            $this->enriching = false;
            $this->dispatchBusyState();

            return;
        }

        $lockKey = 'brand-ai-enrichment:'.(auth()->id() ?? 'guest').':'.($this->brandId ?? 'new');
        $aiLock = Cache::lock($lockKey, 120);

        if (! $aiLock->get()) {
            $this->setStatus(__('agricart.brands.ai.already_running'), 'warning');
            $this->dispatchBusyState();

            return;
        }

        $this->enriching = true;
        $this->ai_status = CategoryAiStatus::Pending->value;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $result = app(BrandEnrichmentService::class)->enrich(
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
            $this->setStatus($result['message'], 'success');
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
            $this->dispatch('brands-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('brands-saved');
            $this->resetForm();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        return view('livewire.brands.form');
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

        $originalImagePath = $this->brandId
            ? Brand::query()->whereKey($this->brandId)->value('image_path')
            : null;
        $this->storedImagePath = $originalImagePath;
        $missing = [];

        if (! filled(trim($this->name_en))) {
            $missing[] = __('agricart.brands.fields.name_en');
        }

        if (! filled(trim($this->name_ur))) {
            $missing[] = __('agricart.brands.fields.name_ur');
        }

        if (! filled($this->storedImagePath) && $this->imageUpload === null) {
            $missing[] = __('agricart.brands.fields.image');
        }

        if ($this->status === 'inactive') {
            if (! filled(trim($this->inactive_reason_en))) {
                $missing[] = __('agricart.brands.fields.inactive_reason_en');
            }

            if (! filled(trim($this->inactive_reason_ur))) {
                $missing[] = __('agricart.brands.fields.inactive_reason_ur');
            }
        }

        $this->fillMissingAgricartReasonsFromSuggestions();

        $criteria = BrandAgricartReviewCriteria::normalizeDetailed($this->agricart_rating_criteria);

        if (BrandAgricartReviewCriteria::hasAnyAnswer($criteria) && ! BrandAgricartReviewCriteria::isComplete($criteria)) {
            $this->setStatus(__('agricart.brands.messages.review_criteria_incomplete'), 'danger');
            $this->dispatchBusyState();

            return false;
        }

        if ($missing !== []) {
            $this->setStatus(
                __('agricart.brands.messages.required_fields', ['fields' => implode(', ', $missing)]),
                'danger',
            );
            $this->dispatchBusyState();

            return false;
        }

        $this->saving = true;
        $this->dispatchBusyState();
        $newImagePath = null;
        $committed = false;

        try {
            $newImagePath = $this->persistUploadedImage();
            $service = app(BrandService::class);
            $payload = $this->buildPayload($service);

            if ($this->brandId) {
                $brand = Brand::query()->findOrFail($this->brandId);
                $service->update($brand, $payload);
            } else {
                $brand = $service->create($payload);
                $this->brandId = $brand->id;
                $this->code = $brand->code;
                $this->mode = 'edit';
            }

            $committed = true;
            $this->persistSiteReviewNotice();
            $this->persistCriterionMessageDefaults();

            if (filled($originalImagePath) && $originalImagePath !== $this->storedImagePath) {
                app(MediaLibraryService::class)->deleteFileIfUnused($originalImagePath);
            }

            $this->storedImagePath = $payload['image_path'] ?? $this->storedImagePath;
            $this->imagePreviewUrl = filled($this->storedImagePath)
                ? Storage::disk('public')->url($this->storedImagePath)
                : null;
            $this->setStatus(__('agricart.brands.messages.saved'), 'success');

            return true;
        } catch (ValidationException $exception) {
            if (! $committed && filled($newImagePath)) {
                app(MediaLibraryService::class)->deleteFileIfUnused($newImagePath);
                $this->restoreOriginalImage($originalImagePath);
            }

            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('agricart.brands.messages.validation_failed'), 'danger');

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
        $this->dispatch('brand-form-busy', enriching: $this->enriching, saving: $this->saving);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(BrandService $service): array
    {
        if (! filled($this->slug) && filled($this->name_en)) {
            $this->slug = $service->uniqueSlug($service->slugFromName($this->name_en), $this->brandId);
        }

        return [
            'code' => $this->code ?: null,
            'slug' => $this->slug,
            'status' => $this->status ?: 'active',
            'inactive_reason_en' => $this->inactive_reason_en ?: null,
            'inactive_reason_ur' => $this->inactive_reason_ur ?: null,
            'agricart_rating' => filled($this->agricart_rating) ? (int) $this->agricart_rating : null,
            'agricart_rating_criteria' => BrandAgricartReviewCriteria::normalizeDetailed($this->agricart_rating_criteria),
            'agricart_rating_reason_en' => $this->agricart_rating_reason_en ?: null,
            'agricart_rating_reason_ur' => $this->agricart_rating_reason_ur ?: null,
            'agricart_rating_revision_en' => $this->agricart_rating_revision_en ?: null,
            'agricart_rating_revision_ur' => $this->agricart_rating_revision_ur ?: null,
            'name_en' => $this->name_en,
            'name_ur' => $this->name_ur,
            'image_path' => $this->storedImagePath,
            'description_en' => $this->description_en ?: null,
            'description_ur' => $this->description_ur ?: null,
            'website_url' => filled($this->website_url) ? $this->website_url : null,
            'ai_status' => $this->ai_status ?: 'none',
        ];
    }

    private function persistUploadedImage(): ?string
    {
        if ($this->imageUpload === null) {
            return null;
        }

        $path = app(OptimizedImageStorage::class)->store($this->imageUpload, 'brands');
        $this->storedImagePath = $path;
        $this->imagePreviewUrl = Storage::disk('public')->url($path);
        $this->imageUpload = null;

        return $path;
    }

    private function resolveImagePathForAi(): ?string
    {
        if ($this->imageUpload !== null) {
            return $this->imageUpload->getRealPath() ?: null;
        }

        if (filled($this->storedImagePath)) {
            $path = Storage::disk('public')->path($this->storedImagePath);

            return is_readable($path) ? $path : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyEnrichmentData(array $data): void
    {
        foreach (['name_ur', 'description_en', 'description_ur', 'slug'] as $field) {
            if (filled($data[$field] ?? null)) {
                $this->{$field} = (string) $data[$field];
            }
        }

        if (filled($data['ai_status'] ?? null)) {
            $this->ai_status = (string) $data['ai_status'];
        }
    }

    private function loadBrand(int $brandId): void
    {
        $brand = Brand::query()->findOrFail($brandId);

        $this->name_en = $brand->name_en;
        $this->name_ur = $brand->name_ur ?? '';
        $this->description_en = $brand->description_en ?? '';
        $this->description_ur = $brand->description_ur ?? '';
        $this->website_url = $brand->website_url ?? '';
        $this->code = $brand->code;
        $this->slug = $brand->slug;
        $this->status = $brand->status;
        $this->inactive_reason_en = $brand->inactive_reason_en ?? '';
        $this->inactive_reason_ur = $brand->inactive_reason_ur ?? '';
        $this->agricart_rating_criteria = BrandAgricartReviewCriteria::normalizeDetailed($brand->agricart_rating_criteria);
        $this->saveCriterionMessageAsDefault = $this->emptySaveAsDefaultFlags();
        $this->syncAgricartReviewFromCriteria();

        if ($this->agricart_rating_reason_en === '' && filled($brand->agricart_rating_reason_en)) {
            $this->agricart_rating_reason_en = (string) $brand->agricart_rating_reason_en;
        }

        if ($this->agricart_rating_reason_ur === '' && filled($brand->agricart_rating_reason_ur)) {
            $this->agricart_rating_reason_ur = (string) $brand->agricart_rating_reason_ur;
        }

        $this->agricart_rating_revision_en = $brand->agricart_rating_revision_en ?? '';
        $this->agricart_rating_revision_ur = $brand->agricart_rating_revision_ur ?? '';
        $this->loadSiteReviewNotice();
        $this->ai_status = $brand->ai_status?->value ?? 'none';
        $this->storedImagePath = $brand->image_path;
        $this->imagePreviewUrl = filled($brand->image_path)
            ? Storage::disk('public')->url($brand->image_path)
            : null;
        $this->enrichmentPrompt = BrandEnrichmentService::defaultPrompt();
    }

    private function resetForm(): void
    {
        $this->brandId = null;
        $this->mode = 'create';
        $this->imageUpload = null;
        $this->imagePreviewUrl = null;
        $this->storedImagePath = null;
        $this->enrichmentPrompt = BrandEnrichmentService::defaultPrompt();
        $this->name_en = '';
        $this->name_ur = '';
        $this->description_en = '';
        $this->description_ur = '';
        $this->website_url = '';
        $this->code = app(BrandService::class)->nextCode();
        $this->slug = '';
        $this->status = 'active';
        $this->inactive_reason_en = '';
        $this->inactive_reason_ur = '';
        $this->agricart_rating = '';
        $this->agricart_rating_criteria = BrandAgricartReviewCriteria::normalizeDetailed(null);
        $this->saveCriterionMessageAsDefault = $this->emptySaveAsDefaultFlags();
        $this->agricart_rating_reason_en = '';
        $this->agricart_rating_reason_ur = '';
        $this->agricart_rating_revision_en = '';
        $this->agricart_rating_revision_ur = '';
        $this->preserveAgricartAiReview = false;
        $this->loadSiteReviewNotice();
        $this->ai_status = 'none';
        $this->saving = false;
        $this->enriching = false;
        $this->statusMessage = null;
        $this->statusType = 'success';
    }

    private function loadSiteReviewNotice(): void
    {
        $settings = StoreSetting::current();
        $this->brand_review_notice_en = filled($settings->brand_review_notice_en)
            ? (string) $settings->brand_review_notice_en
            : BrandAgricartReviewNotice::defaultEnglish();
        $this->brand_review_notice_ur = filled($settings->brand_review_notice_ur)
            ? (string) $settings->brand_review_notice_ur
            : BrandAgricartReviewNotice::defaultUrdu();
    }

    private function persistSiteReviewNotice(): void
    {
        StoreSetting::current()->update([
            'brand_review_notice_en' => filled(trim($this->brand_review_notice_en))
                ? trim($this->brand_review_notice_en)
                : BrandAgricartReviewNotice::defaultEnglish(),
            'brand_review_notice_ur' => filled(trim($this->brand_review_notice_ur))
                ? trim($this->brand_review_notice_ur)
                : BrandAgricartReviewNotice::defaultUrdu(),
        ]);
    }

    private function persistCriterionMessageDefaults(): void
    {
        $service = app(BrandAgricartReviewMessageService::class);
        $criteria = BrandAgricartReviewCriteria::normalizeDetailed($this->agricart_rating_criteria);

        foreach (BrandAgricartReviewCriteria::keys() as $key) {
            if (! ($this->saveCriterionMessageAsDefault[$key] ?? false)) {
                continue;
            }

            $detail = $criteria[$key] ?? null;

            if (! is_array($detail) || $detail['rating'] === null) {
                continue;
            }

            $band = BrandAgricartReviewCriteria::bandForRating((int) $detail['rating']);

            if ($band === null) {
                continue;
            }

            $service->saveBandDefault($key, $band, [
                'en' => (string) ($detail['reason_en'] ?? ''),
                'ur' => (string) ($detail['reason_ur'] ?? ''),
            ]);

            $this->saveCriterionMessageAsDefault[$key] = false;
        }
    }

    /**
     * Ensure rated rows always have bilingual reasons (from manageable defaults) before save.
     */
    private function fillMissingAgricartReasonsFromSuggestions(): void
    {
        $filled = app(BrandAgricartReviewMessageService::class)
            ->fillMissingReasons($this->agricart_rating_criteria);

        $this->agricart_rating_criteria = $filled;

        if (! $this->preserveAgricartAiReview) {
            $this->syncAgricartReviewFromCriteria();
        }
    }

    /**
     * @return array<string, bool>
     */
    private function emptySaveAsDefaultFlags(): array
    {
        $flags = [];

        foreach (BrandAgricartReviewCriteria::keys() as $key) {
            $flags[$key] = false;
        }

        return $flags;
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
        return $this->brandId === null ? 'create' : 'edit';
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

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
