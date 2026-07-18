<?php

namespace Tests\Feature\Store;

use App\Livewire\Store\ListStoreInquiries;
use App\Models\Role;
use App\Models\StoreInquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListStoreInquiriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_and_close_customer_message(): void
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
        $inquiry = StoreInquiry::query()->create([
            'name' => 'Ali Khan',
            'phone' => '03001234567',
            'message' => 'Please contact me.',
            'status' => StoreInquiry::STATUS_NEW,
        ]);

        Livewire::actingAs($user)
            ->test(ListStoreInquiries::class)
            ->assertSee('Ali Khan')
            ->assertSee('Please contact me.')
            ->call('close', $inquiry->id);

        $this->assertDatabaseHas('store_inquiries', [
            'id' => $inquiry->id,
            'status' => StoreInquiry::STATUS_CLOSED,
        ]);
    }
}
