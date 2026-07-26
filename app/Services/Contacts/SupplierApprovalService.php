<?php

namespace App\Services\Contacts;

use App\Models\Supplier;
use Illuminate\Validation\ValidationException;

class SupplierApprovalService
{
    public function approve(Supplier $supplier): Supplier
    {
        if ($supplier->status !== Supplier::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'supplier' => [__('agricart.approvals.suppliers.messages.not_pending')],
            ]);
        }

        $supplier->update([
            'status' => Supplier::STATUS_ACTIVE,
            'rejection_reason' => null,
            'rejected_at' => null,
        ]);

        return $supplier->fresh(['categories']);
    }

    public function reject(Supplier $supplier, string $reason): Supplier
    {
        if ($supplier->status !== Supplier::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'supplier' => [__('agricart.approvals.suppliers.messages.not_pending')],
            ]);
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => [__('agricart.approvals.suppliers.messages.reason_required')],
            ]);
        }

        $supplier->update([
            'status' => Supplier::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'rejected_at' => now(),
        ]);

        return $supplier->fresh(['categories']);
    }
}
