<?php

namespace App\Services\Access;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoleService
{
    /**
     * @param  list<string>  $permissionKeys
     */
    public function create(array $data, array $permissionKeys, ?User $actor = null): Role
    {
        $validated = $this->validateRoleData($data);

        $validated['slug'] = $this->uniqueSlug($validated['name']);
        $validated['is_system'] = false;

        $this->assertCanManagePermissions($actor);

        $role = Role::query()->create($validated);
        $this->syncPermissions($role, $permissionKeys);

        return $role->fresh(['permissions']);
    }

    /**
     * @param  list<string>  $permissionKeys
     */
    public function update(Role $role, array $data, array $permissionKeys, ?User $actor = null): Role
    {
        $this->ensureEditable($role);
        $this->assertCanManagePermissions($actor);

        $validated = $this->validateRoleData($data, $role->id);
        $validated['slug'] = $this->uniqueSlug($validated['name'], $role->id);

        $role->update($validated);
        $this->syncPermissions($role, $permissionKeys);

        return $role->fresh(['permissions']);
    }

    public function assertCanManagePermissions(?User $actor): void
    {
        if ($actor?->isSuperAdmin()) {
            return;
        }

        throw ValidationException::withMessages([
            'permissions' => __('agricart.roles.messages.permissions_super_admin_only'),
        ]);
    }

    public function delete(Role $role, ?User $actor = null): void
    {
        $this->assertCanManagePermissions($actor);
        $this->ensureEditable($role);

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => __('agricart.roles.messages.in_use'),
            ]);
        }

        $role->permissions()->detach();
        $role->delete();
    }

    public function uniqueSlug(string $name, ?int $exceptId = null): string
    {
        $slug = Str::slug($name) ?: 'role';

        if ($slug === 'super-admin') {
            $slug = 'custom-role';
        }

        $original = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $exceptId)) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateRoleData(array $data, ?int $exceptId = null): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($exceptId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return Arr::only($validator->validated(), array_keys($rules));
    }

    /**
     * @param  list<string>  $permissionKeys
     */
    private function syncPermissions(Role $role, array $permissionKeys): void
    {
        // Registry may gain new keys (e.g. Communication) before a seeder is re-run.
        PermissionRegistry::syncToDatabase();

        $keys = array_values(array_unique(array_filter(
            $permissionKeys,
            fn (mixed $key): bool => is_string($key) && $key !== '',
        )));
        $keys = PermissionRegistry::withoutLegacyModuleKeys($keys);

        $permissionIds = Permission::query()
            ->whereIn('key', $keys)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        // Bump updated_at so EagerLoadUserRole cache keys invalidate.
        $role->touch();
    }

    private function ensureEditable(Role $role): void
    {
        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => __('agricart.roles.messages.system_locked'),
            ]);
        }
    }

    private function slugExists(string $slug, ?int $exceptId): bool
    {
        return Role::query()
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->where('slug', $slug)
            ->exists();
    }
}
