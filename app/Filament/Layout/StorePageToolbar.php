<?php

namespace App\Filament\Layout;

use App\Filament\Clusters\Store\OverviewPage;
use App\Models\AnalyticsSession;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class StorePageToolbar extends ClusterPageToolbar
{
    protected static function subNavigationClass(): string
    {
        return StoreSubNavigation::class;
    }

    protected static function overviewPageClass(): string
    {
        return OverviewPage::class;
    }

    protected static function defaultResourceLabel(): string
    {
        return 'Store';
    }

    protected static function defaultResourceSlug(): string
    {
        return 'store';
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
            'items' => [
                ...$config,
                'livewirePrefix' => 'store-items',
                'columnDataAttr' => 'data-store-item-column',
                'interactive' => true,
                'formKey' => 'products',
                'addLabel' => static::userCanCreateCatalogProduct()
                    ? __('agricart.toolbar.add', ['item' => __('agricart.pages.catalog.products')])
                    : null,
                'filters' => [
                    ['label' => __('agricart.form_panel.statuses.active'), 'value' => 'status:active'],
                    ['label' => __('agricart.form_panel.statuses.inactive'), 'value' => 'status:inactive'],
                    ['label' => __('agricart.form_panel.statuses.draft'), 'value' => 'status:draft'],
                    ['label' => __('agricart.store.admin.items.filter_all'), 'value' => 'status:all'],
                ],
                'columns' => [
                    ['label' => __('agricart.store.admin.items.col_image'), 'value' => 'image', 'visible' => true],
                    ['label' => __('agricart.store.admin.items.col_name'), 'value' => 'name', 'visible' => true],
                    ['label' => __('agricart.store.admin.items.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.store.admin.items.col_category'), 'value' => 'category', 'visible' => true],
                    ['label' => __('agricart.store.admin.items.col_display_in'), 'value' => 'display_in', 'visible' => true],
                    ['label' => __('agricart.store.admin.items.col_sku'), 'value' => 'sku', 'visible' => true],
                ],
            ],
            'messages' => [
                ...$config,
                'livewirePrefix' => 'store-messages',
                'columnDataAttr' => 'data-store-message-column',
                'interactive' => true,
                'formKey' => null,
                'addLabel' => null,
                'filters' => [
                    ['label' => __('agricart.inbox.admin.summary_open'), 'value' => 'status:open'],
                    ['label' => __('agricart.inbox.admin.unassigned'), 'value' => 'queue:unassigned'],
                    ['label' => __('agricart.inbox.admin.mine'), 'value' => 'queue:mine'],
                    ['label' => __('agricart.inbox.statuses.closed'), 'value' => 'status:closed'],
                    ['label' => __('agricart.store.admin.messages.filter_all'), 'value' => 'status:all'],
                ],
                'columns' => [
                    ['label' => __('agricart.store.admin.messages.col_name'), 'value' => 'name', 'visible' => true],
                    ['label' => __('agricart.store.admin.messages.col_contact'), 'value' => 'contact', 'visible' => true],
                    ['label' => __('agricart.store.admin.messages.col_message'), 'value' => 'message', 'visible' => true],
                    ['label' => __('agricart.store.admin.messages.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.store.admin.messages.col_received'), 'value' => 'received', 'visible' => true],
                    ['label' => __('agricart.store.admin.messages.col_actions'), 'value' => 'actions', 'visible' => true],
                ],
            ],
            'reviews' => [
                ...$config,
                'livewirePrefix' => 'store-reviews',
                'columnDataAttr' => 'data-store-review-column',
                'interactive' => true,
                'formKey' => null,
                'addLabel' => null,
                'filters' => [
                    ['label' => __('agricart.store.admin.reviews.filter_all'), 'value' => 'status:all'],
                    ['label' => __('agricart.store.admin.reviews.filter_pending'), 'value' => 'status:pending'],
                    ['label' => __('agricart.store.admin.reviews.filter_approved'), 'value' => 'status:approved'],
                    ['label' => __('agricart.store.admin.reviews.filter_hidden'), 'value' => 'status:hidden'],
                    ['label' => __('agricart.store.admin.reviews.filter_rejected'), 'value' => 'status:rejected'],
                ],
                'columns' => [
                    ['label' => __('agricart.store.admin.reviews.col_product'), 'value' => 'product', 'visible' => true],
                    ['label' => __('agricart.store.admin.reviews.col_customer'), 'value' => 'customer', 'visible' => true],
                    ['label' => __('agricart.store.admin.reviews.col_rating'), 'value' => 'rating', 'visible' => true],
                    ['label' => __('agricart.store.admin.reviews.col_body'), 'value' => 'body', 'visible' => true],
                    ['label' => __('agricart.store.admin.reviews.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.store.admin.reviews.col_received'), 'value' => 'received', 'visible' => true],
                    ['label' => __('agricart.store.admin.reviews.col_actions'), 'value' => 'actions', 'visible' => true],
                ],
            ],
            'testimonials' => [
                ...$config,
                'livewirePrefix' => 'store-testimonials',
                'columnDataAttr' => 'data-store-testimonial-column',
                'interactive' => true,
                'formKey' => null,
                'addLabel' => null,
                'filters' => [
                    ['label' => __('agricart.store.admin.testimonials.filter_all'), 'value' => 'status:all'],
                    ['label' => __('agricart.store.admin.testimonials.filter_pending'), 'value' => 'status:pending'],
                    ['label' => __('agricart.store.admin.testimonials.filter_approved'), 'value' => 'status:approved'],
                    ['label' => __('agricart.store.admin.testimonials.filter_hidden'), 'value' => 'status:hidden'],
                    ['label' => __('agricart.store.admin.testimonials.filter_rejected'), 'value' => 'status:rejected'],
                ],
                'columns' => [
                    ['label' => __('agricart.store.admin.testimonials.col_customer'), 'value' => 'customer', 'visible' => true],
                    ['label' => __('agricart.store.admin.testimonials.col_rating'), 'value' => 'rating', 'visible' => true],
                    ['label' => __('agricart.store.admin.testimonials.col_body'), 'value' => 'body', 'visible' => true],
                    ['label' => __('agricart.store.admin.testimonials.col_featured'), 'value' => 'featured', 'visible' => true],
                    ['label' => __('agricart.store.admin.testimonials.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.store.admin.testimonials.col_received'), 'value' => 'received', 'visible' => true],
                    ['label' => __('agricart.store.admin.testimonials.col_actions'), 'value' => 'actions', 'visible' => true],
                ],
            ],
            'brand-reviews' => [
                ...$config,
                'livewirePrefix' => 'store-brand-reviews',
                'columnDataAttr' => 'data-store-brand-review-column',
                'interactive' => true,
                'formKey' => null,
                'addLabel' => null,
                'filters' => [
                    ['label' => __('agricart.store.admin.brand_reviews.filter_all'), 'value' => 'status:all'],
                    ['label' => __('agricart.store.admin.brand_reviews.filter_pending'), 'value' => 'status:pending'],
                    ['label' => __('agricart.store.admin.brand_reviews.filter_approved'), 'value' => 'status:approved'],
                    ['label' => __('agricart.store.admin.brand_reviews.filter_hidden'), 'value' => 'status:hidden'],
                    ['label' => __('agricart.store.admin.brand_reviews.filter_rejected'), 'value' => 'status:rejected'],
                ],
                'columns' => [
                    ['label' => __('agricart.store.admin.brand_reviews.col_brand'), 'value' => 'brand', 'visible' => true],
                    ['label' => __('agricart.store.admin.brand_reviews.col_customer'), 'value' => 'customer', 'visible' => true],
                    ['label' => __('agricart.store.admin.brand_reviews.col_rating'), 'value' => 'rating', 'visible' => true],
                    ['label' => __('agricart.store.admin.brand_reviews.col_body'), 'value' => 'body', 'visible' => true],
                    ['label' => __('agricart.store.admin.brand_reviews.col_status'), 'value' => 'status', 'visible' => true],
                    ['label' => __('agricart.store.admin.brand_reviews.col_received'), 'value' => 'received', 'visible' => true],
                    ['label' => __('agricart.store.admin.brand_reviews.col_actions'), 'value' => 'actions', 'visible' => true],
                ],
            ],
            'visitor-analytics' => [
                ...$config,
                'analyticsControls' => true,
                'dateFrom' => now()->subDays(29)->toDateString(),
                'dateTo' => now()->toDateString(),
                'analyticsOptions' => static::analyticsFilterOptions(),
                'hideSearch' => true,
                'interactive' => false,
                'formKey' => null,
                'addLabel' => null,
                'filters' => [],
                'columns' => [],
            ],
            default => $config,
        };
    }

    protected static function userCanCreateCatalogProduct(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return \App\Services\Access\ModulePageAccess::can($user, 'catalog', 'products', 'create') || \App\Services\Access\ModulePageAccess::can($user, 'store', 'items', 'create');
    }

    /**
     * @return array{countries: list<string>, cities: list<string>, devices: list<string>, sources: list<string>}
     */
    private static function analyticsFilterOptions(): array
    {
        $options = [
            'countries' => [],
            'cities' => [],
            'devices' => [],
            'sources' => [],
        ];

        if (! Schema::hasTable('analytics_sessions')) {
            return $options;
        }

        foreach ([
            'countries' => 'country_name',
            'cities' => 'city',
            'devices' => 'device_type',
            'sources' => 'traffic_source',
        ] as $key => $column) {
            $options[$key] = AnalyticsSession::query()
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->distinct()
                ->orderBy($column)
                ->limit(200)
                ->pluck($column)
                ->all();
        }

        return $options;
    }
}
