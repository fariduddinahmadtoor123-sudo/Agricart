<?php

namespace App\Filament\Layout;

use App\Models\User;
use App\Services\Access\ModulePageAccess;
use App\Services\Access\PermissionRegistry;
use App\Services\Sales\OnlineSaleOrderPaymentAccess;

final class FormPanelRegistry
{
    /**
     * @return array<string, array{component: string, permission: string|null}>
     */
    public static function forms(): array
    {
        return [
            'ai-settings' => ['component' => 'ai-settings.form', 'permission' => 'settings.ai-settings.view'],
            'ai-log-detail' => ['component' => 'ai-logs.detail', 'permission' => 'settings.ai-logs.view'],
            'activity-log-detail' => ['component' => 'activity-logs.detail', 'permission' => 'settings.activity.view'],
            'system-error-detail' => ['component' => 'system-errors.detail', 'permission' => 'settings.system-errors.view'],
            'media-library' => ['component' => 'media.form', 'permission' => 'settings.media-library.view'],
            'categories' => ['component' => 'categories.form', 'permission' => 'catalog.categories.view'],
            'brands' => ['component' => 'brands.form', 'permission' => 'catalog.brands.view'],
            'packages' => ['component' => 'packages.form', 'permission' => 'catalog.packages.view'],
            'units' => ['component' => 'units.form', 'permission' => 'catalog.units.view'],
            'packing-units' => ['component' => 'packing-units.form', 'permission' => 'catalog.packing-units.view'],
            'attributes' => ['component' => 'attributes.form', 'permission' => 'catalog.attributes.view'],
            'controls' => ['component' => 'controls.form', 'permission' => 'catalog.controls.view'],
            'products' => ['component' => 'products.form', 'permission' => 'catalog.products.view'],
            'product-reviews' => ['component' => 'products.agricart-review-editor', 'permission' => 'catalog.product-reviews.view'],
            'labels' => ['component' => 'labels.print-labels-form', 'permission' => 'catalog.labels.view'],
            'users' => ['component' => 'users.form', 'permission' => 'settings.users.view'],
            'staff' => ['component' => 'users.form', 'permission' => 'staff.staff.view'],
            'roles' => ['component' => 'roles.form', 'permission' => 'settings.permission.view'],
            'company-profile' => ['component' => 'company-profile.form', 'permission' => 'settings.store-profile.view'],
            'suppliers' => ['component' => 'suppliers.form', 'permission' => 'contacts.suppliers.view'],
            'customers' => ['component' => 'customers.form', 'permission' => 'contacts.customers.view'],
            'transport-companies' => ['component' => 'transport-companies.form', 'permission' => 'contacts.transport-companies.view'],
            'contact-message-templates' => ['component' => 'contacts.message-template-form', 'permission' => 'contacts.message-templates.view'],
            'staff-salary' => ['component' => 'staff.salary-assignment-form', 'permission' => 'staff.pay-management.view'],
            'staff-attendance' => ['component' => 'staff.daily-attendance-form', 'permission' => 'staff.attendance.view'],
            'staff-advance' => ['component' => 'staff.advance-transaction-form', 'permission' => 'staff.advances.view'],
            'staff-leave' => ['component' => 'staff.leave-settings-form', 'permission' => 'staff.leave-management.view'],
            'staff-payroll' => ['component' => 'staff.payroll-roll-form', 'permission' => 'staff.payroll.view'],
            'finance-bank' => ['component' => 'finance.bank-account-form', 'permission' => 'finance.bank-accounts.view'],
            'finance-fund-transfer' => ['component' => 'finance.fund-transfer-form', 'permission' => 'finance.cash-bank-transfers.view'],
            'finance-expense-category' => ['component' => 'finance.expense-category-form', 'permission' => 'finance.expense-categories.view'],
            'finance-expense' => ['component' => 'finance.expense-form', 'permission' => 'finance.expenses.view'],
            'finance-expense-payment' => ['component' => 'finance.expense-payment-form', 'permission' => 'finance.expense-payments.view'],
            'purchase-planning' => ['component' => 'purchase-planning.form', 'permission' => 'purchasing.purchase-planning.view'],
            'purchase-payment-summary' => ['component' => 'purchase-payment-summaries.form', 'permission' => 'purchasing.purchase-payment-summary.view'],
            'purchase-quotations' => ['component' => 'purchase-quotations.form', 'permission' => 'purchasing.purchase-quotations.view'],
            'purchases' => ['component' => 'purchases.form', 'permission' => 'purchasing.purchases.view'],
            'price-update' => ['component' => 'price-updates.sheet', 'permission' => 'purchasing.price-update.edit'],
            'sales' => ['component' => 'sales.form', 'permission' => 'sales.pos-sales.view'],
            'sale-quotations' => ['component' => 'sale-quotations.form', 'permission' => 'sales.sales-quotations.view'],
            'sale-returns' => ['component' => 'sale-returns.form', 'permission' => 'sales.sales-returns.view'],
            'online-orders' => ['component' => 'online-sale-orders.form', 'permission' => 'sales.online-orders.view'],
            'price-controls' => ['component' => 'price-controls.form', 'permission' => 'marketplace.price-control.view'],
            'quantity-controls' => ['component' => 'quantity-controls.form', 'permission' => 'marketplace.quantity-control.view'],
            'secret-coding' => ['component' => 'secret-codings.form', 'permission' => 'marketplace.secret-coding.view'],
            'tools-reorder-settings' => ['component' => 'tools.re-order-settings-form', 'permission' => 'tools.re-order.view'],
            'sales-channel' => ['component' => 'sales-channels.form', 'permission' => 'marketplace.sales-channels.view'],
            'delivery-methods' => ['component' => 'delivery-methods.form', 'permission' => 'logistics.delivery-methods.view'],
            'logistics-ship' => ['component' => 'logistics.ship-form', 'permission' => 'logistics.shipments.view'],
        ];
    }

