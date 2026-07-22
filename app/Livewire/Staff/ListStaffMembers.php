<?php

namespace App\Livewire\Staff;

use App\Models\User;
use App\Services\Staff\StaffMemberService;
use App\Services\Staff\StaffPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListStaffMembers extends Component
{
    use WithPagination;

    /** @var list<string> */
    public array $visibleColumns = ['photo', 'user_number', 'name', 'email', 'role', 'status'];

    public string $search = '';

    public string $statusFilter = '';

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('staff-members-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('staff-members-search')]
    public function updateSearch(string $query): void
    {
        $this->search = $query;
        $this->resetPage();
    }

    #[On('staff-members-filter')]
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

    #[On('staff-members-clear-filters')]
    public function clearFilters(): void
    {
        $this->statusFilter = '';
        $this->resetPage();
    }

    #[On('staff-members-column-toggle')]
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

        $this->dispatch('staff-members-columns-sync', columns: $this->visibleColumns);
    }

    public function activate(int $userId): void
    {
        if (! $this->canEdit()) {
            return;
        }

        try {
            $member = User::query()->findOrFail($userId);
            app(StaffMemberService::class)->setActive($member);
            $this->flash(__('agricart.staff.members.messages.activated', ['name' => $member->name]), 'success');
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('agricart.staff.members.messages.status_failed'), 'error');
        }
    }

    public function deactivate(int $userId): void
    {
        if (! $this->canEdit()) {
            return;
        }

        try {
            $member = User::query()->findOrFail($userId);
            app(StaffMemberService::class)->setInactive($member);
            $this->flash(__('agricart.staff.members.messages.deactivated', ['name' => $member->name]), 'success');
        } catch (ValidationException $exception) {
            $this->flash(collect($exception->errors())->flatten()->first() ?: __('agricart.staff.members.messages.status_failed'), 'error');
        }
    }

    public function openCreate(): void
    {
        if (! $this->canCreate()) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.staff.members.actions.add'),
            resource: __('agricart.pages.staff.staff'),
            formKey: 'staff',
            formContext: 'staff',
            mode: 'create',
        );
    }

    public function openView(int $userId): void
    {
        if (! $this->canView()) {
            return;
        }

        $member = User::query()->find($userId);

        if ($member === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.staff.members.actions.view', ['name' => $member->name]),
            resource: __('agricart.pages.staff.staff'),
            formKey: 'staff',
            formContext: 'staff',
            userId: $userId,
            mode: 'view',
        );
    }

    public function openEdit(int $userId): void
    {
        if (! $this->canEdit()) {
            return;
        }

        $member = User::query()->find($userId);

        if ($member === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.staff.members.actions.edit', ['name' => $member->name]),
            resource: __('agricart.pages.staff.staff'),
            formKey: 'staff',
            formContext: 'staff',
            userId: $userId,
            mode: 'edit',
        );
    }

    public function render(): View
    {
        $query = app(StaffMemberService::class)->rosterQuery();

        $members = $query
            ->when(filled($this->search), function ($query): void {
                $needle = '%' . $this->search . '%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name', 'like', $needle)
                        ->orWhere('email', 'like', $needle)
                        ->orWhere('user_number', 'like', $needle);
                });
            })
            ->when(filled($this->statusFilter), fn ($query) => $query->where('status', $this->statusFilter))
            ->latest('name')
            ->latest('id')
            ->paginate(15);

        return view('livewire.staff.list', [
            'members' => $members,
            'canView' => $this->canView(),
            'canEdit' => $this->canEdit(),
            'canCreate' => $this->canCreate(),
        ]);
    }

    public function canView(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return StaffPermissions::canViewRoster($user) || StaffPermissions::canManageRoster($user);
    }

    public function canEdit(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return StaffPermissions::canManageRoster($user);
    }

    public function canCreate(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return StaffPermissions::canManageRoster($user);
    }

    private function flash(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }
}
