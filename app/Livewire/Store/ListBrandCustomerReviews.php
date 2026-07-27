<?php

namespace App\Livewire\Store;

use App\Models\BrandCustomerReview;
use App\Models\User;
use App\Services\Store\BrandCustomerReviewService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListBrandCustomerReviews extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['brand', 'customer', 'rating', 'body', 'status', 'received', 'actions'];

    public string $search = '';

    public string $statusFilter = 'pending';

    #[Locked]
    public ?int $viewId = null;

    public string $replyText = '';

    public function mount(): void
    {
        abort_unless($this->userCan('view'), 403);
    }

    #[On('store-brand-reviews-search')]
    public function updateSearch(string $query): void
    {
        $this->search = trim($query);
        $this->resetPage();
    }

    #[On('store-brand-reviews-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status' && in_array($value, [...BrandCustomerReview::STATUSES, 'all'], true)) {
            $this->statusFilter = $value;
            $this->resetPage();
        }
    }

    #[On('store-brand-reviews-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    #[On('store-brand-reviews-column-toggle')]
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

        $this->dispatch('store-brand-reviews-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $id): void
    {
        abort_unless($this->userCan('view'), 403);
        $item = BrandCustomerReview::query()->findOrFail($id);
        $this->viewId = $item->id;
        $this->replyText = (string) ($item->admin_reply ?? '');
    }

    public function closeView(): void
    {
        $this->viewId = null;
        $this->replyText = '';
    }

    public function approve(int $id, BrandCustomerReviewService $service): void
    {
        $this->setStatus($id, BrandCustomerReview::STATUS_APPROVED, $service);
    }

    public function hide(int $id, BrandCustomerReviewService $service): void
    {
        $this->setStatus($id, BrandCustomerReview::STATUS_HIDDEN, $service);
    }

    public function reject(int $id, BrandCustomerReviewService $service): void
    {
        $this->setStatus($id, BrandCustomerReview::STATUS_REJECTED, $service);
    }

    public function pending(int $id, BrandCustomerReviewService $service): void
    {
        $this->setStatus($id, BrandCustomerReview::STATUS_PENDING, $service);
    }

    public function delete(int $id, BrandCustomerReviewService $service): void
    {
        $this->authorizeMutation();
        $item = BrandCustomerReview::query()->findOrFail($id);
        $service->delete($item);

        if ($this->viewId === $id) {
            $this->closeView();
        }

        $this->resetPage();
    }

    public function saveReply(BrandCustomerReviewService $service): void
    {
        $this->authorizeMutation();

        if ($this->viewId === null) {
            return;
        }

        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $item = BrandCustomerReview::query()->findOrFail($this->viewId);
        $service->setAdminReply($item, $this->replyText, $user);
    }

    public function clearReply(BrandCustomerReviewService $service): void
    {
        $this->authorizeMutation();

        if ($this->viewId === null) {
            return;
        }

        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $item = BrandCustomerReview::query()->findOrFail($this->viewId);
        $service->setAdminReply($item, null, $user);
        $this->replyText = '';
    }

    public function render(): View
    {
        abort_unless($this->userCan('view'), 403);

        $items = BrandCustomerReview::query()
            ->with(['brand', 'customer'])
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $this->search).'%';

                $query->where(function ($inner) use ($needle): void {
                    $inner->where('body', 'like', $needle)
                        ->orWhereHas('brand', function ($brand) use ($needle): void {
                            $brand->where('name_en', 'like', $needle)
                                ->orWhere('name_ur', 'like', $needle)
                                ->orWhere('code', 'like', $needle);
                        })
                        ->orWhereHas('customer', function ($customer) use ($needle): void {
                            $customer->where('first_name', 'like', $needle)
                                ->orWhere('last_name', 'like', $needle)
                                ->orWhere('email', 'like', $needle);
                        });
                });
            })
            ->latest('id')
            ->paginate(20);

        $statusCounts = BrandCustomerReview::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();

        return view('livewire.store.list-brand-customer-reviews', [
            'items' => $items,
            'statusCounts' => $statusCounts,
            'canEdit' => $this->userCan('edit'),
            'selected' => $this->viewId
                ? BrandCustomerReview::query()->with(['brand', 'customer'])->find($this->viewId)
                : null,
        ]);
    }

    private function setStatus(int $id, string $status, BrandCustomerReviewService $service): void
    {
        $this->authorizeMutation();
        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $item = BrandCustomerReview::query()->findOrFail($id);
        $service->setStatus($item, $status, $user);
    }

    private function authorizeMutation(): void
    {
        abort_unless($this->userCan('edit'), 403);
    }

    private function userCan(string $ability): bool
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return $user->can('store.'.$ability) || $user->can('store.edit');
    }
}
