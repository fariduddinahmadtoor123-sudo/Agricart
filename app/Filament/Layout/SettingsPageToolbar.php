<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Settings\OverviewPage;
use App\Models\User;
use Filament\Support\Icons\Heroicon;

class SettingsPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return SettingsSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Settings';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'settings';
    }

    /**
     * @return array{
     *     addLabel: string|null,
     *     searchPlaceholder: string,
     *     resourceLabel: string,
     *     resourceSlug: string,
     *     formKey: string|null,
     *     filters: list<array{label: string, value: string}>,
     *     columns: list<array{label: string, value: string, visible: bool}>,
     * }|null
     */
    public static function config(): ?array
    {
        $config = parent::config();

        if ($config === null) {
            return null;
        }

        return match ($config['resourceSlug']) {
            'users' => [
                ...$config,
                'addLabel' => static::userCanManageUsers() ? $config['addLabel'] : null,
                'livewirePrefix' => 'users',
                'columnDataAttr' => 'data-user-column',
                'interactive' => true,
                'filters' => [
                    ['label' => __('agricart.users.statuses.pending'), 'value' => 'status:pending'],
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                    ['label' => __('agricart.users.statuses.rejected'), 'value' => 'status:rejected'],
                ],
                'columns' => [
                    ['label' => __('agricart.users.list.col_user_number'), 'value' => 'user_number', 'visible' => true],
                    ['label' => __('agricart.users.list.col_name'), 'value' => 'name', 'visible' => true],
                    ['label' => __('agricart.users.list.col_email'), 'value' => 'email', 'visible' => true],
                    ['label' => __('agricart.users.list.col_role'), 'value' => 'role', 'visible' => true],
                    ['label' => __('agricart.users.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.users.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
                'formKey' => 'users',
            ],
            'permission' => [
                ...$config,
                'livewirePrefix' => 'roles',
                'interactive' => false,
                'addLabel' => static::userCanManageRoles() ? __('agricart.roles.actions.add') : null,
                'formKey' => 'roles',
                'searchPlaceholder' => __('agricart.roles.list.search'),
            ],
            'ai-logs' => [
                ...$config,
                'addLabel' => null,
                'formKey' => null,
                'searchPlaceholder' => __('agricart.ai.logs.search'),
                'livewirePrefix' => 'ai-logs',
                'filters' => [],
                'filterGroups' => static::aiLogsFilterGroups(),
                'columns' => static::aiLogsColumns(),
                'interactive' => true,
            ],
            'system-errors' => [
                ...$config,
                'addLabel' => null,
                'formKey' => null,
                'searchPlaceholder' => __('agricart.system_errors.search'),
                'livewirePrefix' => 'system-errors',
                'columnDataAttr' => 'data-system-error-column',
                'filters' => [],
                'filterGroups' => static::systemErrorFilterGroups(),
                'columns' => static::systemErrorColumns(),
                'interactive' => true,
            ],
            'activity' => [
                ...$config,
                'addLabel' => null,
                'formKey' => null,
                'searchPlaceholder' => __('agricart.activity.search'),
                'livewirePrefix' => 'activity-logs',
                'columnDataAttr' => 'data-activity-log-column',
                'filters' => [],
                'filterGroups' => static::activityLogsFilterGroups(),
                'columns' => static::activityLogsColumns(),
                'interactive' => true,
            ],
            'media-library' => [
                ...$config,
                'addLabel' => static::userCanManageMedia() ? __('agricart.media.actions.upload') : null,
                'formKey' => 'media-library',
                'searchPlaceholder' => __('agricart.media.search'),
                'livewirePrefix' => 'media-library',
                'filters' => [],
                'filterGroups' => static::mediaFilterGroups(),
                'columns' => [],
                'hideColumns' => true,
                'interactive' => true,
                'toolbarActions' => static::userCanManageMedia() ? [
                    [
                        'label' => __('agricart.media.actions.repair_links'),
                        'value' => 'repair_links',
                        'color' => 'gray',
                        'outlined' => true,
                    ],
                    [
                        'label' => __('agricart.media.actions.prune_orphans'),
                        'value' => 'prune_orphans',
                        'color' => 'gray',
                        'outlined' => true,
                    ],
                ] : [],
            ],
            'ai-settings' => [
                ...$config,
                'addLabel' => __('agricart.toolbar.add_ai'),
                'formKey' => 'ai-settings',
            ],
            'store-profile' => [
                ...$config,
                'addLabel' => __('company_profile.actions.add'),
                'actionIcon' => Heroicon::OutlinedPlus,
                'formKey' => 'company-profile',
                'hideSearch' => true,
                'hideFilters' => true,
                'hideColumns' => true,
                'interactive' => false,
                'filters' => [],
                'columns' => [],
            ],
            'brand-review-messages' => [
                ...$config,
                'addLabel' => null,
                'formKey' => null,
                'hideSearch' => true,
                'hideFilters' => true,
                'hideColumns' => true,
                'interactive' => false,
                'filters' => [],
                'columns' => [],
            ],
            'printing' => [
                ...$config,
                'addLabel' => null,
                'formKey' => null,
                'livewirePrefix' => 'printing',
                'livewireComponent' => 'settings.manage-print-profiles',
                'interactive' => true,
                'hideFilters' => true,
                'searchPlaceholder' => __('agricart.toolbar.search_resource', ['resource' => mb_strtolower($config['resourceLabel'])]),
                'filters' => [],
                'columns' => [],
                'toolbarActions' => static::userCanManagePrinting() ? [
                    [
                        'label' => __('agricart.printing.actions.add_profile'),
                        'value' => 'add-profile',
                        'icon' => Heroicon::OutlinedPlus,
                        'color' => 'primary',
                        'outlined' => false,
                    ],
                ] : [],
            ],
            default => $config,
        };
    }

    /**
     * @return list<array{label: string, filters: list<array{label: string, value: string}>}>
     */
    protected static function aiLogsFilterGroups(): array
    {
        return [
            [
                'label' => __('agricart.ai.logs.filter_status'),
                'filters' => [
                    ['label' => __('agricart.ai.logs.statuses.success'), 'value' => 'status:success'],
                    ['label' => __('agricart.ai.logs.statuses.error'), 'value' => 'status:error'],
                ],
            ],
            [
                'label' => __('agricart.ai.logs.filter_type'),
                'filters' => [
                    ['label' => __('agricart.ai.logs.types.connection_test'), 'value' => 'type:connection_test'],
                    ['label' => __('agricart.ai.logs.types.prompt'), 'value' => 'type:prompt'],
                    ['label' => __('agricart.ai.logs.types.model_fetch'), 'value' => 'type:model_fetch'],
                ],
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: string, visible: bool}>
     */
    protected static function aiLogsColumns(): array
    {
        return [
            ['label' => __('agricart.ai.logs.col_id'), 'value' => 'id', 'visible' => true],
            ['label' => __('agricart.ai.logs.col_type'), 'value' => 'type', 'visible' => true],
            ['label' => __('agricart.ai.logs.col_status'), 'value' => 'status', 'visible' => true],
            ['label' => __('agricart.ai.logs.col_model'), 'value' => 'model', 'visible' => true],
            ['label' => __('agricart.ai.logs.col_duration'), 'value' => 'duration_ms', 'visible' => true],
            ['label' => __('agricart.ai.logs.col_created'), 'value' => 'created_at', 'visible' => true],
        ];
    }

    /**
     * @return list<array{label: string, filters: list<array{label: string, value: string}>}>
     */
    protected static function systemErrorFilterGroups(): array
    {
        return [
            [
                'label' => __('agricart.system_errors.filters.source'),
                'filters' => [
                    ['label' => __('agricart.system_errors.inbox'), 'value' => 'source:inbox'],
                    ['label' => __('agricart.system_errors.file_logs'), 'value' => 'source:file'],
                ],
            ],
            [
                'label' => __('agricart.system_errors.filters.status'),
                'filters' => [
                    ['label' => __('agricart.system_errors.statuses.open'), 'value' => 'status:open'],
                    ['label' => __('agricart.system_errors.statuses.resolved'), 'value' => 'status:resolved'],
                ],
            ],
            [
                'label' => __('agricart.system_errors.filters.severity'),
                'filters' => [
                    ['label' => __('agricart.system_errors.severities.warning'), 'value' => 'severity:warning'],
                    ['label' => __('agricart.system_errors.severities.error'), 'value' => 'severity:error'],
                    ['label' => __('agricart.system_errors.severities.critical'), 'value' => 'severity:critical'],
                ],
            ],
            [
                'label' => __('agricart.system_errors.filters.date'),
                'filters' => [
                    ['label' => __('agricart.system_errors.dates.today'), 'value' => 'date:today'],
                    ['label' => __('agricart.system_errors.dates.seven_days'), 'value' => 'date:7days'],
                    ['label' => __('agricart.system_errors.dates.thirty_days'), 'value' => 'date:30days'],
                ],
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: string, visible: bool}>
     */
    protected static function systemErrorColumns(): array
    {
        return [
            ['label' => __('agricart.system_errors.columns.reference'), 'value' => 'reference', 'visible' => true],
            ['label' => __('agricart.system_errors.columns.severity'), 'value' => 'severity', 'visible' => true],
            ['label' => __('agricart.system_errors.columns.message'), 'value' => 'message', 'visible' => true],
            ['label' => __('agricart.system_errors.columns.occurrences'), 'value' => 'occurrences', 'visible' => true],
            ['label' => __('agricart.system_errors.columns.last_seen'), 'value' => 'last_seen_at', 'visible' => true],
            ['label' => __('agricart.system_errors.columns.status'), 'value' => 'status', 'visible' => true],
        ];
    }

    /**
     * @return list<array{label: string, filters: list<array{label: string, value: string}>}>
     */
    protected static function activityLogsFilterGroups(): array
    {
        return [
            [
                'label' => __('agricart.activity.filter_period'),
                'filters' => [
                    ['label' => __('agricart.activity.periods.today'), 'value' => 'period:today'],
                    ['label' => __('agricart.activity.periods.24h'), 'value' => 'period:24h'],
                    ['label' => __('agricart.activity.periods.7d'), 'value' => 'period:7d'],
                    ['label' => __('agricart.activity.periods.30d'), 'value' => 'period:30d'],
                ],
            ],
            [
                'label' => __('agricart.activity.filter_event'),
                'filters' => [
                    ['label' => __('agricart.activity.events.login'), 'value' => 'event:login'],
                    ['label' => __('agricart.activity.events.logout'), 'value' => 'event:logout'],
                    ['label' => __('agricart.activity.events.failed_login'), 'value' => 'event:failed_login'],
                    ['label' => __('agricart.activity.events.created'), 'value' => 'event:created'],
                    ['label' => __('agricart.activity.events.updated'), 'value' => 'event:updated'],
                    ['label' => __('agricart.activity.events.deleted'), 'value' => 'event:deleted'],
                    ['label' => __('agricart.activity.events.converted'), 'value' => 'event:converted'],
                ],
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: string, visible: bool}>
     */
    protected static function activityLogsColumns(): array
    {
        return [
            ['label' => __('agricart.activity.col_created'), 'value' => 'created_at', 'visible' => true],
            ['label' => __('agricart.activity.col_event'), 'value' => 'event', 'visible' => true],
            ['label' => __('agricart.activity.col_actor'), 'value' => 'actor', 'visible' => true],
            ['label' => __('agricart.activity.col_description'), 'value' => 'description', 'visible' => true],
            ['label' => __('agricart.activity.col_ip'), 'value' => 'ip_address', 'visible' => true],
        ];
    }

    /**
     * @return list<array{label: string, filters: list<array{label: string, value: string}>}>
     */
    protected static function mediaFilterGroups(): array
    {
        return [
            [
                'label' => __('agricart.media.filters.source'),
                'filters' => [
                    ['label' => __('agricart.media.sources.products'), 'value' => 'source:products'],
                    ['label' => __('agricart.media.sources.categories'), 'value' => 'source:categories'],
                    ['label' => __('agricart.media.sources.brands'), 'value' => 'source:brands'],
                    ['label' => __('agricart.media.sources.store'), 'value' => 'source:store'],
                    ['label' => __('agricart.media.sources.profiles'), 'value' => 'source:profiles'],
                    ['label' => __('agricart.media.sources.library'), 'value' => 'source:library'],
                ],
            ],
            [
                'label' => __('agricart.media.filters.usage'),
                'filters' => [
                    ['label' => __('agricart.media.filters.used'), 'value' => 'usage:used'],
                    ['label' => __('agricart.media.filters.unused'), 'value' => 'usage:unused'],
                ],
            ],
            [
                'label' => __('agricart.media.filters.type'),
                'filters' => [
                    ['label' => 'WebP', 'value' => 'type:webp'],
                    ['label' => 'JPEG', 'value' => 'type:jpeg'],
                    ['label' => 'PNG', 'value' => 'type:png'],
                    ['label' => 'GIF', 'value' => 'type:gif'],
                ],
            ],
            [
                'label' => __('agricart.media.filters.date'),
                'filters' => [
                    ['label' => __('agricart.media.filters.today'), 'value' => 'date:today'],
                    ['label' => __('agricart.media.filters.seven_days'), 'value' => 'date:7days'],
                    ['label' => __('agricart.media.filters.thirty_days'), 'value' => 'date:30days'],
                ],
            ],
            [
                'label' => __('agricart.media.filters.status'),
                'filters' => [
                    ['label' => __('agricart.media.filters.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.media.filters.trash'), 'value' => 'status:trash'],
                ],
            ],
        ];
    }

    protected static function userCanManageUsers(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->hasPermission('settings.users.manage'));
    }

    protected static function userCanManageRoles(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->hasPermission('settings.roles.manage'));
    }

    protected static function userCanManagePrinting(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->hasPermission('settings.printing.manage'));
    }

    protected static function userCanManageMedia(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->hasPermission('settings.media.manage'));
    }
}
