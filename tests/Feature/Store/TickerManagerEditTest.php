<?php

namespace Tests\Feature\Store;

use App\Livewire\Store\TickerManager;
use App\Models\Role;
use App\Models\StoreTickerMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TickerManagerEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_message_loads_fields_and_save_updates_existing_row(): void
    {
        $admin = $this->superAdmin();

        $message = StoreTickerMessage::query()->create([
            'text_en' => "Pakistan's First Hardware & Agricultural Machinery Super Store is Coming Soon!",
            'text_ur' => null,
            'link' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(TickerManager::class)
            ->call('editMessage', $message->id)
            ->assertSet('editingId', $message->id)
            ->assertSet('text_en', $message->text_en)
            ->set('text_en', 'Grand Opening on 14 August — updated ticker.')
            ->call('saveMessage')
            ->assertHasNoErrors()
            ->assertSet('editingId', null);

        $this->assertDatabaseHas('store_ticker_messages', [
            'id' => $message->id,
            'text_en' => 'Grand Opening on 14 August — updated ticker.',
        ]);

        $this->assertDatabaseMissing('store_ticker_messages', [
            'text_en' => "Pakistan's First Hardware & Agricultural Machinery Super Store is Coming Soon!",
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
