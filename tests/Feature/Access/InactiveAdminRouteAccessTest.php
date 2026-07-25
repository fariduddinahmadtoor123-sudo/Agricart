<?php

namespace Tests\Feature\Access;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveAdminRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_active_staff_can_access_admin_notification_summary(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->nonSuperAdminRoleId(),
            'status' => User::STATUS_ACTIVE,
            'email' => 'active-staff@test.test',
        ]);

        $this->actingAs($user)
            ->get(route('admin.notifications.summary'))
            ->assertOk();
    }

    public function test_inactive_staff_receives_forbidden_on_admin_routes(): void
    {
        $user = User::factory()->create([
            'role_id' => $this->nonSuperAdminRoleId(),
            'status' => User::STATUS_INACTIVE,
            'email' => 'inactive-staff@test.test',
        ]);

        $this->actingAs($user)
            ->get(route('admin.notifications.summary'))
            ->assertForbidden();
    }

    private function nonSuperAdminRoleId(): int
    {
        $role = Role::query()->where('slug', '!=', 'super-admin')->orderBy('id')->first()
            ?? Role::query()->create([
                'name' => 'Staff',
                'slug' => 'staff-'.uniqid(),
                'is_system' => false,
            ]);

        return (int) $role->id;
    }
}
