<?php

namespace App\Services\Staff;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class StaffMemberService
{
    /**
     * Approved staff on the roster (not pending applications).
     *
     * @return Builder<User>
     */
    public function rosterQuery(): Builder
    {
        return User::query()
            ->with(['role'])
            ->whereIn('status', [
                User::STATUS_ACTIVE,
                User::STATUS_INACTIVE,
            ]);
    }

    public function setActive(User $member, ?User $actor = null): User
    {
        $this->assertCanChangeStatus($member, $actor);

        if ($member->role_id === null && ! $member->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'role_id' => [__('agricart.staff.members.messages.role_required_for_active')],
            ]);
        }

        $member->update(['status' => User::STATUS_ACTIVE]);

        return $member->fresh(['role']);
    }

    public function setInactive(User $member, ?User $actor = null): User
    {
        $this->assertCanChangeStatus($member, $actor);

        $member->update(['status' => User::STATUS_INACTIVE]);

        return $member->fresh(['role']);
    }

    private function assertCanChangeStatus(User $member, ?User $actor): void
    {
        $actor ??= auth()->user();

        if (! $actor instanceof User) {
            throw ValidationException::withMessages([
                'user' => [__('agricart.access.denied')],
            ]);
        }

        if ($member->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'user' => [__('agricart.staff.members.messages.cannot_change_super_admin')],
            ]);
        }

        if ($actor->id === $member->id) {
            throw ValidationException::withMessages([
                'user' => [__('agricart.staff.members.messages.cannot_change_self')],
            ]);
        }
    }
}
