<?php

namespace App\Livewire\PurchasePlanning;

use App\Models\PurchasePlanningSheet;
use App\Models\User;
use App\Services\Purchasing\PurchasePlanningService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListPlanningSheets extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['planning_number', 'item_count', 'paper_size', 'locale', 'created_by', 'created_at'];

    public string $search = '';

    public string $paperSizeFilter = '';

    public string $localeFilter = '';

    #[On('purchase-planning-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('purchase-planning-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('purchase-planning-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'paper_size') {
            $this->paperSizeFilter = $value;
            $this->resetPage();
        }

        if ($field === 'locale') {
            $this->localeFilter = $value;
            $this->resetPage();
        }
    }

    #[On('purchase-planning-clear-filters')]
    public function clearFilters(): void
    {
        $this->paperSizeFilter = '';
        $this->localeFilter = '';
        $this->resetPage();
    }

    #[On('purchase-planning-column-toggle')]
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
            $this->visibleColumns = ['planning_number'];
        }

        $this->dispatch('purchase-planning-columns-sync', columns: $this->visibleColumns);
    }

    public function openCreate(): void
    {
        $this->dispatch(
            'open-form-panel',
            title: __('purchase_planning.actions.add'),
            resource: __('agricart.nav.purchasing.purchase-planning'),
            formKey: 'purchase-planning',
        );
    }

    public function openView(int $sheetId): void
    {
        $sheet = PurchasePlanningSheet::query()->find($sheetId);

        if ($sheet === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('purchase_planning.actions.view_short') . ': ' . $sheet->planning_number,
            resource: __('agricart.nav.purchasing.purchase-planning'),
            formKey: 'purchase-planning',
            sheetId: $sheetId,
            mode: 'view',
        );
    }

    public function openEdit(int $sheetId): void
    {
        $sheet = PurchasePlanningSheet::query()->find($sheetId);

        if ($sheet === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('purchase_planning.actions.edit_short') . ': ' . $sheet->planning_number,
            resource: __('agricart.nav.purchasing.purchase-planning'),
            formKey: 'purchase-planning',
            sheetId: $sheetId,
            mode: 'edit',
        );
    }

    public function deleteSheet(int $sheetId): void
    {
        if (! $this->userCan('delete')) {
            return;
        }

        $sheet = PurchasePlanningSheet::query()->find($sheetId);

        if ($sheet === null) {
            return;
        }

        app(PurchasePlanningService::class)->delete($sheet);
        $this->resetPage();
    }

    public function printSheet(int $sheetId): void
    {
        $sheet = PurchasePlanningSheet::query()->find($sheetId);

        if ($sheet === null) {
            return;
        }

        $this->dispatch('purchase-planning-open-print', url: route('admin.purchase-planning.print', $sheet));
    }

    public function render(): View
    {
        $sheets = PurchasePlanningSheet::query()
            ->with(['creator', 'items'])
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('planning_number', 'like', $needle)
                        ->orWhere('notes', 'like', $needle);
                });
            })
            ->when(filled($this->paperSizeFilter), fn ($query) => $query->where('paper_size', $this->paperSizeFilter))
            ->when(filled($this->localeFilter), fn ($query) => $query->where('locale', $this->localeFilter))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.purchase-planning.list', [
            'sheets' => $sheets,
            'canEdit' => $this->userCan('edit'),
            'canDelete' => $this->userCan('delete'),
            'canCreate' => $this->userCan('create'),
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

        return \App\Services\Access\ModulePageAccess::can($user, 'purchasing', 'purchase-planning', $action);
    }
}
