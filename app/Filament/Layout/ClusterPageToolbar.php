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
                ? 'Add ' . Str::singular($resourceLabel)
                : null,
            'searchPlaceholder' => $isOverview
                ? 'Search...'
                : 'Search ' . strtolower($resourceLabel) . '...',
            'resourceLabel' => $resourceLabel,
            'resourceSlug' => $resourceSlug,
            'filters' => static::filtersFor($resourceLabel),
            'columns' => static::columnsFor($resourceLabel),
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public static function filtersFor(string $resourceLabel): array
    {
        $resource = strtolower($resourceLabel);

        return [
            ['label' => 'Active ' . $resource, 'value' => 'active'],
            ['label' => 'Inactive ' . $resource, 'value' => 'inactive'],
            ['label' => 'Pending', 'value' => 'pending'],
            ['label' => 'Approved', 'value' => 'approved'],
            ['label' => 'Rejected', 'value' => 'rejected'],
            ['label' => 'Created today', 'value' => 'created_today'],
        ];
    }

    /**
     * @return list<array{label: string, value: string, visible: bool}>
     */
    public static function columnsFor(string $resourceLabel): array
    {
        $nameColumn = Str::singular($resourceLabel) . ' name';

        return [
            ['label' => 'ID', 'value' => 'id', 'visible' => true],
            ['label' => $nameColumn, 'value' => 'name', 'visible' => true],
            ['label' => 'Status', 'value' => 'status', 'visible' => true],
            ['label' => 'Created', 'value' => 'created_at', 'visible' => true],
            ['label' => 'Updated', 'value' => 'updated_at', 'visible' => false],
        ];
    }
}
