<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\BrandAgricartReviewMessageService;
use App\Services\Catalog\ProductAgricartReviewAiService;
use App\Services\Catalog\ProductService;
use App\Support\Catalog\BrandAgricartReviewCriteria;
use App\Support\FriendlyError;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AgricartReviewEditor extends Component
{
    public string $mode = 'create';

    public string $product_search = '';

    public ?int $productId = null;

    public string $productNameEn = '';

    public string $productNameUr = '';

    public string $brandNameEn = '';

    public string $categoryNameEn = '';

    public string $agricart_rating = '';

    /** @var array<string, array{rating: int|null, reason_en: string, reason_ur: string}> */
    public array $agricart_rating_criteria = [];

    /** @var array<string, bool> */
    public array $saveCriterionMessageAsDefault = [];

    public string $agricart_rating_reason_en = '';

    public string $agricart_rating_reason_ur = '';

    public string $agricart_rating_revision_en = '';

    public string $agricart_rating_revision_ur = '';

    public bool $preserveAgricartAiReview = false;

    public bool $saving = false;

    public bool $enriching = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    public function mount(): void
    {
        abort_unless($this->userCan('view'), 403);
        $this->agricart_rating_criteria = BrandAgricartReviewCriteria::normalizeDetailed(null);
        $this->saveCriterionMessageAsDefault = $this->emptySaveAsDefaultFlags();
    }

    public function openForm(?int $productId = null, string $mode = 'create'): void
    {
        $normalizedMode = in_array($mode, ['create', 'edit', 'view'], true) ? $mode : 'create';
        $this->authorizeAction(
            $productId === null
                ? 'create'
                : ($normalizedMode === 'view' ? 'view' : 'edit'),
        );

        $this->mode = $normalizedMode;
        $this->statusMessage = null;
        $this->clearSelectedProduct();

        if ($productId !== null) {
            $this->selectProduct($productId);
        }

        $this->dispatchBusyState();
    }

    public function handlePanelAction(string $action): void
    {
        if ($this->isReadonly() && in_array($action, ['save_close', 'save_next', 'ai'], true)) {
            $this->dispatchBusyState();

            return;
        }

        match ($action) {
            'ai' => $this->generateAgricartReviewWithAi(),
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
    }

    public function selectProduct(int $productId): void
    {
        abort_unless($this->userCan('view'), 403);

        $product = Product::query()
            ->with(['brand:id,name_en', 'category:id,name_en'])
            ->findOrFail($productId);

        $this->productId = $product->id;
        $this->product_search = '';
        $this->productNameEn = (string) $product->name_en;
        $this->productNameUr = (string) $product->name_ur;
        $this->brandNameEn = (string) ($product->brand?->name_en ?? '');
        $this->categoryNameEn = (string) ($product->category?->name_en ?? '');
        $this->agricart_rating_criteria = BrandAgricartReviewCriteria::normalizeDetailed($product->agricart_rating_criteria);
        $this->saveCriterionMessageAsDefault = $this->emptySaveAsDefaultFlags();
        $this->agricart_rating = $product->agricart_rating === null ? '' : (string) $product->agricart_rating;
        $this->agricart_rating_reason_en = (string) ($product->agricart_rating_reason_en ?? '');
        $this->agricart_rating_reason_ur = (string) ($product->agricart_rating_reason_ur ?? '');
        $this->agricart_rating_revision_en = (string) ($product->agricart_rating_revision_en ?? '');
        $this->agricart_rating_revision_ur = (string) ($product->agricart_rating_revision_ur ?? '');
        $this->preserveAgricartAiReview = filled($this->agricart_rating_reason_en) && filled($this->agricart_rating_reason_ur);
        $this->statusMessage = null;
    }

    public function clearSelectedProduct(): void
    {
        $this->productId = null;
        $this->product_search = '';
        $this->productNameEn = '';
        $this->productNameUr = '';
        $this->brandNameEn = '';
        $this->categoryNameEn = '';
        $this->clearAgricartReview();
        $this->statusMessage = null;
    }

    public function setAgricartCriterion(string $key, int|string $value): void
    {
        abort_unless($this->userCan('edit'), 403);

        if ($this->isReadonly() || $this->productId === null || ! in_array($key, BrandAgricartReviewCriteria::keys(), true)) {
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
        if ($this->isReadonly() || $this->productId === null) {
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
        $this->preserveAgricartAiReview = false;
        $this->agricart_rating_criteria = BrandAgricartReviewCriteria::normalizeDetailed(null);
        $this->saveCriterionMessageAsDefault = $this->emptySaveAsDefaultFlags();
        $this->agricart_rating = '';
        $this->agricart_rating_reason_en = '';
        $this->agricart_rating_reason_ur = '';
        $this->agricart_rating_revision_en = '';
        $this->agricart_rating_revision_ur = '';
    }

    public function generateAgricartReviewWithAi(): void
    {
        abort_unless($this->userCan('edit'), 403);

        if ($this->isReadonly() || $this->productId === null || $this->enriching) {
            $this->dispatchBusyState();

            return;
        }

        if (! filled(trim($this->productNameEn))) {
            $this->setStatus(__('agricart.products.ai.review_name_required'), 'warning');
            $this->dispatchBusyState();

            return;
        }

        $this->agricart_rating_criteria = BrandAgricartReviewCriteria::normalizeDetailed($this->agricart_rating_criteria);

        if (! BrandAgricartReviewCriteria::allOutcomesSet($this->agricart_rating_criteria)) {
            $this->setStatus(__('agricart.brands.ai.review_outcomes_required'), 'warning');
            $this->dispatchBusyState();

            return;
        }

        $lockKey = 'product-ai-agricart-review:'.(auth()->id() ?? 'guest').':'.$this->productId;
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
            $result = app(ProductAgricartReviewAiService::class)->generate(
                $this->productNameEn,
                $this->productNameUr,
                $this->brandNameEn !== '' ? $this->brandNameEn : null,
                $this->categoryNameEn !== '' ? $this->categoryNameEn : null,
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

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('product-reviews-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('product-reviews-saved');
            $this->mode = 'create';
            $this->clearSelectedProduct();
            $this->setStatus(__('agricart.products.review_page.messages.saved_next'), 'success');
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view';
    }

    public function render(): View
    {
        abort_unless($this->userCan('view'), 403);

        return view('livewire.products.agricart-review-editor', [
            'productOptions' => $this->searchProducts($this->product_search),
            'canEdit' => ! $this->isReadonly() && $this->userCan('edit'),
            'canChangeProduct' => $this->mode === 'create' && ! $this->isReadonly(),
        ]);
    }

    private function persist(): bool
    {
        abort_unless($this->userCan('edit'), 403);

        if ($this->isReadonly() || $this->productId === null || $this->saving) {
            $this->dispatchBusyState();

            return false;
        }

        $this->fillMissingAgricartReasonsFromSuggestions();
        $criteria = BrandAgricartReviewCriteria::normalizeDetailed($this->agricart_rating_criteria);

        if (BrandAgricartReviewCriteria::hasAnyAnswer($criteria) && ! BrandAgricartReviewCriteria::isComplete($criteria)) {
            $this->setStatus(__('agricart.products.messages.review_criteria_incomplete'), 'danger');
            $this->dispatchBusyState();

            return false;
        }

        if (! BrandAgricartReviewCriteria::hasAnyAnswer($criteria)) {
            $this->setStatus(__('agricart.products.review_page.messages.ratings_required'), 'warning');
            $this->dispatchBusyState();

            return false;
        }

        $this->saving = true;
        $this->dispatchBusyState();

        try {
            $product = Product::query()->findOrFail($this->productId);
            app(ProductService::class)->updateAgricartReview($product, [
                'agricart_rating' => filled($this->agricart_rating) ? (int) $this->agricart_rating : null,
                'agricart_rating_criteria' => $this->agricart_rating_criteria,
                'agricart_rating_reason_en' => $this->agricart_rating_reason_en ?: null,
                'agricart_rating_reason_ur' => $this->agricart_rating_reason_ur ?: null,
                'agricart_rating_revision_en' => $this->agricart_rating_revision_en ?: null,
                'agricart_rating_revision_ur' => $this->agricart_rating_revision_ur ?: null,
            ]);
            $this->persistCriterionMessageDefaults();
            $this->selectProduct($product->id);
            $this->setStatus(__('agricart.products.messages.review_saved'), 'success');

            return true;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('agricart.products.messages.validation_failed'), 'danger');

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
     * @return array<int, string>
     */
    private function searchProducts(string $search): array
    {
        $term = trim($search);

        if (mb_strlen($term) < 2) {
            return [];
        }

        $needle = '%'.$term.'%';

        return Product::query()
            ->with('brand:id,name_en')
            ->where(function ($query) use ($needle): void {
                $query->where('name_en', 'like', $needle)
                    ->orWhere('name_ur', 'like', $needle)
                    ->orWhere('code', 'like', $needle)
                    ->orWhere('sku', 'like', $needle)
                    ->orWhere('slug', 'like', $needle);
            })
            ->orderBy('name_en')
            ->limit(20)
            ->get()
            ->mapWithKeys(function (Product $product): array {
                $brand = $product->brand?->name_en;
                $label = $product->name_en;
                if (filled($brand)) {
                    $label .= ' — '.$brand;
                }
                if (filled($product->code)) {
                    $label .= ' ('.$product->code.')';
                }
                if ($product->agricart_rating !== null) {
                    $label .= ' · '.$product->agricart_rating.'/'.BrandAgricartReviewCriteria::scoreMax();
                }

                return [$product->id => $label];
            })
            ->all();
    }

    /**
     * @param  array{criteria?: array<string, array{reason_en?: string, reason_ur?: string}>, review_en?: string, review_ur?: string}  $data
     */
    private function applyAgricartReviewAiData(array $data): void
    {
        $reviewEn = trim((string) ($data['review_en'] ?? ''));
        $reviewUr = trim((string) ($data['review_ur'] ?? ''));
        $hasPolished = $reviewEn !== '' && $reviewUr !== '';

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

    private function setStatus(string $message, string $type = 'success'): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('product-review-form-busy', enriching: $this->enriching, saving: $this->saving);
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
}
