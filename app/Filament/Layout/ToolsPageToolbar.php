<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Tools\OverviewPage;
use Filament\Support\Icons\Heroicon;

class ToolsPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return ToolsSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'External Apps';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'tools';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function config(): ?array
    {
        $config = parent::config();

        if ($config === null) {
            return null;
        }

        $slug = (string) ($config['resourceSlug'] ?? '');

        if ($slug === 're-order') {
            $actions = [];

            if (static::userCan('create')) {
                $actions[] = [
                    'label' => __('agricart.pages.tools.re_order_new_order'),
                    'value' => 'new-order',
                    'color' => 'primary',
                    'outlined' => false,
                    'icon' => Heroicon::OutlinedPlus,
                    'screen' => 'list',
                ];
            }

            if (static::userCan('edit')) {
                $actions[] = [
                    'label' => __('agricart.pages.tools.re_order_settings'),
                    'value' => 'settings',
                    'outlined' => true,
                    'icon' => Heroicon::OutlinedCog6Tooth,
                ];
            }

            if (static::userCan('create')) {
                $actions[] = [
                    'label' => 'EN',
                    'value' => 'lang-en',
                    'outlined' => true,
                    'compact' => true,
                    'screen' => 'form',
                ];
                $actions[] = [
                    'label' => 'UR',
                    'value' => 'lang-ur',
                    'outlined' => true,
                    'compact' => true,
                    'screen' => 'form',
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.re_order_form_add_page'),
                    'value' => 'add-page',
                    'outlined' => true,
                    'screen' => 'form',
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.re_order_form_remove_page'),
                    'value' => 'remove-page',
                    'outlined' => true,
                    'screen' => 'form',
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.re_order_form_print'),
                    'value' => 'print',
                    'outlined' => true,
                    'screen' => 'form',
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.re_order_form_clear_all'),
                    'value' => 'clear-all',
                    'color' => 'danger',
                    'outlined' => true,
                    'screen' => 'form',
                    'formMode' => 'edit',
                    'confirm' => __('agricart.pages.tools.re_order_form_clear_all_confirm'),
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.re_order_form_save_close'),
                    'value' => 'save-close',
                    'color' => 'primary',
                    'outlined' => false,
                    'screen' => 'form',
                    'formMode' => 'edit',
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.re_order_form_close'),
                    'value' => 'close-form',
                    'outlined' => true,
                    'screen' => 'form',
                ];
            }

            return [
                ...$config,
                'livewirePrefix' => 'tools-reorder',
                'interactive' => true,
                'hideSearch' => false,
                'hideFilters' => true,
                'hideColumns' => true,
                'addLabel' => null,
                'searchPlaceholder' => __('agricart.pages.tools.re_order_search'),
                'toolbarActions' => $actions,
                'toolsReorderChrome' => true,
            ];
        }

        if ($slug === 'purchase-summary') {
            $actions = [];

            if (static::userCan('create')) {
                $actions[] = [
                    'label' => __('agricart.pages.tools.ps_new_summary'),
                    'value' => 'new-summary',
                    'color' => 'primary',
                    'outlined' => false,
                    'icon' => Heroicon::OutlinedPlus,
                    'screen' => 'list',
                ];
            }

            if (static::userCan('create')) {
                $actions[] = [
                    'label' => 'EN',
                    'value' => 'lang-en',
                    'outlined' => true,
                    'compact' => true,
                    'screen' => 'form',
                ];
                $actions[] = [
                    'label' => 'UR',
                    'value' => 'lang-ur',
                    'outlined' => true,
                    'compact' => true,
                    'screen' => 'form',
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.ps_form_add_page'),
                    'value' => 'add-page',
                    'outlined' => true,
                    'screen' => 'form',
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.ps_form_remove_page'),
                    'value' => 'remove-page',
                    'outlined' => true,
                    'screen' => 'form',
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.ps_form_print'),
                    'value' => 'print',
                    'outlined' => true,
                    'screen' => 'form',
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.ps_form_clear_all'),
                    'value' => 'clear-all',
                    'color' => 'danger',
                    'outlined' => true,
                    'screen' => 'form',
                    'formMode' => 'edit',
                    'confirm' => __('agricart.pages.tools.ps_form_clear_all_confirm'),
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.ps_form_save_close'),
                    'value' => 'save-close',
                    'color' => 'primary',
                    'outlined' => false,
                    'screen' => 'form',
                    'formMode' => 'edit',
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.ps_form_close'),
                    'value' => 'close-form',
                    'outlined' => true,
                    'screen' => 'form',
                ];
            }

            return [
                ...$config,
                'livewirePrefix' => 'tools-purchase-summary',
                'interactive' => true,
                'hideSearch' => false,
                'hideFilters' => true,
                'hideColumns' => true,
                'addLabel' => null,
                'searchPlaceholder' => __('agricart.pages.tools.ps_search'),
                'toolbarActions' => $actions,
                'toolsPurchaseSummaryChrome' => true,
            ];
        }

        if ($slug === 'pending-payments') {
            $actions = [];

            if (static::userCan('create')) {
                $actions[] = [
                    'label' => __('agricart.pages.tools.pp_new_sheet'),
                    'value' => 'new-sheet',
                    'color' => 'primary',
                    'outlined' => false,
                    'icon' => Heroicon::OutlinedPlus,
                    'screen' => 'list',
                ];
            }

            if (static::userCan('create')) {
                $actions[] = [
                    'label' => 'EN',
                    'value' => 'lang-en',
                    'outlined' => true,
                    'compact' => true,
                    'screen' => 'form',
                ];
                $actions[] = [
                    'label' => 'UR',
                    'value' => 'lang-ur',
                    'outlined' => true,
                    'compact' => true,
                    'screen' => 'form',
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.pp_form_add_page'),
                    'value' => 'add-page',
                    'outlined' => true,
                    'screen' => 'form',
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.pp_form_remove_page'),
                    'value' => 'remove-page',
                    'outlined' => true,
                    'screen' => 'form',
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.pp_form_print'),
                    'value' => 'print',
                    'outlined' => true,
                    'screen' => 'form',
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.pp_form_clear_all'),
                    'value' => 'clear-all',
                    'color' => 'danger',
                    'outlined' => true,
                    'screen' => 'form',
                    'formMode' => 'edit',
                    'confirm' => __('agricart.pages.tools.pp_form_clear_all_confirm'),
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.pp_form_save_close'),
                    'value' => 'save-close',
                    'color' => 'primary',
                    'outlined' => false,
                    'screen' => 'form',
                    'formMode' => 'edit',
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.pp_form_close'),
                    'value' => 'close-form',
                    'outlined' => true,
                    'screen' => 'form',
                ];
            }

            return [
                ...$config,
                'livewirePrefix' => 'tools-pending-payments',
                'interactive' => true,
                'hideSearch' => false,
                'hideFilters' => true,
                'hideColumns' => true,
                'addLabel' => null,
                'searchPlaceholder' => __('agricart.pages.tools.pp_search'),
                'toolbarActions' => $actions,
                'toolsPendingPaymentsChrome' => true,
            ];
        }

        if ($slug === 'printing') {
            $actions = [];

            if (static::userCan('create')) {
                $actions[] = [
                    'label' => __('agricart.pages.tools.bp_new_batch'),
                    'value' => 'new-batch',
                    'color' => 'primary',
                    'outlined' => false,
                    'icon' => Heroicon::OutlinedPlus,
                    'screen' => 'list',
                ];
            }

            $actions[] = [
                'label' => __('agricart.pages.tools.bp_print'),
                'value' => 'print',
                'outlined' => true,
                'screen' => 'form',
            ];

            if (static::userCan('create')) {
                $actions[] = [
                    'label' => __('agricart.pages.tools.bp_clear'),
                    'value' => 'clear-all',
                    'color' => 'danger',
                    'outlined' => true,
                    'screen' => 'form',
                    'formMode' => 'edit',
                    'confirm' => __('agricart.pages.tools.bp_clear_confirm'),
                ];
            }

            $actions[] = [
                'label' => __('agricart.pages.tools.bp_close'),
                'value' => 'close-form',
                'color' => 'primary',
                'outlined' => false,
                'screen' => 'form',
            ];

            return [
                ...$config,
                'livewirePrefix' => 'tools-bill-printing',
                'interactive' => true,
                'hideSearch' => false,
                'hideFilters' => true,
                'hideColumns' => true,
                'addLabel' => null,
                'searchPlaceholder' => __('agricart.pages.tools.bp_search'),
                'toolbarActions' => $actions,
                'toolsBillPrintingChrome' => true,
            ];
        }

        if ($slug === 'barcode-printing') {
            $actions = [];

            if (static::userCan('create') || static::userCan('edit')) {
                $actions[] = [
                    'label' => __('agricart.pages.tools.bc_add_sticker'),
                    'value' => 'add-sticker',
                    'color' => 'primary',
                    'outlined' => false,
                    'icon' => Heroicon::OutlinedPlus,
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.bc_add_sticker_size'),
                    'value' => 'add-sticker-size',
                    'outlined' => true,
                    'icon' => Heroicon::OutlinedSquaresPlus,
                ];
            }

            return [
                ...$config,
                'livewirePrefix' => 'tools-barcode-printing',
                'interactive' => true,
                'hideSearch' => false,
                'hideFilters' => true,
                'hideColumns' => true,
                'addLabel' => null,
                'searchPlaceholder' => __('agricart.pages.tools.bc_search'),
                'toolbarActions' => $actions,
                'toolsBarcodePrintingChrome' => true,
            ];
        }

        if ($slug === 'home-expenses') {
            $actions = [];

            if (static::userCan('create') || static::userCan('edit')) {
                $actions[] = [
                    'label' => __('agricart.pages.tools.he_add_expense'),
                    'value' => 'add-expense',
                    'color' => 'primary',
                    'outlined' => false,
                    'icon' => Heroicon::OutlinedPlus,
                ];
                $actions[] = [
                    'label' => __('agricart.pages.tools.he_close_sheet'),
                    'value' => 'close-sheet',
                    'outlined' => true,
                    'confirm' => __('agricart.pages.tools.he_close_sheet_confirm'),
                ];
            }

            return [
                ...$config,
                'livewirePrefix' => 'tools-home-expenses',
                'interactive' => true,
                'hideSearch' => false,
                'hideFilters' => true,
                'hideColumns' => true,
                'addLabel' => null,
                'searchPlaceholder' => __('agricart.pages.tools.he_search'),
                'toolbarActions' => $actions,
                'toolsHomeExpensesChrome' => true,
            ];
        }

        return [
            ...$config,
            'addLabel' => null,
            'hideSearch' => true,
            'hideFilters' => true,
            'hideColumns' => true,
        ];
    }
}
