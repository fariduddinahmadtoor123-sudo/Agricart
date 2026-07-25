<?php

namespace Tests\Feature\Filament;

use App\Filament\Clusters\Reports\OverviewPage as ReportsOverviewPage;
use App\Filament\Layout\ReportsPageToolbar;
use App\Filament\Layout\SettingsSubNavigation;
use App\Models\Role;
use App\Models\User;
use App\Services\Reports\ReportCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseCBatch1AdminUxTest extends TestCase
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

    public function test_all_live_reports_expose_csv_export_in_toolbar(): void
    {
        foreach (ReportCatalog::reportPages() as $entry) {
            $pageClass = $entry['page'];
            $url = parse_url($pageClass::getUrl(), PHP_URL_PATH);

            $this->get($url)->assertOk();

            $config = ReportsPageToolbar::config();
            $this->assertNotNull($config, "Missing toolbar config for report [{$entry['key']}]");

            $actionValues = array_column($config['toolbarActions'] ?? [], 'value');
            $this->assertContains(
                'export_csv',
                $actionValues,
                "Report [{$entry['key']}] should expose CSV export in the toolbar.",
            );
        }
    }

    public function test_finance_financial_reports_redirects_to_reports_overview(): void
    {
        $this->get('/admin/finance/financial-reports')
            ->assertRedirect(ReportsOverviewPage::getUrl());
    }

    public function test_settings_tax_tab_shows_coming_soon_in_sub_nav(): void
    {
        $this->get('/admin/settings/overview')->assertOk();

        $taxItem = collect(SettingsSubNavigation::items())
            ->first(fn (array $item): bool => $item['label'] === 'Tax');

        $this->assertNotNull($taxItem);
        $this->assertTrue($taxItem['comingSoon']);
        $this->assertSame(__('agricart.placeholder.sub_nav_badge'), $taxItem['comingSoonBadge']);
    }
}
