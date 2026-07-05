<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Settings\OverviewPage;

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
            'ai-settings' => [
                ...$config,
                'addLabel' => __('agricart.toolbar.add_ai'),
                'formKey' => 'ai-settings',
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
}
