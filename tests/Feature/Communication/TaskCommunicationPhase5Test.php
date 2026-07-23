<?php

namespace Tests\Feature\Communication;

use App\Livewire\Communication\ListTaskInbox;
use App\Livewire\Communication\TaskCommunicationPanel;
use App\Models\Category;
use App\Models\Communication\ConversationLink;
use App\Models\Communication\Message;
use App\Models\Communication\TaskLink;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Role;
use App\Models\User;
use App\Services\Communication\ChannelRegistry;
use App\Services\Communication\TaskCommunicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class TaskCommunicationPhase5Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(ChannelRegistry::class)->syncDefinitions(
            config('communication.default_channels', []),
        );
    }

    public function test_create_task_linked_to_order_with_assignee_and_message(): void
    {
        $admin = $this->superAdmin();
        $assignee = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Phase5 Assignee',
            'email' => 'phase5-assignee@test.test',
        ]);
        $order = $this->order('WEB-P5-1');

        $result = app(TaskCommunicationService::class)->createTask(
            $admin,
            [$assignee->id],
            [
                'title' => 'Confirm packing list',
                'body' => 'Please verify SKUs before dispatch.',
                'priority' => TaskLink::PRIORITY_HIGH,
                'linkable_type' => 'online_sale_order',
                'linkable_id' => $order->id,
            ],
        );

        $this->assertSame('task', $result['conversation']->channel->key);
        $this->assertSame(TaskLink::STATUS_OPEN, $result['task']->status);
        $this->assertSame($assignee->id, $result['task']->assignable_id);
        $this->assertNotNull($result['message']);
        $this->assertSame('Please verify SKUs before dispatch.', $result['message']->body);

        $this->assertTrue(
            ConversationLink::query()
                ->where('conversation_id', $result['conversation']->id)
                ->where('linkable_type', 'online_sale_order')
                ->where('linkable_id', $order->id)
                ->exists()
        );

        $forAssignee = app(TaskCommunicationService::class)->tasksForUser($assignee, 'open');
        $this->assertTrue($forAssignee->contains('id', $result['task']->id));
    }

    public function test_complete_and_reopen_task(): void
    {
        $admin = $this->superAdmin();
        $order = $this->order('WEB-P5-2');

        $result = app(TaskCommunicationService::class)->createTask(
            $admin,
            [],
            [
                'title' => 'Call customer',
                'linkable_type' => 'online_sale_order',
                'linkable_id' => $order->id,
            ],
        );

        $done = app(TaskCommunicationService::class)->complete($admin, $result['task']);
        $this->assertSame(TaskLink::STATUS_DONE, $done->status);
        $this->assertNotNull($done->completed_at);

        $open = app(TaskCommunicationService::class)->reopen($admin, $done);
        $this->assertSame(TaskLink::STATUS_OPEN, $open->status);
        $this->assertNull($open->completed_at);
    }

    public function test_record_panel_creates_task_and_reply(): void
    {
        $admin = $this->superAdmin();
        $order = $this->order('WEB-P5-3');

        Livewire::actingAs($admin)
            ->test(TaskCommunicationPanel::class, [
                'linkableType' => 'online_sale_order',
                'linkableId' => $order->id,
            ])
            ->set('title', 'Follow up payment proof')
            ->set('body', 'Ask customer for clearer receipt.')
            ->call('createTask')
            ->assertHasNoErrors()
            ->assertSee('Follow up payment proof', false)
            ->set('replyText', 'Customer sent a new photo.')
            ->call('sendReply')
            ->assertHasNoErrors()
            ->assertSee('Customer sent a new photo.', false);

        $this->assertTrue(
            Message::query()
                ->where('body', 'Customer sent a new photo.')
                ->whereHas('conversation.channel', fn ($q) => $q->where('key', 'task'))
                ->exists()
        );
    }

    public function test_task_inbox_lists_created_tasks(): void
    {
        $admin = $this->superAdmin();

        app(TaskCommunicationService::class)->createTask(
            $admin,
            [],
            ['title' => 'Inbox-only follow-up'],
        );

        Livewire::actingAs($admin)
            ->test(ListTaskInbox::class)
            ->assertSee('Inbox-only follow-up', false)
            ->set('title', 'Second task from inbox')
            ->call('createTask')
            ->assertHasNoErrors()
            ->assertSee('Second task from inbox', false);
    }

    public function test_tasks_for_product_and_review_records(): void
    {
        $admin = $this->superAdmin();
        $product = $this->product('phase5-product');
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-P5REV',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Phase',
            'last_name' => 'Reviewer',
            'email' => 'phase5-reviewer@test.test',
            'phone' => '03009998877',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
        $review = ProductReview::query()->create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'submission_path' => ProductReview::PATH_PROOF,
            'rating' => 5,
            'status' => ProductReview::STATUS_PENDING,
            'body' => 'Great seeds',
        ]);

        $productTask = app(TaskCommunicationService::class)->createTask(
            $admin,
            [],
            [
                'title' => 'Update product photos',
                'linkable_type' => 'product',
                'linkable_id' => $product->id,
            ],
        );

        $reviewTask = app(TaskCommunicationService::class)->createTask(
            $admin,
            [],
            [
                'title' => 'Verify review proof',
                'linkable_type' => 'product_review',
                'linkable_id' => $review->id,
            ],
        );

        $productTasks = app(TaskCommunicationService::class)->tasksForRecord(
            $admin,
            'product',
            $product->id,
        );
        $reviewTasks = app(TaskCommunicationService::class)->tasksForRecord(
            $admin,
            'product_review',
            $review->id,
        );

        $this->assertTrue($productTasks->contains('id', $productTask['task']->id));
        $this->assertTrue($reviewTasks->contains('id', $reviewTask['task']->id));
    }

    private function order(string $number): OnlineSaleOrder
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-'.substr(md5($number), 0, 8),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Phase',
            'last_name' => 'Five',
            'email' => strtolower($number).'@test.test',
            'phone' => '0300'.random_int(1000000, 9999999),
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        return OnlineSaleOrder::query()->create([
            'order_number' => $number,
            'order_date' => today(),
            'order_status' => OnlineSaleOrder::STATUS_PENDING,
            'customer_id' => $customer->id,
        ]);
    }

    private function product(string $slug): Product
    {
        $category = Category::query()->create([
            'slug' => 'cat-'.$slug,
            'code' => 'CAT-'.strtoupper(substr(md5($slug), 0, 6)),
            'status' => 'active',
            'name_en' => 'Phase5 Category',
            'name_ur' => 'مرحلہ زمرہ',
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-'.strtoupper(substr(md5($slug), 0, 6)),
            'sku' => 'SKU-'.$slug,
            'name_en' => 'Phase5 Product',
            'name_ur' => 'مرحلہ پروڈکٹ',
            'slug' => $slug,
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
