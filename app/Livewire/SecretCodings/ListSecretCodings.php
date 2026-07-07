<?php

namespace App\Livewire\SecretCodings;

use App\Models\SecretCodingProfile;
use App\Services\PricingControls\SecretCodingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListSecretCodings extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['profile_number', 'name_en', 'name_ur', 'digit_mapping', 'status'];

    public string $search = '';

    public string $statusFilter = '';

    #[On('secret-codings-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('secret-codings-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('secret-codings-filter')]
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

    #[On('secret-codings-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('secret-codings-column-toggle')]
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

        $this->dispatch('secret-codings-columns-sync', columns: $this->visibleColumns);
    }

    public function openEdit(int $recordId): void
    {
        $record = SecretCodingProfile::query()->find($recordId);

        if ($record === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('secret_coding.actions.edit', ['name' => $record->name_en]),
            resource: __('agricart.pages.marketplace.secret-coding'),
            formKey: 'secret-coding',
            secretCodingId: $recordId,
            mode: 'edit',
        );
    }

    public function render(): View
    {
        app(SecretCodingService::class)->ensureDefaultProfile();

        $service = app(SecretCodingService::class);

        $records = SecretCodingProfile::query()
            ->with('creator')
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('profile_number', 'like', $needle)
                        ->orWhere('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('id')
            ->paginate(15);

        return view('livewire.secret-codings.list', [
            'records' => $records,
            'mappingService' => $service,
        ]);
    }
}
