<?php

namespace App\Livewire\Approvals;

use App\Models\Supplier;
use App\Models\User;
use App\Services\Contacts\SupplierApprovalService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListSupplierApprovals extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'pending';

    public ?int $viewSupplierId = null;

    public ?int $approveSupplierId = null;

    public ?int $rejectSupplierId = null;

    public string $rejectReason = '';

    public ?string $flashMessage = null;

    public string $flashType = 'success';

    #[On('supplier-approvals-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('supplier-approvals-filter')]
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

    #[On('supplier-approvals-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = 'pending';
        $this->resetPage();
    }

    public function openView(int $supplierId): void
    {
        if (! $this->canManage()) {
            return;
        }

        if (! Supplier::query()->whereKey($supplierId)->exists()) {
            return;
        }

        $this->viewSupplierId = $supplierId;
    }

    public function closeView(): void
    {
        $this->viewSupplierId = null;
    }

    public function openApprove(int $supplierId): void
    {
        if (! $this->canManage()) {
            return;
        }

        $supplier = Supplier::query()->find($supplierId);

        if ($supplier === null || ! $supplier->isPending()) {
            return;
        }

        $this->approveSupplierId = $supplierId;
    }

    public function confirmApprove(): void
    {
        if (! $this->canManage() || $this->approveSupplierId === null) {
            return;
        }

        try {
            $supplier = Supplier::query()->findOrFail($this->approveSupplierId);
            app(SupplierApprovalService::class)->approve($supplier);
            $this->flash(__('agricart.approvals.suppliers.messages.approved', [
                'name' => $supplier->displayBusinessName(),
            ]), 'success');
            $this->approveSupplierId = null;
            $this->viewSupplierId = null;
            $this->resetPage();
        } catch (ValidationException $exception) {
            $this->flash(
                collect($exception->errors())->flatten()->first()
                    ?: __('agricart.approvals.suppliers.messages.approve_failed'),
                'danger',
            );
        }
    }

    public function openReject(int $supplierId): void
    {
        if (! $this->canManage()) {
            return;
        }

        $supplier = Supplier::query()->find($supplierId);

        if ($supplier === null || ! $supplier->isPending()) {
            return;
        }

        $this->rejectSupplierId = $supplierId;
        $this->rejectReason = '';
    }

    public function confirmReject(): void
    {
        if (! $this->canManage() || $this->rejectSupplierId === null) {
            return;
        }

        try {
            $supplier = Supplier::query()->findOrFail($this->rejectSupplierId);
            app(SupplierApprovalService::class)->reject($supplier, $this->rejectReason);
            $this->flash(__('agricart.approvals.suppliers.messages.rejected', [
                'name' => $supplier->displayBusinessName(),
            ]), 'success');
            $this->rejectSupplierId = null;
            $this->rejectReason = '';
            $this->viewSupplierId = null;
            $this->resetPage();
        } catch (ValidationException $exception) {
            $this->flash(
                collect($exception->errors())->flatten()->first()
                    ?: __('agricart.approvals.suppliers.messages.reject_failed'),
                'danger',
            );
        }
    }

    public function cancelApprove(): void
    {
        $this->approveSupplierId = null;
    }

    public function cancelReject(): void
    {
        $this->rejectSupplierId = null;
        $this->rejectReason = '';
    }

    public function render(): View
    {
        $suppliers = Supplier::query()
            ->with('categories')
            ->whereIn('status', [
                Supplier::STATUS_PENDING,
                Supplier::STATUS_ACTIVE,
                Supplier::STATUS_REJECTED,
            ])
            ->where(function ($query): void {
                $query->where('source', Supplier::SOURCE_PUBLIC)
                    ->orWhereIn('status', [Supplier::STATUS_PENDING, Supplier::STATUS_REJECTED]);
            })
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('business_name_en', 'like', $needle)
                        ->orWhere('business_name_ur', 'like', $needle)
                        ->orWhere('supplier_code', 'like', $needle)
                        ->orWhere('city_en', 'like', $needle)
                        ->orWhere('contacts', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        $viewSupplier = $this->viewSupplierId !== null
            ? Supplier::query()->with('categories')->find($this->viewSupplierId)
            : null;

        return view('livewire.approvals.list-suppliers', [
            'suppliers' => $suppliers,
            'viewSupplier' => $viewSupplier,
            'canManage' => $this->canManage(),
        ]);
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->hasPermission('approvals.edit'));
    }

    private function flash(string $message, string $type): void
    {
        $this->flashMessage = $message;
        $this->flashType = $type;
    }
}
