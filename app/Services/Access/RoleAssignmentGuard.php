<?php

namespace App\Services\Access;

use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RoleAssignmentGuard
{
    public function __construct(
        private readonly BootstrapSuperAdminService $bootstrap,
    ) {}

    public function superAdminRoleId(): ?int
    {
        $id = Role::query()->where('slug', 'super-admin')->value('id');

        return $id !== null ? (int) $id : null;
    }

    public function isSuperAdminRoleId(?int $roleId): bool
    {
        $superId = $this->superAdminRoleId();

        return $superId !== null && $roleId !== null && (int) $roleId === $superId;
    }

    /**
     * System owner = lowest-id user who currently holds Super Admin.
     * Fallback when none exist yet: lowest users.id (bootstrap candidate).
     */
    public function ownerUser(): ?User
    {
        $superId = $this->superAdminRoleId();

        if ($superId !== null) {
            $owner = User::query()
                ->where('role_id', $superId)
                ->orderBy('id')
                ->first();

            if ($owner instanceof User) {
                return $owner;
            }
        }

        return User::query()->orderBy('id')->first();
    }

    public function isOwner(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        $owner = $this->ownerUser();

        return $owner instanceof User && (int) $owner->id === (int) $user->id;
    }

    public function actorCanAssignSuperAdmin(?User $actor): bool
    {
        // Super Admin is exclusive to the bootstrap system owner.
        // Never assign (or demote via role UI) through Settings — use bootstrap/repair only.
        return false;
    }

    /**
     * @return array<int, string> role_id => name
     */
    public function assignableRoleOptions(?User $actor): array
    {
        $canSuper = $this->actorCanAssignSuperAdmin($actor);

        return Role::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->filter(fn (Role $role): bool => $canSuper || ! $role->isSuperAdmin())
            ->mapWithKeys(fn (Role $role): array => [(int) $role->id => (string) $role->name])
            ->all();
    }

    public function defaultAssignableRoleId(?User $actor): ?int
    {
        $options = $this->assignableRoleOptions($actor);
        $first = array_key_first($options);

        return $first !== null ? (int) $first : null;
    }

    /**
     * Enforce role assignment rules for create/update/approve.
     *
     * @throws ValidationException
     */
    public function assertCanAssign(?User $actor, ?User $target, ?int $newRoleId): void
    {
        $superId = $this->superAdminRoleId();
        $newRoleId = $newRoleId !== null ? (int) $newRoleId : null;

        if ($target instanceof User && $this->isOwner($target)) {
            $currentRoleId = $target->role_id !== null ? (int) $target->role_id : null;

            if ($newRoleId !== $currentRoleId) {
                throw ValidationException::withMessages([
                    'role_id' => [__('agricart.users.errors.owner_role_locked')],
                ]);
            }

            return;
        }

        if ($this->isSuperAdminRoleId($newRoleId) && ! $this->actorCanAssignSuperAdmin($actor)) {
            throw ValidationException::withMessages([
                'role_id' => [__('agricart.users.errors.super_admin_assign_forbidden')],
            ]);
        }

        if (
            $target instanceof User
            && $this->isSuperAdminRoleId($target->role_id !== null ? (int) $target->role_id : null)
            && ! $this->isSuperAdminRoleId($newRoleId)
            && ! $this->actorCanAssignSuperAdmin($actor)
        ) {
            throw ValidationException::withMessages([
                'role_id' => [__('agricart.users.errors.super_admin_demote_forbidden')],
            ]);
        }

        // Keep bootstrap path exclusive when the system still has no Super Admin.
        if (
            $superId !== null
            && $newRoleId === $superId
            && ! $this->bootstrap->hasSuperAdminUser()
            && ! ($actor instanceof User && $this->isOwner($actor))
        ) {
            throw ValidationException::withMessages([
                'role_id' => [__('agricart.users.errors.super_admin_assign_forbidden')],
            ]);
        }
    }
}
