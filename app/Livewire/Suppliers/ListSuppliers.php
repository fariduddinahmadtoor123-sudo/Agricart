<?php

namespace App\Livewire\Suppliers;

use App\Filament\Forms\CategoryForm;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Contacts\SupplierPortableArchiveService;
use App\Services\Contacts\SupplierService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListSuppliers extends Component
{
    use WithFileUploads;
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['supplier_code', 'business_name', 'contact_name', 'city', 'supplier_type', 'status', 'created_at'];

    public string $search = '';

    public string $statusFilter = '';

    public string $cityFilter = '';

    public bool $showImportPanel = false;

    /** @var mixed */
    public $importArchive = null;

    public ?string $portableStatus = null;

    public string $portableStatusTone = 'success';

    #[On('suppliers-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('suppliers-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('suppliers-filter')]
    public function applyFilter(string $filter): void
    {
        if (! str_contains($filter, ':')) {
            return;
        }

        [$field, $value] = explode(':', $filter, 2);

        match ($field) {
            'status' => $this->statusFilter = $value,
            'city' => $this->cityFilter = $value,
            default => null,
        };

        $this->resetPage();
    }

    #[On('suppliers-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->cityFilter = '';
        $this->resetPage();
    }

    #[On('suppliers-column-toggle')]
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

        $this->dispatch('suppliers-columns-sync', columns: $this->visibleColumns);
    }

    #[On('suppliers-action')]
    public function handleToolbarAction(string $action): mixed
    {
        return match ($action) {
            'export_archive' => $this->exportArchive(),
            'import_archive' => $this->openImportPanel(),
            default => null,
        };
    }

    public function openImportPanel(): void
    {
        if (! $this->userCan('create')) {
            $this->portableStatus = __('suppliers.portable.forbidden');
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

    public function exportArchive(): ?BinaryFileResponse
    {
        if (! $this->userCan('view')) {
            $this->portableStatus = __('suppliers.portable.forbidden');
            $this->portableStatusTone = 'danger';

            return null;
        }

        try {
            $archive = app(SupplierPortableArchiveService::class)->exportAll();
            $this->portableStatus = __('suppliers.portable.export_ok', ['count' => $archive['count']]);
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
            $this->portableStatus = __('suppliers.portable.forbidden');
            $this->portableStatusTone = 'danger';

            return;
        }

        $this->validate([
            'importArchive' => ['required', 'file', 'max:512000'],
        ], [], [
            'importArchive' => __('suppliers.actions.import_archive'),
        ]);

        $stored = $this->importArchive->storeAs(
            'tmp',
            'supplier-import-'.uniqid('', true).'.ags',
            'local',
        );

        $absolute = storage_path('app/private/'.$stored);

        if (! is_file($absolute)) {
            $absolute = storage_path('app/'.$stored);
        }

        try {
            $result = app(SupplierPortableArchiveService::class)->importFromPath($absolute);
            $this->portableStatus = __('suppliers.portable.import_ok', $result);
            $this->portableStatusTone = 'success';
            $this->showImportPanel = false;
            $this->importArchive = null;
            $this->resetPage();
        } catch (ValidationException $exception) {
            $this->portableStatus = collect($exception->errors())->flatten()->first();
            $this->portableStatusTone = 'danger';
        } finally {
            if (is_file($absolute)) {
                File::delete($absolute);
            }
        }
    }

    public function openCreate(): void
    {
        $this->dispatch(
            'open-form-panel',
            title: __('suppliers.actions.add'),
            resource: __('agricart.nav.contacts.suppliers'),
            formKey: 'suppliers',
        );
    }

    public function openView(int $supplierId): void
    {
        $supplier = Supplier::query()->find($supplierId);

        if ($supplier === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('suppliers.actions.view_short').': '.$supplier->displayBusinessName(),
            resource: __('agricart.nav.contacts.suppliers'),
            formKey: 'suppliers',
            supplierId: $supplierId,
            mode: 'view',
        );
    }

    public function openEdit(int $supplierId): void
    {
        $supplier = Supplier::query()->find($supplierId);

        if ($supplier === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('suppliers.actions.edit_short').': '.$supplier->displayBusinessName(),
            resource: __('agricart.nav.contacts.suppliers'),
            formKey: 'suppliers',
            supplierId: $supplierId,
            mode: 'edit',
        );
    }

    public function deleteSupplier(int $supplierId): void
    {
        if (! $this->userCan('delete')) {
            return;
        }

        $supplier = Supplier::query()->find($supplierId);

        if ($supplier === null) {
            return;
        }

        try {
            app(SupplierService::class)->delete($supplier);
            $this->resetPage();
        } catch (ValidationException $exception) {
            session()->flash('supplier_error', collect($exception->errors())->flatten()->first());
        }
    }

    public function render(): View
    {
        $suppliers = Supplier::query()
            ->with('categories')
            ->when(filled($this->search), function ($query): void {
                $term = trim($this->search);
                $needle = '%'.$term.'%';
                $digits = preg_replace('/\D+/', '', $term) ?? '';

                $query->where(function ($inner) use ($needle, $digits): void {
                    $inner->where('supplier_code', 'like', $needle)
                        ->orWhere('business_name_en', 'like', $needle)
                        ->orWhere('business_name_ur', 'like', $needle)
                        ->orWhere('city_en', 'like', $needle)
                        ->orWhere('city_ur', 'like', $needle)
                        ->orWhere('email', 'like', $needle)
                        ->orWhere('contacts', 'like', $needle);

                    if ($digits !== '') {
                        $inner->orWhere('contacts', 'like', '%'.$digits.'%');
                    }
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->when(filled($this->cityFilter), fn ($query) => $query->where('city_key', $this->cityFilter))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.suppliers.list', [
            'suppliers' => $suppliers,
            'canEdit' => $this->userCan('edit'),
            'canDelete' => $this->userCan('delete'),
            'categoryLabels' => CategoryForm::categoryLabelsByIds(
                $suppliers->getCollection()
                    ->flatMap(fn (Supplier $supplier): array => $supplier->categories->pluck('id')->all())
                    ->unique()
                    ->values()
                    ->all(),
            ),
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
