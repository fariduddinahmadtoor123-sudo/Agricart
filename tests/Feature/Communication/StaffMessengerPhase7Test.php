<?php

namespace Tests\Feature\Communication;

use App\Livewire\Communication\StaffMessengerFloat;
use App\Models\Communication\Message;
use App\Models\Role;
use App\Models\User;
use App\Services\Communication\ChannelRegistry;
use App\Services\Communication\DirectStaffMessengerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffMessengerPhase7Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(ChannelRegistry::class)->syncDefinitions(
            config('communication.default_channels', []),
        );
    }

    public function test_direct_dm_creates_staff_ops_conversation_and_message(): void
    {
        $admin = $this->superAdmin();
        $peer = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Peer Staff',
            'email' => 'peer-staff@test.test',
        ]);

        $message = app(DirectStaffMessengerService::class)->postMessage(
            $admin,
            $peer,
            'Can you check packing for WEB-1?',
        );

        $this->assertSame('Can you check packing for WEB-1?', $message->body);
        $this->assertSame('staff_ops', $message->conversation->channel->key);
    }

    public function test_float_ui_sends_message_to_peer(): void
    {
        $admin = $this->superAdmin();
        $peer = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Float Peer',
            'email' => 'float-peer@test.test',
        ]);

        Livewire::actingAs($admin)
            ->test(StaffMessengerFloat::class)
            ->call('openMessenger')
            ->assertSet('open', true)
            ->call('selectPeer', $peer->id)
            ->assertSet('peerId', $peer->id)
            ->set('messageText', 'Hello from the corner messenger.')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSee('Hello from the corner messenger.', false);

        $this->assertTrue(
            Message::query()
                ->where('body', 'Hello from the corner messenger.')
                ->whereHas('conversation', fn ($q) => $q->where('meta->purpose', DirectStaffMessengerService::PURPOSE))
                ->exists()
        );
    }

    public function test_close_button_closes_messenger_panel(): void
    {
        $admin = $this->superAdmin();

        Livewire::actingAs($admin)
            ->test(StaffMessengerFloat::class)
            ->call('openMessenger')
            ->assertSet('open', true)
            ->call('close')
            ->assertSet('open', false);
    }

    public function test_searchable_staff_finds_maha_style_queries(): void
    {
        $admin = $this->superAdmin();
        User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Maha Qayyum Toor',
            'email' => 'maha@test.test',
        ]);

        $hits = app(DirectStaffMessengerService::class)->searchableStaff($admin, 'mah');

        $this->assertTrue(collect($hits)->contains(fn (array $row): bool => $row['email'] === 'maha@test.test'));
    }

    public function test_float_ui_always_lists_staff_then_opens_compose(): void
    {
        $admin = $this->superAdmin();
        $peer = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Label Peer',
            'email' => 'label-peer@test.test',
        ]);

        Livewire::actingAs($admin)
            ->test(StaffMessengerFloat::class)
            ->call('openMessenger')
            ->assertSee(__('communication.messenger.staff_available', ['count' => 1]), false)
            ->assertSee('Label Peer', false)
            ->assertSee('label-peer@test.test', false)
            ->call('selectPeer', $peer->id)
            ->assertSet('peerId', $peer->id)
            ->assertSee(__('communication.messenger.placeholder'), false)
            ->assertSee(__('communication.messenger.send'), false)
            ->set('messageText', 'Salam from messenger')
            ->call('send')
            ->assertSee('Salam from messenger', false);
    }

    public function test_non_sales_admin_can_open_staff_dm(): void
    {
        $role = Role::query()->create([
            'name' => 'Catalog Only',
            'slug' => 'catalog-only-'.uniqid(),
            'is_system' => false,
        ]);

        $admin = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Catalog Admin',
            'email' => 'catalog-admin-dm@test.test',
        ]);

        $peer = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Catalog Peer',
            'email' => 'catalog-peer-dm@test.test',
        ]);

        Livewire::actingAs($admin)
            ->test(StaffMessengerFloat::class)
            ->call('openMessenger')
            ->call('selectPeer', $peer->id)
            ->assertSet('peerId', $peer->id)
            ->assertSet('errorMessage', null)
            ->assertSee('Catalog Peer', false);
    }

    public function test_admin_with_zero_permissions_can_send_staff_dm(): void
    {
        $role = Role::query()->create([
            'name' => 'Bare Admin',
            'slug' => 'bare-admin-'.uniqid(),
            'is_system' => false,
        ]);

        $admin = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Bare Admin',
            'email' => 'bare-admin-dm@test.test',
        ]);

        $peer = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Bare Peer',
            'email' => 'bare-peer-dm@test.test',
        ]);

        $this->assertFalse($admin->hasPermission('comms.access'));
        $this->assertFalse($admin->hasPermission('comms.channel.staff_ops.access'));

        Livewire::actingAs($admin)
            ->test(StaffMessengerFloat::class)
            ->call('openMessenger')
            ->call('selectPeer', $peer->id)
            ->set('messageText', 'Salam without any comms permission')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('errorMessage', null)
            ->assertSee('Salam without any comms permission', false);
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
