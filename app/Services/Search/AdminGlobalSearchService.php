<?php

namespace App\Services\Search;

use App\Filament\Clusters\BaseAgricartCluster;
use App\Filament\Layout\ClusterSubNavigation;
use App\Filament\Layout\FormPanelRegistry;
use App\Filament\Layout\ModuleChrome;
use App\Models\User;
use App\Services\Approvals\ApprovalsSearchService;
use App\Services\Catalog\CatalogSearchService;
use App\Services\Contacts\ContactsSearchService;
use App\Services\Finance\FinanceSearchService;
use App\Services\Marketplace\MarketplaceSearchService;
use App\Services\Purchasing\PurchasingSearchService;
use App\Services\Sales\SalesSearchService;
use App\Services\Staff\StaffSearchService;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class AdminGlobalSearchService
{
    public function __construct(
        private readonly CatalogSearchService $catalog,
        private readonly ContactsSearchService $contacts,
        private readonly SalesSearchService $sales,
        private readonly PurchasingSearchService $purchasing,
        private readonly StaffSearchService $staff,
        private readonly MarketplaceSearchService $marketplace,
        private readonly ApprovalsSearchService $approvals,
        private readonly FinanceSearchService $finance,
    ) {}

    /**
     * @return array{query: string, groups: list<array{key: string, label: string, items: list<array{label: string, meta: string|null, url: string, image: string|null, panel: array<string, mixed>|null}>}>}
     */
    public function search(string $term, ?User $user = null): array
    {
        $term = trim($term);
        $user ??= auth()->user() instanceof User ? auth()->user() : null;

        if ($term === '' || mb_strlen($term) < 2 || ! $user instanceof User) {
            return ['query' => $term, 'groups' => []];
        }

        $this->ensureAdminPanel();

        $merged = [];

        if ($this->canRead($user, 'catalog')) {
            $merged = array_merge($merged, $this->catalog->search($term));
        }

        if ($this->canRead($user, 'contacts')) {
            $merged = array_merge($merged, $this->contacts->search($term));
        }

        if ($this->canRead($user, 'sales')) {
            $merged = array_merge($merged, $this->sales->search($term));
        }

        if ($this->canRead($user, 'purchasing')) {
            $merged = array_merge($merged, $this->purchasing->search($term));
        }

        if ($this->canRead($user, 'staff')) {
            $merged = array_merge($merged, $this->staff->search($term));
        }

        if ($this->canRead($user, 'marketplace')) {
            $merged = array_merge($merged, $this->marketplace->search($term));
        }

        if ($this->canRead($user, 'approvals')) {
            $merged = array_merge($merged, $this->approvals->search($term));
        }

        if ($this->canRead($user, 'finance')) {
            $merged = array_merge($merged, $this->finance->search($term));
        }

        $pages = $this->pageMatches($term);

        if ($pages !== []) {
            $merged['pages'] = $pages;
        }

        return [
            'query' => $term,
            'groups' => $this->toGroups($merged, $user),
        ];
    }

    /**
     * @return list<array{label: string, meta: string|null, url: string}>
     */
    private function pageMatches(string $term): array
    {
        $needle = mb_strtolower($term);
        $items = [];

        foreach (ModuleChrome::modules() as $module) {
            $moduleKey = (string) $module['key'];
            $moduleLabel = (string) ($module['navLabel'] ?? $moduleKey);
            $comingSoon = (bool) ($module['comingSoon'] ?? false);

            /** @var class-string<ClusterSubNavigation> $subNavigationClass */
            $subNavigationClass = $module['subNavigation'];
            $clusterClass = $subNavigationClass::clusterClass();

            foreach ($clusterClass::getClusteredComponents() as $component) {
                if (! is_subclass_of($component, Page::class)) {
                    continue;
                }

                if (
                    is_subclass_of($clusterClass, BaseAgricartCluster::class)
                    && $comingSoon
                    && ! str_ends_with(class_basename($component), 'OverviewPage')
                ) {
                    continue;
                }

                if (! $component::canAccess()) {
                    continue;
                }

                $label = (string) $component::getNavigationLabel();
                $slug = (string) ($component::getSlug() ?? 'overview');
                $haystack = mb_strtolower($moduleLabel.' '.$label.' '.$slug.' '.$moduleKey);

                if (! str_contains($haystack, $needle)) {
                    continue;
                }

                $items[] = [
                    'label' => $label,
                    'meta' => $moduleLabel,
                    'url' => $component::getUrl(),
                ];

                if (count($items) >= 8) {
                    return $items;
                }
            }
        }

        return $items;
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $merged
     * @return list<array{key: string, label: string, items: list<array{label: string, meta: string|null, url: string, image: string|null, panel: array<string, mixed>|null}>}>
     */
    private function toGroups(array $merged, User $user): array
    {
        $groups = [];

        foreach ($merged as $key => $items) {
            if (! is_array($items) || $items === []) {
                continue;
            }

            $groups[] = [
                'key' => (string) $key,
                'label' => $this->groupLabel((string) $key),
                'items' => array_values(array_map(
                    fn (array $item): array => $this->finalizeHit($item, $user),
                    $items,
                )),
            ];
        }

        return $groups;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{label: string, meta: string|null, url: string, image: string|null, panel: array<string, mixed>|null}
     */
    private function finalizeHit(array $item, User $user): array
    {
        $panel = is_array($item['panel'] ?? null) ? $item['panel'] : null;
        $formKey = is_string($panel['formKey'] ?? null) ? (string) $panel['formKey'] : null;

        if ($formKey !== null) {
            $mode = is_string($panel['mode'] ?? null)
                ? (string) $panel['mode']
                : $this->preferredPanelMode($user, $formKey);

            if (! in_array($mode, ['view', 'edit'], true)) {
                $mode = $this->preferredPanelMode($user, $formKey);
            }

            $panel['mode'] = $mode;

            if (FormPanelRegistry::component($formKey, $user, $mode) === null) {
                $panel = null;
            }
        } else {
            $panel = null;
        }

        $image = $item['image'] ?? null;

        return [
            'label' => (string) ($item['label'] ?? ''),
            'meta' => isset($item['meta']) && filled($item['meta']) ? (string) $item['meta'] : null,
            'url' => (string) ($item['url'] ?? ''),
            'image' => filled($image) ? (string) $image : null,
            'panel' => $panel,
        ];
    }

    private function preferredPanelMode(User $user, string $formKey): string
    {
        if ($formKey === 'online-orders') {
            return 'view';
        }

        if (FormPanelRegistry::component($formKey, $user, 'edit') !== null) {
            return 'edit';
        }

        return 'view';
    }

    private function groupLabel(string $key): string
    {
        $translation = __('agricart.topbar.search_groups.'.$key);

        if ($translation !== 'agricart.topbar.search_groups.'.$key) {
            return (string) $translation;
        }

        return Str::headline(str_replace('_', ' ', $key));
    }

    private function ensureAdminPanel(): void
    {
        if (Filament::getCurrentPanel()?->getId() === 'admin') {
            return;
        }

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function canRead(User $user, string $moduleKey): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($moduleKey === 'staff') {
            return $user->hasAnyPermission(['staff.view', 'staff.view_self']);
        }

        return $user->hasPermission($moduleKey.'.view');
    }
}
