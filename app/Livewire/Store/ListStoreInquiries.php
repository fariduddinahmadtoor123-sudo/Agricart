<?php

namespace App\Livewire\Store;

use App\Models\StoreInquiry;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListStoreInquiries extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['name', 'contact', 'message', 'status', 'received', 'actions'];

    public string $search = '';

    public string $statusFilter = 'all';

    #[Locked]
    public ?int $viewInquiryId = null;

    public function mount(): void
    {
        abort_unless($this->userCan('view'), 403);
    }

    #[On('store-messages-search')]
    public function updateSearch(string $query): void
    {
        $this->search = trim($query);
        $this->resetPage();
    }

    #[On('store-messages-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status' && in_array($value, [...StoreInquiry::STATUSES, 'all'], true)) {
            $this->statusFilter = $value;
            $this->resetPage();
        }
    }

    #[On('store-messages-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    #[On('store-messages-column-toggle')]
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
            $this->visibleColumns = ['message'];
        }

        $this->dispatch('store-messages-columns-sync', columns: $this->visibleColumns);
    }

    public function markRead(int $inquiryId): void
    {
        $this->authorizeMutation();

        StoreInquiry::query()
            ->whereKey($inquiryId)
            ->whereIn('status', [StoreInquiry::STATUS_NEW, StoreInquiry::STATUS_PENDING])
            ->update([
                'status' => StoreInquiry::STATUS_READ,
                'read_at' => now(),
                'closed_at' => null,
            ]);
    }

    public function markUnread(int $inquiryId): void
    {
        $this->authorizeMutation();

        StoreInquiry::query()
            ->whereKey($inquiryId)
            ->whereIn('status', [StoreInquiry::STATUS_READ, StoreInquiry::STATUS_PENDING])
            ->update([
                'status' => StoreInquiry::STATUS_NEW,
                'read_at' => null,
                'closed_at' => null,
            ]);
    }

    public function markPending(int $inquiryId): void
    {
        $this->authorizeMutation();

        StoreInquiry::query()
            ->whereKey($inquiryId)
            ->where('status', '!=', StoreInquiry::STATUS_CLOSED)
            ->update([
                'status' => StoreInquiry::STATUS_PENDING,
                'read_at' => now(),
                'closed_at' => null,
            ]);
    }

    public function close(int $inquiryId): void
    {
        $this->authorizeMutation();

        StoreInquiry::query()
            ->whereKey($inquiryId)
            ->where('status', '!=', StoreInquiry::STATUS_CLOSED)
            ->update([
                'status' => StoreInquiry::STATUS_CLOSED,
                'read_at' => now(),
                'closed_at' => now(),
            ]);
    }

    public function reopen(int $inquiryId): void
    {
        $this->authorizeMutation();

        StoreInquiry::query()
            ->whereKey($inquiryId)
            ->where('status', StoreInquiry::STATUS_CLOSED)
            ->update([
                'status' => StoreInquiry::STATUS_PENDING,
                'read_at' => now(),
                'closed_at' => null,
            ]);
    }

    public function openView(int $inquiryId): void
    {
        abort_unless($this->userCan('view'), 403);

        $inquiry = StoreInquiry::query()->findOrFail($inquiryId);

        if ($inquiry->status === StoreInquiry::STATUS_NEW && $this->userCan('edit')) {
            $inquiry->update([
                'status' => StoreInquiry::STATUS_READ,
                'read_at' => now(),
            ]);
        }

        $this->viewInquiryId = $inquiry->id;
    }

    public function closeView(): void
    {
        $this->viewInquiryId = null;
    }

    public function render(): View
    {
        abort_unless($this->userCan('view'), 403);

        $inquiries = StoreInquiry::query()
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $this->search).'%';

                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name', 'like', $needle)
                        ->orWhere('phone', 'like', $needle)
                        ->orWhere('email', 'like', $needle)
                        ->orWhere('message', 'like', $needle);
                });
            })
            ->latest()
            ->paginate(20);

        $statusCounts = StoreInquiry::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();

        return view('livewire.store.list-inquiries', [
            'inquiries' => $inquiries,
            'canEdit' => $this->userCan('edit'),
            'selectedInquiry' => $this->viewInquiryId !== null
                ? StoreInquiry::query()->find($this->viewInquiryId)
                : null,
            'statusCounts' => $statusCounts,
        ]);
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

        return $user->isSuperAdmin() || \App\Services\Access\ModulePageAccess::can($user, 'store', 'messages', $action);
    }
}
