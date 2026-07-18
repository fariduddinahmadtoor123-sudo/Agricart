<?php

namespace App\Livewire\Store;

use App\Models\StoreInquiry;
use App\Models\User;
use Illuminate\Contracts\View\View;
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
            ->where('status', StoreInquiry::STATUS_NEW)
            ->update([
                'status' => StoreInquiry::STATUS_READ,
                'read_at' => now(),
            ]);
    }

    public function close(int $inquiryId): void
    {
        $this->authorizeMutation();

        StoreInquiry::query()->whereKey($inquiryId)->update([
            'status' => StoreInquiry::STATUS_CLOSED,
            'read_at' => now(),
            'closed_at' => now(),
        ]);
    }

    public function reopen(int $inquiryId): void
    {
        $this->authorizeMutation();

        StoreInquiry::query()->whereKey($inquiryId)->update([
            'status' => StoreInquiry::STATUS_READ,
            'read_at' => now(),
            'closed_at' => null,
        ]);
    }

    public function render(): View
    {
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

        return view('livewire.store.list-inquiries', [
            'inquiries' => $inquiries,
            'canEdit' => $this->userCan('edit'),
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

        return $user->isSuperAdmin() || $user->hasPermission('store.'.$action);
    }
}
