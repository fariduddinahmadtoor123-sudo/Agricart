<?php

namespace App\Livewire\OnlineSaleOrders;

use App\Models\OnlineSaleOrderStaffAssignment;
use App\Models\User;
use App\Services\Sales\OnlineOrderDispatchService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListForwardedOrders extends Component
{
    use WithPagination;

    public string $search = '';

    public string $fromUserFilter = '';

    public string $toUserFilter = '';

    #[On('forwarded-orders-saved')]
    #[On('order-dispatch-saved')]
    #[On('online-sale-orders-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('forwarded-orders-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('forwarded-orders-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        match ($field) {
            'from' => $this->fromUserFilter = $value,
            'to' => $this->toUserFilter = $value,
            default => null,
        };

        $this->resetPage();
    }

    #[On('forwarded-orders-clear-filters')]
    public function clearFilters(): void
    {
        $this->fromUserFilter = '';
        $this->toUserFilter = '';
        $this->search = '';
        $this->resetPage();
        $this->dispatch('forwarded-orders-clear-search');
    }

    public function removeFilter(string $field): void
    {
        match ($field) {
            'from' => $this->fromUserFilter = '',
            'to' => $this->toUserFilter = '',
            default => null,
        };

        $this->resetPage();
    }

    /**
     * @return list<array{field: string, label: string}>
     */
    public function activeFilters(): array
    {
        $filters = [];

        if (filled($this->fromUserFilter)) {
            $name = User::query()->whereKey((int) $this->fromUserFilter)->value('name');

            $filters[] = [
                'field' => 'from',
                'label' => __('forwarded_orders.filters.active_from', [
                    'name' => $name ?: '#'.$this->fromUserFilter,
                ]),
            ];
        }

        if (filled($this->toUserFilter)) {
            $name = User::query()->whereKey((int) $this->toUserFilter)->value('name');

            $filters[] = [
                'field' => 'to',
                'label' => __('forwarded_orders.filters.active_to', [
                    'name' => $name ?: '#'.$this->toUserFilter,
                ]),
            ];
        }

        return $filters;
    }

    public function hasListConstraints(): bool
    {
        return filled($this->search)
            || filled($this->fromUserFilter)
            || filled($this->toUserFilter);
    }

    public function markReleased(int $assignmentId): void
    {
        if (! $this->userCan('edit')) {
            session()->flash('forwarded_orders_status', __('forwarded_orders.messages.release_denied'));

            return;
        }

        $assignment = OnlineSaleOrderStaffAssignment::query()
            ->whereKey($assignmentId)
            ->whereNotNull('sent_at')
            ->first();

        if ($assignment === null) {
            session()->flash('forwarded_orders_status', __('forwarded_orders.messages.release_failed'));

            return;
        }

        if ($assignment->released_at !== null) {
            return;
        }

        try {
            app(OnlineOrderDispatchService::class)->markStaffReleased($assignment);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            session()->flash('forwarded_orders_status', is_string($message) ? $message : __('forwarded_orders.messages.release_failed'));

            return;
        }

        session()->flash('forwarded_orders_status', __('forwarded_orders.messages.released'));
        $this->dispatch('forwarded-orders-saved');
    }

    public function render(): View
    {
        $rows = $this->historyQuery()
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.online-sale-orders.list-forwarded-orders', [
            'rows' => $rows,
            'activeFilters' => $this->activeFilters(),
            'hasListConstraints' => $this->hasListConstraints(),
            'canRelease' => $this->userCan('edit'),
        ]);
    }

    private function userCan(string $action): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return \App\Services\Access\ModulePageAccess::can($user, 'sales', 'forwarded-orders', $action);
    }

    /**
     * @return Builder<OnlineSaleOrderStaffAssignment>
     */
    private function historyQuery(): Builder
    {
        $query = OnlineSaleOrderStaffAssignment::query()
            ->whereNotNull('sent_at')
            ->with([
                'order.sale',
                'user',
                'assignedBy',
            ]);

        if (filled($this->fromUserFilter)) {
            $query->where('assigned_by_id', (int) $this->fromUserFilter);
        }

        if (filled($this->toUserFilter)) {
            $query->where('user_id', (int) $this->toUserFilter);
        }

        $needle = trim($this->search);

        if ($needle !== '') {
            $like = '%'.$needle.'%';

            $query->where(function (Builder $builder) use ($like): void {
                $builder
                    ->whereHas('order', function (Builder $order) use ($like): void {
                        $order
                            ->where('order_number', 'like', $like)
                            ->orWhereHas('sale', function (Builder $sale) use ($like): void {
                                $sale->where('sale_number', 'like', $like);
                            });
                    })
                    ->orWhereHas('user', function (Builder $user) use ($like): void {
                        $user
                            ->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    })
                    ->orWhereHas('assignedBy', function (Builder $user) use ($like): void {
                        $user
                            ->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            });
        }

        return $query;
    }
}
