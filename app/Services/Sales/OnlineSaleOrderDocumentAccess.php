<?php

namespace App\Services\Sales;

use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderStaffAssignment;
use App\Models\Sale;
use App\Models\StaffMessageThread;
use App\Models\User;
use App\Services\Access\ModulePageAccess;

/**
 * Who may open online-order invoice / packing print routes.
 * Inbox recipients get document access for orders forwarded to them
 * without needing order-dispatch.print.
 */
final class OnlineSaleOrderDocumentAccess
{
    public static function canPrintPacking(User $user, OnlineSaleOrder $order): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (ModulePageAccess::can($user, 'sales', 'order-dispatch', 'print')
            || ModulePageAccess::can($user, 'sales', 'online-orders', 'print')
            || ModulePageAccess::can($user, 'sales', 'forwarded-orders', 'print')) {
            return true;
        }

        return self::canAccessViaInbox($user, $order);
    }

    public static function canPrintOnlineInvoice(User $user, OnlineSaleOrder $order): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (ModulePageAccess::can($user, 'sales', 'online-orders', 'print')
            || ModulePageAccess::can($user, 'sales', 'order-dispatch', 'print')) {
            return true;
        }

        return self::canAccessViaInbox($user, $order);
    }

    public static function canPrintConvertedSale(User $user, Sale $sale): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (ModulePageAccess::can($user, 'sales', 'pos-sales', 'print')) {
            return true;
        }

        $order = OnlineSaleOrder::query()
            ->where('sale_id', $sale->id)
            ->first();

        if ($order === null) {
            return false;
        }

        return self::canAccessViaInbox($user, $order);
    }

    private static function canAccessViaInbox(User $user, OnlineSaleOrder $order): bool
    {
        if (! ModulePageAccess::can($user, 'sales', 'inbox', 'view')) {
            return false;
        }

        if (OnlineSaleOrderStaffAssignment::query()
            ->where('online_sale_order_id', $order->id)
            ->where('user_id', $user->id)
            ->exists()) {
            return true;
        }

        return StaffMessageThread::query()
            ->where('context_type', StaffMessageThread::CONTEXT_ONLINE_SALE_ORDER)
            ->where('context_id', $order->id)
            ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
            ->exists();
    }
}