    public static function component(string $formKey, User $user, string $mode = 'create'): ?string
    {
        $form = self::forms()[$formKey] ?? null;

        if ($form === null) {
            return null;
        }

        $permission = $form['permission'];

        // Page-scoped keys: module.page.view → .create / .edit by mode.
        if (
            is_string($permission)
            && preg_match('/^([a-z]+)\.([a-z0-9-]+)\.view$/', $permission, $matches) === 1
            && PermissionRegistry::moduleUsesPagePermissions($matches[1])
        ) {
            $permission = ModulePageAccess::key($matches[1], $matches[2], match ($mode) {
                'view' => 'view',
                'edit' => 'edit',
                default => 'create',
            });
        }

        // Message templates: create uses edit (no separate create action).
        if ($formKey === 'contact-message-templates' && $mode === 'create') {
            $permission = 'contacts.message-templates.edit';
        }

        // tools-reorder-settings: page is page-scoped; create/edit modes map via view→create/edit above.

        if ($formKey === 'online-orders') {
            $action = match ($mode) {
                'view' => 'view',
                'edit' => 'edit',
                default => 'create',
            };

            if ($user->isSuperAdmin() || OnlineSaleOrderPaymentAccess::canManageOrders($user, $action)) {
                return $form['component'];
            }

            return null;
        }

        if ($formKey === 'logistics-ship') {
            $action = $mode === 'edit' ? 'edit' : 'view';

            if ($user->isSuperAdmin()) {
                return $form['component'];
            }

            foreach (['shipments', 'shipped', 'delivered'] as $page) {
                if ($action === 'edit' && $page === 'delivered') {
                    continue;
                }

                if (ModulePageAccess::can($user, 'logistics', $page, $action)
                    || ($action === 'view' && ModulePageAccess::can($user, 'logistics', $page, 'view'))) {
                    return $form['component'];
                }
            }

            if (ModulePageAccess::can($user, 'sales', 'order-dispatch', $action)) {
                return $form['component'];
            }

            return null;
        }

        if (! $user->isSuperAdmin() && filled($permission) && ! ModulePageAccess::userHasKey($user, $permission)) {
            return null;
        }

        return $form['component'];
    }
}
