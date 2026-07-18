<?php

namespace Tests\Feature\Notifications;

use App\Filament\Clusters\Approvals\StaffPage;
use App\Models\OnlineSaleOrder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StoreInquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopbarNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_receives_actionable_pending_request_counts(): void
    {
        $this->actingAs($this->superAdmin());

        User::factory()->count(2)->create(['status' => User::STATUS_PENDING]);
        StoreInquiry::query()->create([
            'name' => 'Customer',
            'phone' => '03001234567',
            'message' => 'Please contact me.',
            'status' => StoreInquiry::STATUS_NEW,
        ]);
        StoreInquiry::query()->create([
            'name' => 'Read Customer',
            'phone' => '03007654321',
            'message' => 'Already reviewed.',
            'status' => StoreInquiry::STATUS_READ,
        ]);
        OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-00001',
            'order_date' => today(),
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
        ]);

        $this->getJson(route('admin.notifications.summary'))
            ->assertOk()
            ->assertJsonPath('total', 4)
            ->assertJsonPath('items.0.key', 'staff_approvals')
            ->assertJsonPath('items.0.count', 2)
            ->assertJsonPath('items.1.key', 'customer_messages')
            ->assertJsonPath('items.1.count', 1)
            ->assertJsonPath('items.2.key', 'online_orders')
            ->assertJsonPath('items.2.count', 1);
    }

    public function test_user_only_sees_notification_types_they_can_access(): void
    {
        $role = Role::query()->create([
            'name' => 'Approval Viewer',
            'slug' => 'approval-viewer',
            'is_system' => false,
        ]);
        $permission = Permission::query()->create([
            'key' => 'approvals.view',
            'group_key' => 'approvals',
            'label_en' => 'View approvals',
            'label_ur' => 'منظوریاں دیکھیں',
            'sort_order' => 1,
        ]);
        $role->permissions()->attach($permission);

        $viewer = User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->actingAs($viewer);

        User::factory()->create(['status' => User::STATUS_PENDING]);
        StoreInquiry::query()->create([
            'name' => 'Customer',
            'phone' => '03001234567',
            'message' => 'Hidden without Store access.',
            'status' => StoreInquiry::STATUS_NEW,
        ]);

        $notificationResponse = $this->getJson(route('admin.notifications.summary'));
        $this->assertSame(200, $notificationResponse->status(), 'Notification summary endpoint must be accessible.');
        $notificationResponse
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.key', 'staff_approvals');

        $this->assertTrue(StaffPage::canAccess());
        $this->assertNotSame('', $viewer->defaultAdminHomeUrl());
        $staffPageResponse = $this->get(StaffPage::getUrl());
        $this->assertSame(200, $staffPageResponse->status(), 'Staff approvals page must be accessible.');
        $staffPageResponse->assertOk();
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
