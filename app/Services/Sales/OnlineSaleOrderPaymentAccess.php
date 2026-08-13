<?php

namespace App\Services\Sales;

use App\Models\User;
use App\Services\Access\ModulePageAccess;

/**
 * Who may view / verify customer payment proofs on online orders.
 */
final class OnlineSaleOrderPaymentAccess
{
    /** @var list<string> */
    private const VIEW_PAGES = [
        'online-quotations',
        'online-orders',
        'order-dispatch',
        'forwarded-orders',
        'inbox',
    ];

    /** @var list<string> */
    private const VERIFY_PAGES = [
        'online-quotations',
        'online-orders',
    ];

    public static function canViewProof(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        foreach (self::VIEW_PAGES as $page) {
            if (ModulePageAccess::can($user, 'sales', $page, 'view')
                || ModulePageAccess::can($user, 'sales', $page, 'edit')) {
                return true;
            }
        }

        return false;
    }

    public static function canVerifyProof(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        foreach (self::VERIFY_PAGES as $page) {
            if (ModulePageAccess::can($user, 'sales', $page, 'edit')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Shared form panel / Livewire gate for online quotation + converted invoice forms.
     */
    public static function canManageOrders(User $user, string $action): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return ModulePageAccess::can($user, 'sales', 'online-orders', $action)
            || ModulePageAccess::can($user, 'sales', 'online-quotations', $action);
    }
}
