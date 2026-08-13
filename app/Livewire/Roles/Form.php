<?php

namespace App\Livewire\Roles;

use App\Models\Permission;
use App\Models\Role;
use App\Services\Access\PermissionRegistry;
use App\Services\Access\RoleService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public ?int $roleId = null;

    public string $mode = 'create';

    public string $name = '';

    public string $description = '';

    public bool $isSystem = false;

    /** @var list<string> */
    public array $selectedPermissions = [];

    public bool $saving = false;

    public ?string $statusMessage = null;

    public string $statusType = 'success';

    #[On('role-form-open')]
    public function openForm(?int $roleId = null, string $mode = 'create'): void
    {
        PermissionRegistry::syncToDatabase();

        $this->resetForm();
        $this->mode = $mode;
        $this->roleId = $roleId;

        if ($roleId) {
            $this->loadRole($roleId);
        }
    }

    #[On('role-form-action')]
    public function handlePanelAction(string $action): void
    {
        match ($action) {
            'save_close' => $this->saveAndClose(),
            'save_next' => $this->saveAndAddNext(),
            default => null,
        };
    }

    public function toggleGroup(string $groupKey, bool $checked): void
    {
        if ($this->isReadonly() || $this->isSystem) {
            return;
        }

        $groupKeys = collect(PermissionRegistry::groups()[$groupKey]['permissions'] ?? [])
            ->pluck('key')
            ->all();

        if ($checked) {
            $this->selectedPermissions = array_values(array_unique([
                ...$this->selectedPermissions,
                ...$groupKeys,
            ]));
        } else {
            $this->selectedPermissions = array_values(array_filter(
                $this->selectedPermissions,
                fn (string $permission): bool => ! in_array($permission, $groupKeys, true),
            ));
        }
    }

    public function saveAndClose(): void
    {
        if ($this->persist()) {
            $this->dispatch('roles-saved');
            $this->dispatch('close-form-panel');
        }
    }

    public function saveAndAddNext(): void
    {
        if ($this->persist()) {
            $this->dispatch('roles-saved');
            $this->resetForm();
        }
    }

    public function isReadonly(): bool
    {
        return $this->mode === 'view' || $this->isSystem;
    }

    public function render(): View
    {
        return view('livewire.roles.form', [
            'permissionGroups' => PermissionRegistry::groupsForForm(),
        ]);
    }

    private function loadRole(int $roleId): void
    {
        $role = Role::query()->with('permissions')->findOrFail($roleId);

        $this->name = $role->name;
        $this->description = (string) ($role->description ?? '');
        $this->isSystem = $role->is_system;
        $this->selectedPermissions = PermissionRegistry::withoutLegacyModuleKeys(
            $role->permissions->pluck('key')->all(),
        );

        if ($role->isSuperAdmin()) {
            $this->selectedPermissions = Permission::query()->pluck('key')->all();
        }
    }

    private function persist(): bool
    {
        if ($this->isReadonly()) {
            return false;
        }

        $user = auth()->user();

        if (! $user instanceof \App\Models\User || ! (
            \App\Services\Access\ModulePageAccess::can($user, 'settings', 'permission', 'create')
            || \App\Services\Access\ModulePageAccess::can($user, 'settings', 'permission', 'edit')
        )) {
            $this->setStatus(__('agricart.access.denied'), 'danger');

            return false;
        }

        $this->saving = true;
        $this->statusMessage = null;
        $this->dispatchBusyState();

        try {
            $this->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('roles', 'name')->ignore($this->roleId),
                ],
                'description' => ['nullable', 'string', 'max:2000'],
                'selectedPermissions' => ['array'],
            ]);

            $service = app(RoleService::class);
            $payload = [
                'name' => $this->name,
                'description' => $this->description ?: null,
            ];
            $permissionKeys = array_values(array_unique($this->selectedPermissions));

            if ($this->roleId) {
                $role = Role::query()->findOrFail($this->roleId);
                $service->update($role, $payload, $permissionKeys, $user);
                $this->setStatus(__('agricart.roles.messages.updated', ['name' => $this->name]), 'success');
            } else {
                $role = $service->create($payload, $permissionKeys, $user);
                $this->roleId = $role->id;
                $this->mode = 'edit';
                $this->setStatus(__('agricart.roles.messages.created', ['name' => $this->name]), 'success');
            }

            return true;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            $this->setStatus($message ?: __('agricart.roles.messages.save_failed'), 'danger');

            return false;
        } catch (\Throwable $exception) {
            $this->setStatus(\App\Support\FriendlyError::message($exception), 'danger');

            return false;
        } finally {
            $this->saving = false;
            $this->dispatchBusyState();
        }
    }

    private function resetForm(): void
    {
        $this->reset([
            'roleId',
            'name',
            'description',
            'isSystem',
            'selectedPermissions',
            'statusMessage',
        ]);

        $this->mode = 'create';
        $this->statusType = 'success';
        $this->saving = false;
    }

    private function setStatus(string $message, string $type): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    private function dispatchBusyState(): void
    {
        $this->dispatch('role-form-busy', saving: $this->saving);
    }
}
