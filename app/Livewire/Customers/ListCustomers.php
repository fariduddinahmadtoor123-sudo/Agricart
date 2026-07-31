<?php

namespace App\Livewire\Customers;

use App\Livewire\Concerns\ImportsPortableArchive;
use App\Models\Customer;
use App\Models\User;
use App\Services\Contacts\CustomerPortableArchiveService;
use App\Services\Contacts\CustomerService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListCustomers extends Component
{
    use ImportsPortableArchive;
    use WithFileUploads;
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['customer_number', 'name', 'phone', 'channel', 'city', 'balance', 'created_at'];

    public string $search = '';

    public string $channelFilter = '';

    public string $statusFilter = '';

    public bool $showImportPanel = false;

    /** @var mixed */
    public $importArchive = null;

    public ?string $portableStatus = null;

    public string $portableStatusTone = 'success';

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

    #[On('customers-action')]
    public function handleToolbarAction(string $action): mixed
    {
        return match ($action) {
            'export_all' => $this->exportArchive(CustomerPortableArchiveService::SCOPE_ALL),
            'export_online' => $this->exportArchive(CustomerPortableArchiveService::SCOPE_ONLINE),
            'export_offline' => $this->exportArchive(CustomerPortableArchiveService::SCOPE_OFFLINE),
            'import_archive' => $this->openImportPanel(),
            default => null,
        };
    }

    public function openImportPanel(): void
    {
        if (! $this->userCan('create')) {
            $this->portableStatus = __('customers.portable.forbidden');
            $this->portableStatusTone = 'danger';

            return;
        }

        $this->showImportPanel = true;
        $this->importArchive = null;
        $this->portableStatus = null;
    }

    public function closeImportPanel(): void
    {
        $this->showImportPanel = false;
        $this->importArchive = null;
    }

    public function exportArchive(string $scope = CustomerPortableArchiveService::SCOPE_ALL): ?BinaryFileResponse
    {
        if (! $this->userCan('view')) {
            $this->portableStatus = __('customers.portable.forbidden');
            $this->portableStatusTone = 'danger';

            return null;
        }

        try {
            $archive = app(CustomerPortableArchiveService::class)->export($scope);
            $scopeLabel = match ($archive['scope']) {
                CustomerPortableArchiveService::SCOPE_ONLINE => __('customers.portable.scope_online'),
                CustomerPortableArchiveService::SCOPE_OFFLINE => __('customers.portable.scope_offline'),
                default => __('customers.portable.scope_all'),
            };
            $this->portableStatus = __('customers.portable.export_ok', [
                'count' => $archive['count'],
                'scope' => $scopeLabel,
            ]);
            $this->portableStatusTone = 'success';

            return response()
                ->download($archive['path'], $archive['filename'], [
                    'Content-Type' => 'application/octet-stream',
                ])
                ->deleteFileAfterSend(true);
        } catch (ValidationException $exception) {
            $this->portableStatus = collect($exception->errors())->flatten()->first();
            $this->portableStatusTone = 'danger';

            return null;
        }
    }

    public function importUploadedArchive(): void
    {
        if (! $this->userCan('create')) {
            $this->portableStatus = __('customers.portable.forbidden');
            $this->portableStatusTone = 'danger';

            return;
        }

        $this->validate([
            'importArchive' => ['required', 'file', 'max:1024000'],
        ], [], [
            'importArchive' => __('customers.actions.import_archive'),
        ]);

        $materialized = $this->materializePortableArchiveUpload($this->importArchive, 'customer-import');
        $absolute = $materialized['path'];

        try {
            $result = app(CustomerPortableArchiveService::class)->importFromPath($absolute);
            $this->portableStatus = __('customers.portable.import_ok', $result);
            $this->portableStatusTone = 'success';
            $this->showImportPanel = false;
            $this->importArchive = null;
            $this->resetPage();
        } catch (ValidationException $exception) {
            $this->portableStatus = collect($exception->errors())->flatten()->first();
            $this->portableStatusTone = 'danger';
        } finally {
            if ($materialized['delete'] && is_file($absolute)) {
                @unlink($absolute);
            }
        }
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
            title: __('customers.actions.view_short').': '.$customer->displayLabel(),
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
            title: __('customers.actions.edit_short').': '.$customer->displayLabel(),
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
                $term = trim($this->search);
                $needle = '%'.$term.'%';
                $normalized = app(CustomerService::class)->normalizePhone($term);

                $query->where(function ($inner) use ($needle, $normalized): void {
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
                        ->orWhere('city', 'like', $needle)
                        ->orWhere('contacts', 'like', $needle);

                    if ($normalized !== '') {
                        $digitNeedle = '%'.$normalized.'%';
                        $inner->orWhere('phone', 'like', $digitNeedle)
                            ->orWhere('contacts', 'like', $digitNeedle)
                            ->orWhereHas('phones', fn ($phones) => $phones->where('canonical_phone', 'like', $digitNeedle));
                    }
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

        return $user->hasPermission('contacts.'.$action);
    }
}
