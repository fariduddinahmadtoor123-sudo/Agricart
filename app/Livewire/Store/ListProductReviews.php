<?php

namespace App\Livewire\Store;

use App\Models\ProductReview;
use App\Models\ProductReviewPhoto;
use App\Models\ProductReviewReport;
use App\Models\User;
use App\Services\Store\ProductReviewService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListProductReviews extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['product', 'customer', 'rating', 'body', 'status', 'received', 'actions'];

    public string $search = '';

    public string $statusFilter = 'pending';

    public ?int $replyReviewId = null;

    public string $replyText = '';

    public function mount(): void
    {
        abort_unless($this->userCan('view'), 403);
    }

    #[On('store-reviews-search')]
    public function updateSearch(string $query): void
    {
        $this->search = trim($query);
        $this->resetPage();
    }

    #[On('store-reviews-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status' && in_array($value, [...ProductReview::STATUSES, 'all'], true)) {
            $this->statusFilter = $value;
            $this->resetPage();
        }
    }

    #[On('store-reviews-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    #[On('store-reviews-column-toggle')]
    public function toggleColumn(string $column, bool $visible): void
    {
        if ($visible && ! in_array($column, $this->visibleColumns, true)) {
            $this->visibleColumns[] = $column;
        }

        if (! $visible) {
            $this->visibleColumns = array_values(array_filter(
                $this->visibleColumns,
                fn (string $item): bool => $item !== $column,
            ));
        }

        if ($this->visibleColumns === []) {
            $this->visibleColumns = ['body'];
        }

        $this->dispatch('store-reviews-columns-sync', columns: $this->visibleColumns);
    }

    public function approve(int $reviewId, ProductReviewService $reviews): void
    {
        $this->setStatus($reviewId, ProductReview::STATUS_APPROVED, $reviews);
    }

    public function hide(int $reviewId, ProductReviewService $reviews): void
    {
        $this->setStatus($reviewId, ProductReview::STATUS_HIDDEN, $reviews);
    }

    public function reject(int $reviewId, ProductReviewService $reviews): void
    {
        $this->setStatus($reviewId, ProductReview::STATUS_REJECTED, $reviews);
    }

    public function pending(int $reviewId, ProductReviewService $reviews): void
    {
        $this->setStatus($reviewId, ProductReview::STATUS_PENDING, $reviews);
    }

    public function delete(int $reviewId, ProductReviewService $reviews): void
    {
        $this->authorizeMutation();

        $review = ProductReview::query()->with('photos')->findOrFail($reviewId);
        $reviews->delete($review);
        $this->resetPage();
    }

    public function deletePhoto(int $photoId, ProductReviewService $reviews): void
    {
        $this->authorizeMutation();

        $photo = ProductReviewPhoto::query()->findOrFail($photoId);
        $reviews->deletePhoto($photo);
    }

    public function openReply(int $reviewId): void
    {
        $this->authorizeMutation();

        $review = ProductReview::query()->findOrFail($reviewId);
        $this->replyReviewId = $review->id;
        $this->replyText = (string) ($review->admin_reply ?? '');
    }

    public function saveReply(ProductReviewService $reviews): void
    {
        $this->authorizeMutation();

        if ($this->replyReviewId === null) {
            return;
        }

        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $review = ProductReview::query()->findOrFail($this->replyReviewId);
        $reviews->setAdminReply($review, $this->replyText, $user);
        $this->replyReviewId = null;
        $this->replyText = '';
    }

    public function clearReply(int $reviewId, ProductReviewService $reviews): void
    {
        $this->authorizeMutation();

        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $review = ProductReview::query()->findOrFail($reviewId);
        $reviews->setAdminReply($review, null, $user);

        if ($this->replyReviewId === $reviewId) {
            $this->replyReviewId = null;
            $this->replyText = '';
        }
    }

    public function resolveReport(int $reportId, ProductReviewService $reviews): void
    {
        $this->authorizeMutation();

        $report = ProductReviewReport::query()->findOrFail($reportId);
        $reviews->resolveReport($report, ProductReviewReport::STATUS_RESOLVED);
    }

    public function dismissReport(int $reportId, ProductReviewService $reviews): void
    {
        $this->authorizeMutation();

        $report = ProductReviewReport::query()->findOrFail($reportId);
        $reviews->resolveReport($report, ProductReviewReport::STATUS_DISMISSED);
    }

    public function resolveReportAndHide(int $reportId, ProductReviewService $reviews): void
    {
        $this->authorizeMutation();

        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $report = ProductReviewReport::query()->with('review')->findOrFail($reportId);
        $reviews->resolveReport($report, ProductReviewReport::STATUS_RESOLVED);

        if ($report->review !== null) {
            $reviews->setStatus($report->review, ProductReview::STATUS_HIDDEN, $user);
        }
    }

    public function render(): View
    {
        abort_unless($this->userCan('view'), 403);

        $items = ProductReview::query()
            ->with([
                'product:id,name_en,name_ur,slug',
                'customer',
                'photos',
                'reports' => fn ($query) => $query->where('status', ProductReviewReport::STATUS_OPEN),
            ])
            ->withCount([
                'reports as open_reports_count' => fn ($query) => $query->where('status', ProductReviewReport::STATUS_OPEN),
            ])
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $this->search).'%';

                $query->where(function ($inner) use ($needle): void {
                    $inner->where('body', 'like', $needle)
                        ->orWhereHas('product', function ($product) use ($needle): void {
                            $product->where('name_en', 'like', $needle)
                                ->orWhere('name_ur', 'like', $needle)
                                ->orWhere('slug', 'like', $needle);
                        })
                        ->orWhereHas('customer', function ($customer) use ($needle): void {
                            $customer->where('first_name', 'like', $needle)
                                ->orWhere('last_name', 'like', $needle)
                                ->orWhere('display_name', 'like', $needle)
                                ->orWhere('email', 'like', $needle);
                        });
                });
            })
            ->latest('id')
            ->paginate(20);

        $statusCounts = ProductReview::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();

        return view('livewire.store.list-product-reviews', [
            'reviews' => $items,
            'canEdit' => $this->userCan('edit'),
            'statusCounts' => $statusCounts,
        ]);
    }

    private function setStatus(int $reviewId, string $status, ProductReviewService $reviews): void
    {
        $this->authorizeMutation();

        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $review = ProductReview::query()->findOrFail($reviewId);
        $reviews->setStatus($review, $status, $user);
    }

    private function authorizeMutation(): void
    {
        abort_unless($this->userCan('edit'), 403);
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->isSuperAdmin() || $user->hasPermission('store.'.$action);
    }
}
