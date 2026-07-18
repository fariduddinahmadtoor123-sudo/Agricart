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
        $user = $this->superAdmin();
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

    public function test_viewing_a_new_message_marks_it_read_and_it_can_be_viewed_again(): void
    {
        $user = $this->superAdmin();
        $inquiry = StoreInquiry::query()->create([
            'name' => 'Sara Ahmed',
            'phone' => '03111234567',
            'email' => 'sara@example.com',
            'message' => 'I need information about delivery to Multan.',
            'status' => StoreInquiry::STATUS_NEW,
        ]);

        Livewire::actingAs($user)
            ->test(ListStoreInquiries::class)
            ->call('openView', $inquiry->id)
            ->assertSet('viewInquiryId', $inquiry->id)
            ->assertSee('I need information about delivery to Multan.')
            ->assertSee(__('agricart.store.admin.messages.mark_unread'))
            ->call('closeView')
            ->assertSet('viewInquiryId', null)
            ->call('openView', $inquiry->id)
            ->assertSet('viewInquiryId', $inquiry->id);

        $this->assertDatabaseHas('store_inquiries', [
            'id' => $inquiry->id,
            'status' => StoreInquiry::STATUS_READ,
        ]);
        $this->assertNotNull($inquiry->fresh()->read_at);
    }

    public function test_message_can_move_through_pending_closed_and_reopened_workflow(): void
    {
        $user = $this->superAdmin();
        $inquiry = StoreInquiry::query()->create([
            'name' => 'Usman Malik',
            'phone' => '03211234567',
            'message' => 'Please call me about a bulk order.',
            'status' => StoreInquiry::STATUS_READ,
            'read_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(ListStoreInquiries::class)
            ->call('openView', $inquiry->id)
            ->call('markPending', $inquiry->id)
            ->assertSee(__('agricart.store.admin.messages.statuses.pending'))
            ->call('close', $inquiry->id)
            ->assertSee(__('agricart.store.admin.messages.statuses.closed'))
            ->call('reopen', $inquiry->id)
            ->assertSee(__('agricart.store.admin.messages.statuses.pending'))
            ->call('markRead', $inquiry->id)
            ->call('markUnread', $inquiry->id);

        $inquiry->refresh();

        $this->assertSame(StoreInquiry::STATUS_NEW, $inquiry->status);
        $this->assertNull($inquiry->read_at);
        $this->assertNull($inquiry->closed_at);
    }

    private function superAdmin(): User
    {
        $role = Role::query()->create([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'is_system' => true,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
