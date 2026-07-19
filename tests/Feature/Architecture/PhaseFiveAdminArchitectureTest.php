<?php

namespace Tests\Feature\Architecture;

use App\Filament\Clusters\Settings\OverviewPage;
use App\Filament\Clusters\Settings\StoreSettingPage;
use App\Filament\Clusters\SettingsCluster;
use App\Filament\Layout\FormPanelRegistry;
use App\Filament\Layout\ModuleChrome;
use App\Livewire\FormPanel\DynamicForm;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseFiveAdminArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_shared_panel_only_contains_one_lazy_dynamic_host(): void
    {
        $panel = file_get_contents(resource_path('views/filament/components/form-panel/index.blade.php'));

        $this->assertSame(1, substr_count($panel, '<livewire:form-panel.dynamic-form'));
        $this->assertStringNotContainsString('<livewire:categories.form', $panel);
        $this->assertStringNotContainsString('<livewire:products.form', $panel);
        $this->assertStringContainsString('form-panel-mount', $panel);

        $this->actingAs($this->superAdmin());
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(DynamicForm::class)
            ->assertSet('component', null)
            ->assertDontSee('data-form-panel-dynamic-child');
        $this->assertSame([], DB::getQueryLog(), 'The unopened dynamic form host queried the database.');
        DB::disableQueryLog();

        $this->assertArrayHasKey('categories', FormPanelRegistry::forms());
    }

    public function test_locked_shell_order_labels_and_settings_destinations_are_preserved(): void
    {
        $modules = ModuleChrome::modules();

        $this->assertCount(15, $modules);
        $this->assertSame([
            'Dashboard', 'Approvals', 'Contacts', 'Catalog', 'Purchasing',
            'Sales', 'Store', 'Logistics', 'Service', 'Reports', 'Staff',
            'Finance', 'Marketplace', 'Settings', 'Docs',
        ], array_column($modules, 'navLabel'));
        $this->assertSame(OverviewPage::getUrl(), SettingsCluster::getNavigationUrl());
        $this->assertNotSame(StoreSettingPage::getUrl(), SettingsCluster::getNavigationUrl());
    }

    public function test_accessibility_and_design_invariants_are_structurally_enforced(): void
    {
        $panel = file_get_contents(resource_path('views/filament/components/form-panel/index.blade.php'));
        $topbar = file_get_contents(resource_path('views/filament/components/topbar/actions.blade.php'));
        $errors = file_get_contents(resource_path('views/livewire/system-errors/list.blade.php'));
        $media = file_get_contents(resource_path('views/livewire/media/list-media.blade.php'));
        $theme = file_get_contents(resource_path('css/filament/admin/theme.css'));

        $this->assertStringContainsString('trapFocus($event)', $panel);
        $this->assertStringContainsString('lastFocusedElement', $panel);
        $this->assertStringContainsString('keydown.escape.window', $panel);
        $this->assertSame(2, substr_count($panel, '[tabindex]:not([tabindex=&quot;-1&quot;])'));
        $this->assertStringNotContainsString('[tabindex]:not([tabindex="-1"])', $panel);
        $this->assertStringNotContainsString('clock" aria-live=', $topbar);
        $this->assertStringContainsString('wire:keydown.enter=', $errors);
        $this->assertStringContainsString('wire:keydown.space.prevent=', $errors);
        $this->assertStringContainsString('aria-label="{{ __(\'agricart.media.actions.view\') }}:', $media);
        $this->assertStringContainsString('alt="{{ $asset->display_name }}"', $media);
        $this->assertStringContainsString('--font-family: Arial, sans-serif', $theme);
        $this->assertStringContainsString('--agricart-radius: 0', $theme);
    }

    private function superAdmin(): User
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
