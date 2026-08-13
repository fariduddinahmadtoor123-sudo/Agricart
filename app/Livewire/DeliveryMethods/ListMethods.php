<?php

namespace App\Livewire\DeliveryMethods;

use App\Models\DeliveryMethod;
use App\Models\User;
use App\Services\Logistics\DeliveryMethodService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListMethods extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = [
        'sort_order',
        'name_en',
        'name_ur',
        'status',
        'shipping_charge_mode',
        'cod_allowed',
    ];

    public string $search = '';

    public string $statusFilter = '';

    public bool $canView = false;

    public bool $canEdit = false;

    public bool $canDelete = false;

    public function mount(): void
    {
        $user = auth()->user();
        $this->canView = $this->userCan($user, 'view');
        $this->canEdit = $this->userCan($user, 'edit');
        $this->canDelete = $this->userCan($user, 'delete');
        $this->dispatch('delivery-methods-columns-sync', columns: $this->visibleColumns);
    }

    #[On('delivery-methods-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('delivery-methods-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('delivery-methods-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'status') {
            $this->statusFilter = $value;
            $this->resetPage();
        }
    }

    #[On('delivery-methods-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('delivery-methods-column-toggle')]
    public function toggleColumn(string $column, bool $visible): void
    {
        if ($visible && ! in_array($column, $this->visibleColumns, true)) {
            $this->visibleColumns[] = $column;
        }

        if (! $visible) {
            $this->visibleColumns = array_values(array_filter(
                $this->visibleColumns,
                fn (string $col): bool => $col !== $column,
            ));
        }

        if ($this->visibleColumns === []) {
            $this->visibleColumns = ['name_en'];
        }

        $this->dispatch('delivery-methods-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $methodId): void
    {
        $this->authorizeAction('view');
        $method = DeliveryMethod::query()->find($methodId);

        if ($method === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.delivery_methods.actions.view', ['name' => $method->name_en]),
            resource: __('agricart.pages.logistics.delivery-methods'),
            formKey: 'delivery-methods',
            deliveryMethodId: $methodId,
            mode: 'view',
        );
    }

    public function openEdit(int $methodId): void
    {
        $this->authorizeAction('edit');
        $method = DeliveryMethod::query()->find($methodId);

        if ($method === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.delivery_methods.actions.edit', ['name' => $method->name_en]),
            resource: __('agricart.pages.logistics.delivery-methods'),
            formKey: 'delivery-methods',
            deliveryMethodId: $methodId,
            mode: 'edit',
        );
    }

    public function deleteMethod(int $methodId): void
    {
        $this->authorizeAction('delete');
        $method = DeliveryMethod::query()->find($methodId);

        if ($method === null) {
            return;
        }

        app(DeliveryMethodService::class)->delete($method);

        Notification::make()
            ->title(__('agricart.delivery_methods.actions.deleted'))
            ->success()
            ->send();

        $this->resetPage();
    }

    public function render(): View
    {
        $methods = DeliveryMethod::query()
            ->with('rule')
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('slug', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->paginate(15);

        return view('livewire.delivery-methods.list', [
            'methods' => $methods,
        ]);
    }

    private function authorizeAction(string $action): void
    {
        abort_unless($this->userCan(auth()->user(), $action), 403);
    }

    private function userCan(?User $user, string $action): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return \App\Services\Access\ModulePageAccess::can($user, 'logistics', 'delivery-methods', $action);
    }
}
