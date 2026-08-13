<?php

namespace App\Livewire\Store;

use App\Models\CompanyTestimonial;
use App\Models\User;
use App\Services\Store\CompanyTestimonialService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListCompanyTestimonials extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['customer', 'rating', 'body', 'featured', 'status', 'received', 'actions'];

    public string $search = '';

    public string $statusFilter = 'pending';

    #[Locked]
    public ?int $viewId = null;

    public string $replyText = '';

    public string $manual_display_name = '';

    public string $manual_title = '';

    public int $manual_rating = 5;

    public string $manual_body = '';

    public bool $manual_featured = false;

    public function mount(): void
    {
        abort_unless($this->userCan('view'), 403);
    }

    #[On('store-testimonials-search')]
    public function updateSearch(string $query): void
    {
        $this->search = trim($query);
        $this->resetPage();
    }

    #[On('store-testimonials-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status' && in_array($value, [...CompanyTestimonial::STATUSES, 'all'], true)) {
            $this->statusFilter = $value;
            $this->resetPage();
        }
    }

    #[On('store-testimonials-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    #[On('store-testimonials-column-toggle')]
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

        $this->dispatch('store-testimonials-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $id): void
    {
        abort_unless($this->userCan('view'), 403);

        $item = CompanyTestimonial::query()->findOrFail($id);
        $this->viewId = $item->id;
        $this->replyText = (string) ($item->admin_reply ?? '');
    }

    public function closeView(): void
    {
        $this->viewId = null;
        $this->replyText = '';
    }

    public function approve(int $id, CompanyTestimonialService $service): void
    {
        $this->setStatus($id, CompanyTestimonial::STATUS_APPROVED, $service);
    }

    public function hide(int $id, CompanyTestimonialService $service): void
    {
        $this->setStatus($id, CompanyTestimonial::STATUS_HIDDEN, $service);
    }

    public function reject(int $id, CompanyTestimonialService $service): void
    {
        $this->setStatus($id, CompanyTestimonial::STATUS_REJECTED, $service);
    }

    public function pending(int $id, CompanyTestimonialService $service): void
    {
        $this->setStatus($id, CompanyTestimonial::STATUS_PENDING, $service);
    }

    public function toggleFeatured(int $id, CompanyTestimonialService $service): void
    {
        $this->authorizeMutation();
        $item = CompanyTestimonial::query()->findOrFail($id);
        $service->setFeatured($item, ! $item->is_featured);
    }

    public function delete(int $id, CompanyTestimonialService $service): void
    {
        $this->authorizeMutation();
        $item = CompanyTestimonial::query()->findOrFail($id);
        $service->delete($item);

        if ($this->viewId === $id) {
            $this->closeView();
        }

        $this->resetPage();
    }

    public function saveReply(CompanyTestimonialService $service): void
    {
        $this->authorizeMutation();

        if ($this->viewId === null) {
            return;
        }

        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $item = CompanyTestimonial::query()->findOrFail($this->viewId);
        $service->setAdminReply($item, $this->replyText, $user);
    }

    public function clearReply(CompanyTestimonialService $service): void
    {
        $this->authorizeMutation();

        if ($this->viewId === null) {
            return;
        }

        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $item = CompanyTestimonial::query()->findOrFail($this->viewId);
        $service->setAdminReply($item, null, $user);
        $this->replyText = '';
    }

    public function createManual(CompanyTestimonialService $service): void
    {
        $this->authorizeMutation();

        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $this->validate([
            'manual_display_name' => ['required', 'string', 'max:120'],
            'manual_title' => ['nullable', 'string', 'max:160'],
            'manual_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'manual_body' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $service->createFromAdmin(
            $user,
            $this->manual_display_name,
            $this->manual_rating,
            $this->manual_body,
            $this->manual_title !== '' ? $this->manual_title : null,
            $this->manual_featured,
            true,
        );

        $this->manual_display_name = '';
        $this->manual_title = '';
        $this->manual_rating = 5;
        $this->manual_body = '';
        $this->manual_featured = false;
        $this->statusFilter = 'approved';
        $this->resetPage();
    }

    public function render(): View
    {
        abort_unless($this->userCan('view'), 403);

        $items = CompanyTestimonial::query()
            ->with(['customer'])
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $this->search).'%';

                $query->where(function ($inner) use ($needle): void {
                    $inner->where('body', 'like', $needle)
                        ->orWhere('display_name', 'like', $needle)
                        ->orWhere('title', 'like', $needle)
                        ->orWhereHas('customer', function ($customer) use ($needle): void {
                            $customer->where('first_name', 'like', $needle)
                                ->orWhere('last_name', 'like', $needle)
                                ->orWhere('email', 'like', $needle);
                        });
                });
            })
            ->latest('id')
            ->paginate(20);

        $statusCounts = CompanyTestimonial::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();

        $selected = null;
        if ($this->viewId !== null) {
            $selected = CompanyTestimonial::query()->with(['customer'])->find($this->viewId);
            if ($selected === null) {
                $this->viewId = null;
            }
        }

        return view('livewire.store.list-company-testimonials', [
            'items' => $items,
            'canEdit' => $this->userCan('edit'),
            'statusCounts' => $statusCounts,
            'selected' => $selected,
        ]);
    }

    private function setStatus(int $id, string $status, CompanyTestimonialService $service): void
    {
        $this->authorizeMutation();

        $user = auth()->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $item = CompanyTestimonial::query()->findOrFail($id);
        $service->setStatus($item, $status, $user);
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

        return $user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'store', 'testimonials', $action);
    }
}
