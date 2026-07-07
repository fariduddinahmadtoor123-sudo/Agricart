<?php

namespace App\Services\Access;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class StaffApprovalService
{
    public function approve(User $user, ?int $roleId = null): User
    {
        if ($user->status !== User::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'user' => __('agricart.approvals.staff.messages.not_pending'),
            ]);
        }

        if ($roleId !== null) {
            $user->role_id = $roleId;
        }

        if ($user->role_id === null) {
            throw ValidationException::withMessages([
                'role_id' => __('agricart.approvals.staff.messages.role_required'),
            ]);
        }

        $user->update(['status' => User::STATUS_ACTIVE]);

        return $user->fresh(['role']);
    }

    public function reject(User $user): User
    {
        if ($user->status !== User::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'user' => __('agricart.approvals.staff.messages.not_pending'),
            ]);
        }

        $user->update(['status' => User::STATUS_REJECTED]);

        return $user->fresh(['role']);
    }
}
