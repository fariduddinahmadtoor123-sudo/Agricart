<?php

namespace App\Livewire\Attributes;

use App\Models\CatalogAttribute;
use App\Services\Catalog\AttributeService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListAttributes extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['code', 'name_en', 'name_ur', 'status', 'ai_status', 'created_at'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('attributes-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('attributes-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('attributes-filter')]
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

    #[On('attributes-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('attributes-column-toggle')]
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

        $this->dispatch('attributes-columns-sync', columns: $this->visibleColumns);
    }

    public function openView(int $attributeId): void
    {
        $attribute = CatalogAttribute::query()->find($attributeId);

        if ($attribute === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.attributes.actions.view', ['name' => $attribute->name_en]),
            resource: __('agricart.pages.catalog.attributes'),
            formKey: 'attributes',
            attributeId: $attributeId,
            mode: 'view',
        );
    }

    public function openEdit(int $attributeId): void
    {
        $attribute = CatalogAttribute::query()->find($attributeId);

        if ($attribute === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.attributes.actions.edit', ['name' => $attribute->name_en]),
            resource: __('agricart.pages.catalog.attributes'),
            formKey: 'attributes',
            attributeId: $attributeId,
            mode: 'edit',
        );
    }

    public function deleteAttribute(int $attributeId): void
    {
        $attribute = CatalogAttribute::query()->find($attributeId);

        if ($attribute === null) {
            return;
        }

        app(AttributeService::class)->delete($attribute);
        $this->resetPage();
    }

    public function render(): View
    {
        $attributes = CatalogAttribute::query()
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('slug', 'like', $needle)
                        ->orWhere('code', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.attributes.list', [
            'attributes' => $attributes,
        ]);
    }
}
