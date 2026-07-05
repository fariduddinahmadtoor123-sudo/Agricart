<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Catalog\OverviewPage;

class CatalogPageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return CatalogSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Catalog';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'catalog';
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

        return match ($config['resourceSlug']) {
            'categories' => [
                ...$config,
                'livewirePrefix' => 'categories',
                'columnDataAttr' => 'data-category-column',
                'interactive' => true,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                    ['label' => __('agricart.form_panel.statuses.draft'), 'value' => 'status:draft'],
                ],
                'columns' => [
                    ['label' => __('agricart.categories.list.col_image'), 'value' => 'image', 'visible' => true],
                    ['label' => __('agricart.categories.list.col_name_en'), 'value' => 'name_en', 'visible' => true],
                    ['label' => __('agricart.categories.list.col_name_ur'), 'value' => 'name_ur', 'visible' => false],
                    ['label' => __('agricart.categories.list.col_slug'), 'value' => 'slug', 'visible' => true],
                    ['label' => __('agricart.categories.list.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.categories.list.col_ai_status'), 'value' => 'ai_status', 'visible' => true],
                    ['label' => __('agricart.categories.list.col_created'), 'value' => 'created_at', 'visible' => true],
                ],
            ],
            default => $config,
        };
    }
}
