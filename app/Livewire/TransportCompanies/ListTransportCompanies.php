<?php

namespace App\Livewire\TransportCompanies;

use App\Models\TransportCompany;
use App\Models\User;
use App\Services\Contacts\TransportCompanyService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListTransportCompanies extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['transport_code', 'business_name', 'contact_name', 'transport_type', 'status', 'created_at'];

    public string $search = '';

    public string $statusFilter = '';

    public string $typeFilter = '';

    #[On('transport-companies-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('transport-companies-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('transport-companies-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        match ($field) {
            'status' => $this->statusFilter = $value,
            'type' => $this->typeFilter = $value,
            default => null,
        };

        $this->resetPage();
    }

    #[On('transport-companies-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->typeFilter = '';
        $this->resetPage();
    }

    #[On('transport-companies-column-toggle')]
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
            $this->visibleColumns = ['business_name'];
        }

        $this->dispatch('transport-companies-columns-sync', columns: $this->visibleColumns);
    }

    public function openCreate(): void
    {
        $this->dispatch(
            'open-form-panel',
            title: __('transport_companies.actions.add'),
            resource: __('transport_companies.page_title'),
            formKey: 'transport-companies',
        );
    }

    public function openView(int $id): void
    {
        if (TransportCompany::query()->whereKey($id)->doesntExist()) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('transport_companies.actions.view_short'),
            resource: __('transport_companies.page_title'),
            formKey: 'transport-companies',
            transportCompanyId: $id,
            mode: 'view',
        );
    }

    public function openEdit(int $id): void
    {
        if (TransportCompany::query()->whereKey($id)->doesntExist()) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('transport_companies.actions.edit_short'),
            resource: __('transport_companies.page_title'),
            formKey: 'transport-companies',
            transportCompanyId: $id,
            mode: 'edit',
        );
    }

    public function deleteTransportCompany(int $id): void
    {
        if (! $this->userCan('delete')) {
            return;
        }

        $company = TransportCompany::query()->find($id);

        if ($company === null) {
            return;
        }

        app(TransportCompanyService::class)->delete($company);
        $this->resetPage();
    }

    public function render(): View
    {
        $companies = TransportCompany::query()
            ->when(filled($this->search), function ($query): void {
                $term = trim($this->search);
                $needle = '%' . $term . '%';
                $digits = preg_replace('/\D+/', '', $term) ?? '';

                $query->where(function ($inner) use ($needle, $digits): void {
                    $inner->where('transport_code', 'like', $needle)
                        ->orWhere('business_name_en', 'like', $needle)
                        ->orWhere('business_name_ur', 'like', $needle)
                        ->orWhere('address_en', 'like', $needle)
                        ->orWhere('address_ur', 'like', $needle)
                        ->orWhere('city_en', 'like', $needle)
                        ->orWhere('city_ur', 'like', $needle)
                        ->orWhere('branches', 'like', $needle)
                        ->orWhere('contacts', 'like', $needle);

                    if ($digits !== '') {
                        $inner->orWhere('contacts', 'like', '%' . $digits . '%');
                    }
                });
            })
            ->when(
                filled($this->statusFilter),
                fn ($query) => $query->where('status', $this->statusFilter),
                fn ($query) => $query->whereIn('status', [TransportCompany::STATUS_ACTIVE, TransportCompany::STATUS_INACTIVE]),
            )
            ->when(filled($this->typeFilter), fn ($query) => $query->where('transport_type', $this->typeFilter))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.transport-companies.list', [
            'companies' => $companies,
            'canEdit' => $this->userCan('edit'),
            'canDelete' => $this->userCan('delete'),
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

        return \App\Services\Access\ModulePageAccess::can($user, 'contacts', 'transport-companies', $action);
    }
}
