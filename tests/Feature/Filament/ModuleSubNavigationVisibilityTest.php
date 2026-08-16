<?php

namespace Tests\Feature\Filament;

use App\Filament\Clusters\BaseAgricartCluster;
use App\Filament\Layout\ModuleChrome;
use App\Models\Role;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleSubNavigationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);

        $this->actingAs(User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]));
    }

    public function test_every_module_exposes_its_sub_pages_in_cluster_chrome(): void
    {
        $failures = [];

        foreach (ModuleChrome::modules() as $module) {
            /** @var class-string<\App\Filament\Layout\ClusterSubNavigation> $subNav */
            $subNav = $module['subNavigation'];
            $cluster = $subNav::clusterClass();
            $comingSoon = is_subclass_of($cluster, BaseAgricartCluster::class) && $cluster::isComingSoon();

            $expectedLabels = [];

            foreach ($cluster::getClusteredComponents() as $component) {
                if (! is_subclass_of($component, Page::class)) {
                    continue;
                }

                if (
                    method_exists($component, 'registersInModuleChrome')
                    && ! $component::registersInModuleChrome()
                ) {
                    continue;
                }

                if ($comingSoon && ! str_ends_with(class_basename($component), 'OverviewPage')) {
                    continue;
                }

                if (! $component::canAccess()) {
                    continue;
                }

                $expectedLabels[] = $component::getNavigationLabel();
            }

            $overviewClass = "App\\Filament\\Clusters\\".str($module['key'])->studly()."\\OverviewPage";
            $overviewUrl = class_exists($overviewClass)
                ? $overviewClass::getUrl()
                : $cluster::getUrl();

            $this->get($overviewUrl)->assertOk();

            $items = $subNav::items();
            $visibleLabels = array_column($items, 'label');

            foreach ($expectedLabels as $label) {
                if (! in_array($label, $visibleLabels, true)) {
                    $failures[] = "{$module['key']}: missing sub-nav tab [{$label}] (visible: ".implode(', ', $visibleLabels).')';
                }
            }

            if ($expectedLabels === []) {
                $failures[] = "{$module['key']}: expected at least one sub-nav tab";
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_store_and_settings_include_full_known_tabs(): void
    {
        $this->get('/admin/store/overview')->assertOk();
        $storeLabels = array_column(\App\Filament\Layout\StoreSubNavigation::items(), 'label');

        foreach (['Overview', 'Top Bar', 'Ticker', 'Header', 'Menu', 'Pages', 'Footer', 'Items', 'Agricart Inbox', 'Reviews', 'Testimonials', 'Visitor Analytics', 'SEO', 'About', 'Brand Reviews'] as $label) {
            $this->assertContains($label, $storeLabels);
        }

        $this->get('/admin/settings/overview')->assertOk();
        $settingsLabels = array_column(\App\Filament\Layout\SettingsSubNavigation::items(), 'label');

        foreach (['Overview', 'Business Profile', 'Tax', 'Media Library', 'AI', 'Logs', 'Print', 'System Errors', 'Activity', 'Permission', 'Users', 'Backups'] as $label) {
            $this->assertContains($label, $settingsLabels);
        }

        $this->get('/admin/logistics/overview')->assertOk();
        $logisticsLabels = array_column(\App\Filament\Layout\LogisticsSubNavigation::items(), 'label');

        foreach (['Overview', 'Delivery Methods', 'Pending', 'Shipped', 'Delivered'] as $label) {
            $this->assertContains($label, $logisticsLabels);
        }
    }
}
