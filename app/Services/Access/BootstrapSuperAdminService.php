<?php

namespace App\Services\Access;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Schema;

class BootstrapSuperAdminService
{
    public function hasSuperAdminUser(): bool
    {
        $roleId = Role::query()->where('slug', 'super-admin')->value('id');

        if ($roleId === null) {
            return false;
        }

        return User::query()->where('role_id', $roleId)->exists();
    }

    public function shouldPromoteToBootstrapOwner(): bool
    {
        return ! $this->hasSuperAdminUser();
    }

    /**
     * True when this registration creates the very first row in users (system owner).
     */
    public function isFirstUserRegistration(): bool
    {
        return ! User::query()->exists();
    }

    public function canAssignBootstrapOwner(User $user): bool
    {
        if ($this->hasSuperAdminUser() || $user->role_id !== null) {
            return false;
        }

        if (! in_array($user->status, [User::STATUS_ACTIVE, User::STATUS_PENDING], true)) {
            return false;
        }

        $firstUserId = User::query()->orderBy('id')->value('id');

        return $firstUserId !== null && (int) $firstUserId === (int) $user->id;
    }

    public function assignBootstrapOwnerIfEligible(User $user): User
    {
        if (! $this->canAssignBootstrapOwner($user)) {
            return $user;
        }

        return $this->promoteToBootstrapOwner($user);
    }

    public function superAdminRole(): Role
    {
        $this->ensureRolesSeeded();

        return Role::query()->where('slug', 'super-admin')->firstOrFail();
    }

    public function promoteToBootstrapOwner(User $user): User
    {
        $role = $this->superAdminRole();

        $user->update([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
            'rejection_reason' => null,
            'rejected_at' => null,
        ]);

        return $user->fresh(['role']);
    }

    /**
     * Assign Super Admin when the system has none yet (e.g. first production user stuck without role).
     */
    public function repairOwner(?string $email = null): ?User
    {
        if ($this->hasSuperAdminUser()) {
            return null;
        }

        $user = null;

        if (filled($email)) {
            $user = User::query()->where('email', $email)->first();
        }

        $user ??= User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->orderBy('id')
            ->first();

        $user ??= User::query()->orderBy('id')->first();

        if (! $user instanceof User) {
            return null;
        }

        return $this->promoteToBootstrapOwner($user);
    }

    private function ensureRolesSeeded(): void
    {
        if (! Schema::hasTable('roles')) {
            throw new \RuntimeException('Roles table missing. Run php artisan migrate --force.');
        }

        if (Role::query()->where('slug', 'super-admin')->exists()) {
            return;
        }

        app(RolesAndPermissionsSeeder::class)->run();
    }
}
