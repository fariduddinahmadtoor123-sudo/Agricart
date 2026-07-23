<?php

namespace Tests\Feature\Notifications;

use App\Filament\Clusters\Approvals\StaffPage;
use App\Models\AgricartConversation;
use App\Models\AgricartMessage;
use App\Models\Category;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TopbarNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_receives_actionable_pending_request_counts(): void
    {
        $this->actingAs($this->superAdmin());

        User::factory()->count(2)->create(['status' => User::STATUS_PENDING]);
        $conversation = AgricartConversation::query()->create([
            'subject' => 'Contact message',
            'topic' => AgricartConversation::TOPIC_CONTACT,
            'status' => AgricartConversation::STATUS_OPEN,
            'guest_name' => 'Customer',
            'guest_phone' => '03001234567',
            'last_message_at' => now(),
        ]);
        AgricartMessage::query()->create([
            'agricart_conversation_id' => $conversation->id,
            'sender_type' => AgricartMessage::SENDER_CUSTOMER,
            'body' => 'Please contact me.',
            'is_system' => false,
        ]);
        AgricartConversation::query()->create([
            'subject' => 'Already read contact',
            'topic' => AgricartConversation::TOPIC_CONTACT,
            'status' => AgricartConversation::STATUS_OPEN,
            'guest_name' => 'Read Customer',
            'guest_phone' => '03007654321',
            'last_message_at' => now()->subHour(),
            'admin_last_read_at' => now(),
        ]);
        OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-00001',
            'order_date' => today(),
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
        ]);
        $category = Category::query()->create([
            'slug' => 'cat-topbar-review',
            'code' => 'CAT-TBREV',
            'status' => 'active',
            'name_en' => 'Topbar Category',
            'name_ur' => 'ٹاپ بار زمرہ',
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-TBREV',
            'sku' => 'SKU-topbar-review',
            'name_en' => 'Topbar Review Product',
            'name_ur' => 'ٹاپ بار ریویو پروڈکٹ',
            'slug' => 'topbar-review-product',
        ]);
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-TBREV',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Topbar',
            'last_name' => 'Reviewer',
            'email' => 'topbar-review@test.test',
            'phone' => '03001112233',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
        ProductReview::query()->create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'submission_path' => ProductReview::PATH_PROOF,
            'rating' => 5,
            'body' => 'Pending review waiting in the topbar bell.',
            'status' => ProductReview::STATUS_PENDING,
        ]);

        $this->getJson(route('admin.notifications.summary'))
            ->assertOk()
            ->assertJsonPath('total', 5)
            ->assertJsonPath('inbox_unread', 0)
            ->assertJsonPath('items.0.key', 'staff_approvals')
            ->assertJsonPath('items.0.count', 2)
            ->assertJsonPath('items.1.key', 'customer_messages')
            ->assertJsonPath('items.1.count', 1)
            ->assertJsonPath('items.2.key', 'product_reviews')
            ->assertJsonPath('items.2.count', 1)
            ->assertJsonPath('items.3.key', 'online_orders')
            ->assertJsonPath('items.3.count', 1);
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
        AgricartConversation::query()->create([
            'subject' => 'Hidden without Store access',
            'topic' => AgricartConversation::TOPIC_CONTACT,
            'status' => AgricartConversation::STATUS_OPEN,
            'guest_name' => 'Customer',
            'guest_phone' => '03001234567',
            'last_message_at' => now(),
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
