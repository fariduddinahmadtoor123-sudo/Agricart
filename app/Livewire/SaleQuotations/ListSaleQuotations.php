<?php

namespace App\Livewire\SaleQuotations;

use App\Models\SaleQuotation;
use App\Models\User;
use App\Services\Sales\SaleQuotationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListSaleQuotations extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['quotation_number', 'customer', 'prepared_by', 'quotation_amount', 'locale', 'quotation_date', 'created_at'];

    public string $search = '';

    public string $localeFilter = '';

    #[On('sale-quotations-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('sale-quotations-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('sale-quotations-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        if ($field === 'locale') {
            $this->localeFilter = $value;
            $this->resetPage();
        }
    }

    #[On('sale-quotations-clear-filters')]
    public function clearFilters(): void
    {
        $this->localeFilter = '';
        $this->resetPage();
    }

    #[On('sale-quotations-column-toggle')]
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
            $this->visibleColumns = ['quotation_number'];
        }

        $this->dispatch('sale-quotations-columns-sync', columns: $this->visibleColumns);
    }

    public function openCreate(): void
    {
        $this->dispatch(
            'open-form-panel',
            title: __('sale_quotations.actions.add'),
            resource: __('agricart.nav.sales.sales-quotations'),
            formKey: 'sale-quotations',
        );
    }

    public function openView(int $quotationId): void
    {
        $quotation = SaleQuotation::query()->find($quotationId);

        if ($quotation === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('sale_quotations.actions.view_short') . ': ' . $quotation->quotation_number,
            resource: __('agricart.nav.sales.sales-quotations'),
            formKey: 'sale-quotations',
            quotationId: $quotationId,
            mode: 'view',
        );
    }

    public function openEdit(int $quotationId): void
    {
        $quotation = SaleQuotation::query()->find($quotationId);

        if ($quotation === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('sale_quotations.actions.edit_short') . ': ' . $quotation->quotation_number,
            resource: __('agricart.nav.sales.sales-quotations'),
            formKey: 'sale-quotations',
            quotationId: $quotationId,
            mode: 'edit',
        );
    }

    public function deleteQuotation(int $quotationId): void
    {
        if (! $this->userCan('delete')) {
            return;
        }

        $quotation = SaleQuotation::query()->find($quotationId);

        if ($quotation === null) {
            return;
        }

        app(SaleQuotationService::class)->delete($quotation);
        $this->resetPage();
    }

    public function printQuotation(int $quotationId): void
    {
        $quotation = SaleQuotation::query()->find($quotationId);

        if ($quotation === null) {
            return;
        }

        $this->dispatch('sale-quotations-open-print', url: route('admin.sale-quotations.print', $quotation));
    }

    public function render(): View
    {
        $query = SaleQuotation::query()
            ->with(['customer', 'preparedBy'])
            ->latest('created_at');

        if ($this->search !== '') {
            $needle = '%' . $this->search . '%';
            $query->where(function ($builder) use ($needle): void {
                $builder->where('quotation_number', 'like', $needle)
                    ->orWhereHas('customer', function ($customer) use ($needle): void {
                        $customer->where('display_name', 'like', $needle)
                            ->orWhere('customer_code', 'like', $needle);
                    })
                    ->orWhereHas('preparedBy', function ($user) use ($needle): void {
                        $user->where('name', 'like', $needle);
                    });
            });
        }

        if ($this->localeFilter !== '') {
            $query->where('locale', $this->localeFilter);
        }

        return view('livewire.sale-quotations.list', [
            'quotations' => $query->paginate(20),
            'canCreate' => $this->userCan('create'),
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

        return $user->hasPermission('sales.' . $action);
    }
}
