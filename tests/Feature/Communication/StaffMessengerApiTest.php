<?php

namespace Tests\Feature\Communication;

use App\Models\Communication\Message;
use App\Models\Role;
use App\Models\User;
use App\Services\Communication\ChannelRegistry;
use App\Services\Communication\DirectStaffMessengerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffMessengerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(ChannelRegistry::class)->syncDefinitions(
            config('communication.default_channels', []),
        );
    }

    public function test_staff_list_returns_active_admin_peers(): void
    {
        $admin = $this->superAdmin();
        $peer = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Api Peer',
            'email' => 'api-peer@test.test',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.messenger.staff'))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $peer->id,
                'name' => 'Api Peer',
                'email' => 'api-peer@test.test',
            ]);
    }

    public function test_messages_and_send_work_without_comms_permissions(): void
    {
        $role = Role::query()->create([
            'name' => 'Bare',
            'slug' => 'bare-'.uniqid(),
            'is_system' => false,
        ]);

        $admin = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
            'email' => 'bare-api-admin@test.test',
        ]);

        $peer = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Bare Api Peer',
            'email' => 'bare-api-peer@test.test',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.messenger.messages', $peer))
            ->assertOk()
            ->assertJsonPath('peer.id', $peer->id)
            ->assertJsonPath('messages', []);

        $this->actingAs($admin)
            ->postJson(route('admin.messenger.send'), [
                'peer_id' => $peer->id,
                'body' => 'Hello from simple API messenger',
            ])
            ->assertOk()
            ->assertJsonPath('message.body', 'Hello from simple API messenger')
            ->assertJsonPath('message.mine', true);

        $this->assertTrue(
            Message::query()
                ->where('body', 'Hello from simple API messenger')
                ->whereHas('conversation', fn ($q) => $q->where('meta->purpose', DirectStaffMessengerService::PURPOSE))
                ->exists()
        );

        $this->actingAs($admin)
            ->getJson(route('admin.messenger.messages', $peer))
            ->assertOk()
            ->assertJsonFragment(['body' => 'Hello from simple API messenger']);
    }

    private function superAdmin(): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_system' => true],
        );

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
