<?php

namespace Tests\Feature\Access;

use App\Models\Role;
use App\Models\User;
use App\Services\Access\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminPasswordSessionWipeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.driver' => 'database', 'session.encrypt' => false]);
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_user_service_password_update_wipes_all_sessions(): void
    {
        if (! Schema::hasTable('sessions')) {
            $this->markTestSkipped('sessions table missing.');
        }

        $super = User::factory()->create([
            'role_id' => Role::query()->where('slug', 'super-admin')->value('id'),
            'status' => User::STATUS_ACTIVE,
            'email' => 'super-session@test.test',
        ]);

        $role = Role::query()->create([
            'name' => 'Staff',
            'slug' => 'staff-session-test',
            'is_system' => false,
        ]);

        $user = User::factory()->create([
            'name' => 'Staff User',
            'email' => 'staff-session@test.test',
            'password' => 'OldPass123',
            'status' => User::STATUS_ACTIVE,
            'role_id' => $role->id,
        ]);

        DB::table('sessions')->insert([
            'id' => 'admin-session-1',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => base64_encode(''),
            'last_activity' => time(),
        ]);

        app(UserService::class)->update($user, [
            'name' => $user->name,
            'email' => $user->email,
            'status' => User::STATUS_ACTIVE,
            'role_id' => $role->id,
            'password' => 'NewPass456',
        ], $super);

        $this->assertDatabaseMissing('sessions', ['id' => 'admin-session-1']);
    }
}
