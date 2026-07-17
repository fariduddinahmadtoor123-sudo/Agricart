<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\User;
use App\Services\Contacts\CustomerService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListCustomers extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['customer_number', 'name', 'phone', 'channel', 'city', 'balance', 'created_at'];

    public string $search = '';

    public string $channelFilter = '';

    public string $statusFilter = '';

    #[On('customers-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('customers-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('customers-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        match ($field) {
            'channel' => $this->channelFilter = $value,
            'status' => $this->statusFilter = $value,
            default => null,
        };

        $this->resetPage();
    }

    #[On('customers-clear-filters')]
    public function clearFilters(): void
    {
        $this->channelFilter = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('customers-column-toggle')]
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
            $this->visibleColumns = ['name'];
        }

        $this->dispatch('customers-columns-sync', columns: $this->visibleColumns);
    }

    public function openCreate(): void
    {
        $this->dispatch(
            'open-form-panel',
            title: __('customers.actions.add'),
            resource: __('agricart.nav.contacts.customers'),
            formKey: 'customers',
        );
    }

    public function openView(int $customerId): void
    {
        $customer = Customer::query()->find($customerId);

        if ($customer === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('customers.actions.view_short') . ': ' . $customer->displayLabel(),
            resource: __('agricart.nav.contacts.customers'),
            formKey: 'customers',
            customerId: $customerId,
            mode: 'view',
        );
    }

    public function openEdit(int $customerId): void
    {
        $customer = Customer::query()->find($customerId);

        if ($customer === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('customers.actions.edit_short') . ': ' . $customer->displayLabel(),
            resource: __('agricart.nav.contacts.customers'),
            formKey: 'customers',
            customerId: $customerId,
            mode: 'edit',
        );
    }

    public function deleteCustomer(int $customerId): void
    {
        if (! $this->userCan('delete')) {
            return;
        }

        $customer = Customer::query()->find($customerId);

        if ($customer === null) {
            return;
        }

        try {
            app(CustomerService::class)->delete($customer);
            $this->resetPage();
        } catch (ValidationException $exception) {
            session()->flash('customer_error', collect($exception->errors())->flatten()->first());
        }
    }

    public function render(): View
    {
        $customers = Customer::query()
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('customer_number', 'like', $needle)
                        ->orWhere('display_name', 'like', $needle)
                        ->orWhere('name_en', 'like', $needle)
                        ->orWhere('name_ur', 'like', $needle)
                        ->orWhere('first_name', 'like', $needle)
                        ->orWhere('last_name', 'like', $needle)
                        ->orWhere('phone', 'like', $needle)
                        ->orWhere('email', 'like', $needle)
                        ->orWhere('city_en', 'like', $needle)
                        ->orWhere('city_ur', 'like', $needle)
                        ->orWhere('city', 'like', $needle);
                });
            })
            ->when(filled($this->channelFilter), fn ($query) => $query->where('customer_channel', $this->channelFilter))
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.customers.list', [
            'customers' => $customers,
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

        return $user->hasPermission('contacts.' . $action);
    }
}
