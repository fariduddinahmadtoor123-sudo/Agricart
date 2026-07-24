<?php

namespace App\Services\Access;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class StaffApprovalService
{
    public function approve(User $user, ?int $roleId = null, ?User $actor = null): User
    {
        if ($user->status !== User::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'user' => __('agricart.approvals.staff.messages.not_pending'),
            ]);
        }

        $actor ??= auth()->user() instanceof User ? auth()->user() : null;
        $assignedRoleId = $roleId ?? $user->role_id;

        app(RoleAssignmentGuard::class)->assertCanAssign(
            $actor,
            $user,
            $assignedRoleId !== null ? (int) $assignedRoleId : null,
        );

        if ($roleId !== null) {
            $user->role_id = $roleId;
        }

        if ($user->role_id === null) {
            throw ValidationException::withMessages([
                'role_id' => __('agricart.approvals.staff.messages.role_required'),
            ]);
        }

        $user->update([
            'role_id' => $user->role_id,
            'status' => User::STATUS_ACTIVE,
        ]);

        return $user->fresh(['role']);
    }

    public function reject(User $user, string $reason): User
    {
        if ($user->status !== User::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'user' => __('agricart.approvals.staff.messages.not_pending'),
            ]);
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => __('agricart.approvals.staff.messages.reason_required'),
            ]);
        }

        $user->update([
            'status' => User::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'rejected_at' => now(),
        ]);

        return $user->fresh(['role']);
    }
}
