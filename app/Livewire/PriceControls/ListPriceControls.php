<?php

namespace App\Livewire\PriceControls;

use App\Models\PriceControl;
use App\Services\PricingControls\PriceControlService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListPriceControls extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['control_number', 'name_en', 'name_ur', 'status', 'created_at'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('price-controls-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('price-controls-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('price-controls-filter')]
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

    #[On('price-controls-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('price-controls-column-toggle')]
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

        $this->dispatch('price-controls-columns-sync', columns: $this->visibleColumns);
    }

    public function openEdit(int $recordId): void
    {
        $record = PriceControl::query()->find($recordId);

        if ($record === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('price_controls.actions.edit', ['name' => $record->name_en]),
            resource: __('agricart.pages.marketplace.price-control'),
            formKey: 'price-controls',
            priceControlId: $recordId,
            mode: 'edit',
        );
    }

    public function deleteRecord(int $recordId): void
    {
        $record = PriceControl::query()->find($recordId);

        if ($record === null) {
            return;
        }

        try {
            app(PriceControlService::class)->delete($record);
            $this->resetPage();
        } catch (ValidationException $exception) {
            session()->flash('price_control_error', collect($exception->errors())->flatten()->first());
        }
    }

    public function render(): View
    {
        $records = PriceControl::query()
            ->with('creator')
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('control_number', 'like', $needle)
                        ->orWhere('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate(15);

        return view('livewire.price-controls.list', [
            'records' => $records,
        ]);
    }
}
