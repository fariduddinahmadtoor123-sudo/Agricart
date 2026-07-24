<?php

namespace Tests\Feature\Communication;

use App\Models\Communication\Conversation;
use App\Models\Communication\Message;
use App\Models\Role;
use App\Models\User;
use App\Services\Communication\ChannelRegistry;
use App\Services\Communication\DirectStaffMessengerService;
use App\Services\Communication\StaffGroupMessengerService;
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
            ])
            ->assertJsonPath('unread', 0);
    }

    public function test_incoming_message_sets_unread_and_clears_when_opened(): void
    {
        $admin = $this->superAdmin();
        $peer = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Unread Peer',
            'email' => 'unread-peer@test.test',
        ]);

        app(DirectStaffMessengerService::class)->postMessage($peer, $admin, 'Please check this');

        $this->actingAs($admin)
            ->getJson(route('admin.messenger.staff'))
            ->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonFragment([
                'id' => $peer->id,
                'unread' => 1,
            ]);

        $this->actingAs($admin)
            ->getJson(route('admin.messenger.messages', $peer))
            ->assertOk()
            ->assertJsonPath('unread', 0)
            ->assertJsonFragment(['body' => 'Please check this']);

        $this->actingAs($admin)
            ->getJson(route('admin.messenger.staff'))
            ->assertOk()
            ->assertJsonPath('unread', 0)
            ->assertJsonFragment([
                'id' => $peer->id,
                'unread' => 0,
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

    public function test_staff_can_create_and_chat_in_a_group(): void
    {
        $admin = $this->superAdmin();
        $a = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Group A',
            'email' => 'group-a@test.test',
        ]);
        $b = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Group B',
            'email' => 'group-b@test.test',
        ]);
        $c = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Group C',
            'email' => 'group-c@test.test',
        ]);

        $create = $this->actingAs($admin)
            ->postJson(route('admin.messenger.groups.store'), [
                'title' => 'Sales desk',
                'member_ids' => [$a->id, $b->id, $c->id],
            ])
            ->assertCreated()
            ->assertJsonPath('group.title', 'Sales desk');

        $conversationId = (int) $create->json('group.id');

        $this->assertTrue(
            Conversation::query()
                ->whereKey($conversationId)
                ->where('type', Conversation::TYPE_GROUP)
                ->where('meta->purpose', StaffGroupMessengerService::PURPOSE)
                ->exists()
        );

        $this->actingAs($a)
            ->postJson(route('admin.messenger.groups.send', $conversationId), [
                'body' => 'Hello group',
            ])
            ->assertOk()
            ->assertJsonPath('message.body', 'Hello group');

        $this->actingAs($admin)
            ->getJson(route('admin.messenger.staff'))
            ->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonFragment([
                'id' => $conversationId,
                'title' => 'Sales desk',
                'unread' => 1,
            ]);

        $this->actingAs($admin)
            ->getJson(route('admin.messenger.groups.messages', $conversationId))
            ->assertOk()
            ->assertJsonPath('unread', 0)
            ->assertJsonFragment([
                'body' => 'Hello group',
                'sender' => 'Group A',
            ]);
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
