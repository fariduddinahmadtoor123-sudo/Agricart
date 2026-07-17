<?php

namespace Tests\Feature\Dashboard;

use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\Dashboard\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_operational_cards_and_period_summary(): void
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->actingAs($user);

        Sale::query()->create([
            'sale_number' => 'SALE-DASH-1',
            'sale_date' => today(),
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'total_amount' => 1250,
            'amount_paid' => 1250,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        $dashboard = app(DashboardService::class);
        $cards = collect($dashboard->overviewCards())->keyBy('key');
        $rows = collect($dashboard->summaryRows())->keyBy('key');

        $this->assertSame('1,250.00 PKR', $cards['sales_today']['value']);
        $this->assertSame('1,250.00 PKR', $rows['sales']['today']);
        $this->assertNotEmpty($dashboard->attentionItems());
    }
}
