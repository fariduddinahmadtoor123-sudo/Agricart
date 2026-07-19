<?php

namespace App\Filament\Layout;

use App\Models\User;

final class FormPanelRegistry
{
    /**
     * @return array<string, array{component: string, permission: string|null}>
     */
    public static function forms(): array
    {
        return [
            'ai-settings' => ['component' => 'ai-settings.form', 'permission' => 'settings.ai.manage'],
            'ai-log-detail' => ['component' => 'ai-logs.detail', 'permission' => 'settings.ai.logs.view'],
            'system-error-detail' => ['component' => 'system-errors.detail', 'permission' => 'settings.system-errors.view'],
            'media-library' => ['component' => 'media.form', 'permission' => 'settings.media.view'],
            'categories' => ['component' => 'categories.form', 'permission' => 'catalog.view'],
            'brands' => ['component' => 'brands.form', 'permission' => 'catalog.view'],
            'units' => ['component' => 'units.form', 'permission' => 'catalog.view'],
            'packing-units' => ['component' => 'packing-units.form', 'permission' => 'catalog.view'],
            'attributes' => ['component' => 'attributes.form', 'permission' => 'catalog.view'],
            'controls' => ['component' => 'controls.form', 'permission' => 'catalog.view'],
            'products' => ['component' => 'products.form', 'permission' => 'catalog.view'],
            'labels' => ['component' => 'labels.print-labels-form', 'permission' => 'catalog.view'],
            'users' => ['component' => 'users.form', 'permission' => 'settings.users.manage'],
            'staff' => ['component' => 'users.form', 'permission' => 'staff.view'],
            'roles' => ['component' => 'roles.form', 'permission' => 'settings.roles.manage'],
            'company-profile' => ['component' => 'company-profile.form', 'permission' => 'settings.view'],
            'suppliers' => ['component' => 'suppliers.form', 'permission' => 'contacts.view'],
            'customers' => ['component' => 'customers.form', 'permission' => 'contacts.view'],
            'staff-salary' => ['component' => 'staff.salary-assignment-form', 'permission' => 'staff.view'],
            'staff-attendance' => ['component' => 'staff.daily-attendance-form', 'permission' => 'staff.view'],
            'staff-advance' => ['component' => 'staff.advance-transaction-form', 'permission' => 'staff.view'],
            'staff-leave' => ['component' => 'staff.leave-settings-form', 'permission' => 'staff.view'],
            'staff-payroll' => ['component' => 'staff.payroll-roll-form', 'permission' => 'staff.view'],
            'finance-bank' => ['component' => 'finance.bank-account-form', 'permission' => 'finance.view'],
            'finance-fund-transfer' => ['component' => 'finance.fund-transfer-form', 'permission' => 'finance.view'],
            'finance-expense-category' => ['component' => 'finance.expense-category-form', 'permission' => 'finance.view'],
            'finance-expense' => ['component' => 'finance.expense-form', 'permission' => 'finance.view'],
            'finance-expense-payment' => ['component' => 'finance.expense-payment-form', 'permission' => 'finance.view'],
            'purchase-planning' => ['component' => 'purchase-planning.form', 'permission' => 'purchasing.view'],
            'purchase-payment-summary' => ['component' => 'purchase-payment-summaries.form', 'permission' => 'purchasing.view'],
            'purchase-quotations' => ['component' => 'purchase-quotations.form', 'permission' => 'purchasing.view'],
            'purchases' => ['component' => 'purchases.form', 'permission' => 'purchasing.view'],
            'sales' => ['component' => 'sales.form', 'permission' => 'sales.view'],
            'sale-quotations' => ['component' => 'sale-quotations.form', 'permission' => 'sales.view'],
            'sale-returns' => ['component' => 'sale-returns.form', 'permission' => 'sales.view'],
            'online-orders' => ['component' => 'online-sale-orders.form', 'permission' => 'sales.view'],
            'price-controls' => ['component' => 'price-controls.form', 'permission' => 'marketplace.view'],
            'quantity-controls' => ['component' => 'quantity-controls.form', 'permission' => 'marketplace.view'],
            'secret-coding' => ['component' => 'secret-codings.form', 'permission' => 'marketplace.view'],
            'sales-channel' => ['component' => 'sales-channels.form', 'permission' => 'marketplace.view'],
        ];
    }

    public static function component(string $formKey, User $user, string $mode = 'create'): ?string
    {
        $form = self::forms()[$formKey] ?? null;

        if ($form === null) {
            return null;
        }

        $permission = $form['permission'];
        $module = is_string($permission) ? str($permission)->before('.')->toString() : '';

        if (
            in_array($module, ['catalog', 'contacts', 'staff', 'finance', 'purchasing', 'sales', 'marketplace'], true)
            && str_ends_with((string) $permission, '.view')
        ) {
            $permission = $module.'.'.match ($mode) {
                'view' => 'view',
                'edit' => 'edit',
                default => 'create',
            };
        }

        if (! $user->isSuperAdmin() && filled($permission) && ! $user->hasPermission($permission)) {
            return null;
        }

        return $form['component'];
    }
}
