<?php

namespace App\Livewire\Approvals;

use App\Models\User;
use App\Services\Access\RoleAssignmentGuard;
use App\Services\Access\StaffApprovalService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListStaffApprovals extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'pending';

    public ?int $viewUserId = null;

    public ?int $approveUserId = null;

    public ?int $approveRoleId = null;

    public ?int $rejectUserId = null;

    public string $rejectReason = '';

    public ?string $flashMessage = null;

    public string $flashType = 'success';

    #[On('staff-approvals-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('staff-approvals-filter')]
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

    #[On('staff-approvals-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = 'pending';
        $this->resetPage();
    }

    public function openView(int $userId): void
    {
        if (! $this->canManage()) {
            return;
        }

        if (! User::query()->whereKey($userId)->exists()) {
            return;
        }

        $this->viewUserId = $userId;
    }

    public function closeView(): void
    {
        $this->viewUserId = null;
    }

    public function openApprove(int $userId): void
    {
        if (! $this->canManage()) {
            return;
        }

        $user = User::query()->find($userId);

        if ($user === null || ! $user->isPending()) {
            return;
        }

        $this->approveUserId = $userId;
        $this->approveRoleId = $user->role_id;
    }

    public function confirmApprove(): void
    {
        if (! $this->canManage() || $this->approveUserId === null) {
            return;
        }

        try {
            $user = User::query()->findOrFail($this->approveUserId);
            app(StaffApprovalService::class)->approve($user, $this->approveRoleId);
            $this->flash(__('agricart.approvals.staff.messages.approved', ['name' => $user->name]), 'success');
            $this->approveUserId = null;
            $this->approveRoleId = null;
            $this->viewUserId = null;
            $this->resetPage();
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('agricart.approvals.staff.messages.approve_failed'), 'danger');
        }
    }

    public function openReject(int $userId): void
    {
        if (! $this->canManage()) {
            return;
        }

        $user = User::query()->find($userId);

        if ($user === null || ! $user->isPending()) {
            return;
        }

        $this->rejectUserId = $userId;
        $this->rejectReason = '';
    }

    public function confirmReject(): void
    {
        if (! $this->canManage() || $this->rejectUserId === null) {
            return;
        }

        try {
            $user = User::query()->findOrFail($this->rejectUserId);
            app(StaffApprovalService::class)->reject($user, $this->rejectReason);
            $this->flash(__('agricart.approvals.staff.messages.rejected', ['name' => $user->name]), 'success');
            $this->rejectUserId = null;
            $this->rejectReason = '';
            $this->viewUserId = null;
            $this->resetPage();
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('agricart.approvals.staff.messages.reject_failed'), 'danger');
        }
    }

    public function cancelApprove(): void
    {
        $this->approveUserId = null;
        $this->approveRoleId = null;
    }

    public function cancelReject(): void
    {
        $this->rejectUserId = null;
        $this->rejectReason = '';
    }

    public function render(): View
    {
        $users = User::query()
            ->with(['role'])
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name', 'like', $needle)
                        ->orWhere('email', 'like', $needle)
                        ->orWhere('user_number', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        $viewUser = $this->viewUserId !== null
            ? User::query()->with('role')->find($this->viewUserId)
            : null;

        return view('livewire.approvals.list-staff', [
            'users' => $users,
            'viewUser' => $viewUser,
            'roleOptions' => app(RoleAssignmentGuard::class)->assignableRoleOptions(
                auth()->user() instanceof User ? auth()->user() : null,
            ),
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
