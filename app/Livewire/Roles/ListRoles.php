<?php

namespace App\Livewire\Roles;

use App\Livewire\Concerns\AuthorizesSensitiveActions;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\RoleService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListRoles extends Component
{
    use AuthorizesSensitiveActions, WithPagination;

    public string $search = '';

    #[On('roles-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    #[On('roles-search')]
    public function updateSearch(string $query): void
    {
        $this->authorizeAny(['settings.permission.view', 'settings.permission.edit', 'settings.permission.delete']);
        $this->search = $query;
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorizeAny(['settings.permission.create', 'settings.permission.edit']);
        $this->dispatch(
            'open-form-panel',
            title: __('agricart.roles.actions.add'),
            resource: __('agricart.pages.settings.permission'),
            formKey: 'roles',
            mode: 'create',
        );
    }

    public function openEdit(int $roleId): void
    {
        $this->authorizeAny(['settings.permission.view', 'settings.permission.edit']);
        $role = Role::query()->find($roleId);

        if ($role === null) {
            return;
        }

        $this->dispatch(
            'open-form-panel',
            title: __('agricart.roles.actions.edit', ['name' => $role->name]),
            resource: __('agricart.pages.settings.permission'),
            formKey: 'roles',
            roleId: $roleId,
            mode: $role->is_system ? 'view' : 'edit',
        );
    }

    public function deleteRole(int $roleId): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->isSuperAdmin()) {
            abort(403);
        }

        $role = Role::query()->find($roleId);

        if ($role === null) {
            return;
        }

        try {
            app(RoleService::class)->delete($role, $user);
            $this->resetPage();
        } catch (ValidationException $exception) {
            session()->flash('role_error', collect($exception->errors())->flatten()->first());
        }
    }

    public function render(): View
    {
        if (! $this->canAccessAny(['settings.permission.view', 'settings.permission.edit', 'settings.permission.delete'])) {
            return view('livewire.roles.list', [
                'roles' => Role::query()->whereRaw('1 = 0')->paginate(15),
                'canDeleteRoles' => false,
            ]);
        }

        $roles = Role::query()
            ->withCount('permissions')
            ->when(filled($this->search), function ($query): void {
                $needle = '%'.$this->search.'%';
                $query->where(function ($inner) use ($needle): void {
                    $inner->where('name', 'like', $needle)
                        ->orWhere('description', 'like', $needle);
                });
            })
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->paginate(15);

        $user = auth()->user();

        return view('livewire.roles.list', [
            'roles' => $roles,
            'canDeleteRoles' => $user instanceof User && $user->isSuperAdmin(),
        ]);
    }
}
