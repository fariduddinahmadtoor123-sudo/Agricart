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
        $this->assertSame(
            DirectStaffMessengerService::PURPOSE,
            $message->conversation->meta['purpose'] ?? null,
        );

        $again = app(DirectStaffMessengerService::class)->conversationWith($admin, $peer);
        $this->assertSame($message->conversation_id, $again->id);
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
            ->call('toggle')
            ->assertSet('open', true)
            ->assertDontSee('Float Peer', false)
            ->set('staffSearch', 'Float')
            ->assertSee('Float Peer', false)
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

    public function test_directory_lists_active_staff_only(): void
    {
        $admin = $this->superAdmin();
        User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Directory Staff',
            'email' => 'directory-staff@test.test',
        ]);
        User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_INACTIVE,
            'name' => 'Inactive Staff',
            'email' => 'inactive-staff@test.test',
        ]);

        $directory = app(DirectStaffMessengerService::class)->directory($admin);

        $this->assertTrue(
            $directory->contains(fn (array $row): bool => $row['user']->email === 'directory-staff@test.test')
        );
        $this->assertFalse(
            $directory->contains(fn (array $row): bool => $row['user']->email === 'inactive-staff@test.test')
        );
    }

    public function test_float_ui_hides_staff_until_search_then_opens_chat(): void
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
            ->assertSee(__('communication.messenger.fab_label'), false)
            ->call('toggle')
            ->assertSee(__('communication.messenger.title'), false)
            ->assertSee(__('communication.messenger.idle_hint'), false)
            ->assertDontSee('Label Peer', false)
            ->set('staffSearch', 'Label')
            ->assertSee('Label Peer', false)
            ->assertSee('label-peer@test.test', false)
            ->call('selectPeer', $peer->id)
            ->assertSet('peerId', $peer->id)
            ->assertSet('staffSearch', '')
            ->assertSet('errorMessage', null)
            ->assertSee(__('communication.messenger.empty_thread'), false)
            ->assertSee(__('communication.messenger.placeholder'), false);
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

        $this->assertTrue($admin->canUseAdmin());

        Livewire::actingAs($admin)
            ->test(StaffMessengerFloat::class)
            ->call('toggle')
            ->call('selectPeer', $peer->id)
            ->assertSet('peerId', $peer->id)
            ->assertSet('errorMessage', null)
            ->assertSee('Catalog Peer', false)
            ->assertSee(__('communication.messenger.empty_thread'), false);
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
