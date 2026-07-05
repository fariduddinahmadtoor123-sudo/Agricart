<?php

namespace App\Filament\Layout;

use Illuminate\Support\Str;

abstract class ClusterPageToolbar
{
    /**
     * @return class-string<\Filament\Clusters\Cluster>
     */
    abstract protected static function subNavigationClass(): string;

    /**
     * @return class-string<\Filament\Pages\Page>
     */
    abstract protected static function overviewPageClass(): string;

    abstract protected static function defaultResourceLabel(): string;

    abstract protected static function defaultResourceSlug(): string;

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
        $subNavigationClass = static::subNavigationClass();

        if (! $subNavigationClass::isModulePath()) {
            return null;
        }

        $pageClass = $subNavigationClass::resolveActivePageClass();
        $resourceLabel = $pageClass ? $pageClass::getNavigationLabel() : static::defaultResourceLabel();
        $resourceSlug = $pageClass ? ($pageClass::getSlug() ?? static::defaultResourceSlug()) : static::defaultResourceSlug();
        $isOverview = $pageClass === static::overviewPageClass() || $pageClass === null;

        return [
            'addLabel' => (! $isOverview && $pageClass)
                ? __('agricart.toolbar.add', ['item' => $resourceLabel])
                : null,
            'searchPlaceholder' => $isOverview
                ? __('agricart.toolbar.search')
                : __('agricart.toolbar.search_resource', ['resource' => mb_strtolower($resourceLabel)]),
            'resourceLabel' => $resourceLabel,
            'resourceSlug' => $resourceSlug,
            'formKey' => static::formKeyFor($resourceSlug),
            'filters' => static::filtersFor($resourceLabel),
            'columns' => static::columnsFor($resourceLabel),
        ];
    }

    protected static function formKeyFor(string $resourceSlug): ?string
    {
        return match ($resourceSlug) {
            'ai-settings' => 'ai-settings',
            'categories' => 'categories',
            default => null,
        };
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public static function filtersFor(string $resourceLabel): array
    {
        return [
            ['label' => __('agricart.toolbar.filter_active', ['resource' => $resourceLabel]), 'value' => 'active'],
            ['label' => __('agricart.toolbar.filter_inactive', ['resource' => $resourceLabel]), 'value' => 'inactive'],
            ['label' => __('agricart.toolbar.filter_pending'), 'value' => 'pending'],
            ['label' => __('agricart.toolbar.filter_approved'), 'value' => 'approved'],
            ['label' => __('agricart.toolbar.filter_rejected'), 'value' => 'rejected'],
            ['label' => __('agricart.toolbar.filter_created_today'), 'value' => 'created_today'],
        ];
    }

    /**
     * @return list<array{label: string, value: string, visible: bool}>
     */
    public static function columnsFor(string $resourceLabel): array
    {
        return [
            ['label' => __('agricart.toolbar.col_id'), 'value' => 'id', 'visible' => true],
            ['label' => __('agricart.toolbar.col_name', ['item' => $resourceLabel]), 'value' => 'name', 'visible' => true],
            ['label' => __('agricart.toolbar.col_status'), 'value' => 'status', 'visible' => true],
            ['label' => __('agricart.toolbar.col_created'), 'value' => 'created_at', 'visible' => true],
            ['label' => __('agricart.toolbar.col_updated'), 'value' => 'updated_at', 'visible' => false],
        ];
    }
}
