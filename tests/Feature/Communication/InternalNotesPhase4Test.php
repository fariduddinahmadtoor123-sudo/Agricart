<?php

namespace Tests\Feature\Communication;

use App\Livewire\Communication\InternalNotesPanel;
use App\Livewire\Staff\ListInbox;
use App\Models\Category;
use App\Models\Communication\Attachment;
use App\Models\Communication\Conversation;
use App\Models\Communication\ConversationLink;
use App\Models\Communication\Message;
use App\Models\Customer;
use App\Models\OnlineSaleOrder;
use App\Models\OnlineSaleOrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\Communication\ChannelRegistry;
use App\Services\Communication\InternalNotesService;
use App\Services\Communication\StaffOpsBridge;
use App\Services\Staff\StaffMessagingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class InternalNotesPhase4Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake((string) config('communication.attachment_disk', 'local'));

        app(ChannelRegistry::class)->syncDefinitions(
            config('communication.default_channels', []),
        );
    }

    public function test_internal_note_creates_notes_conversation_linked_to_order(): void
    {
        $admin = $this->superAdmin();
        $order = $this->order('WEB-P4-1');

        $result = app(InternalNotesService::class)->postNote(
            $admin,
            'online_sale_order',
            $order->id,
            'Confirm packing checklist before handoff.',
        );

        $conversation = $result['conversation'];
        $this->assertSame('internal_notes', $conversation->channel->key);
        $this->assertSame(Conversation::TYPE_NOTES, $conversation->type);
        $this->assertSame('Confirm packing checklist before handoff.', $result['message']->body);

        $this->assertTrue(
            ConversationLink::query()
                ->where('conversation_id', $conversation->id)
                ->where('linkable_type', 'online_sale_order')
                ->where('linkable_id', $order->id)
                ->where('relation', ConversationLink::RELATION_ABOUT)
                ->exists()
        );

        $again = app(InternalNotesService::class)->conversationForRecord(
            $admin,
            'online_sale_order',
            $order->id,
        );
        $this->assertSame($conversation->id, $again->id);
    }

    public function test_internal_note_with_attachment_stores_comm_attachment(): void
    {
        $admin = $this->superAdmin();
        $order = $this->order('WEB-P4-2');
        $file = UploadedFile::fake()->create('checklist.pdf', 80, 'application/pdf');

        $result = app(InternalNotesService::class)->postNote(
            $admin,
            'online_sale_order',
            $order->id,
            'See attached checklist.',
            [$file],
        );

        $this->assertNotEmpty($result['attachments']);
        $attachment = $result['attachments'][0];
        $this->assertSame('checklist.pdf', $attachment->original_name);
        $this->assertTrue(
            Storage::disk($attachment->disk)->exists($attachment->path)
        );
        $this->assertSame(Message::TYPE_ATTACHMENT, $result['message']->message_type);
    }

    public function test_authenticated_user_can_download_note_attachment(): void
    {
        $admin = $this->superAdmin();
        $order = $this->order('WEB-P4-3');
        $file = UploadedFile::fake()->create('photo-note.pdf', 40, 'application/pdf');

        $result = app(InternalNotesService::class)->postNote(
            $admin,
            'online_sale_order',
            $order->id,
            'Photo note',
            [$file],
        );

        /** @var Attachment $attachment */
        $attachment = $result['attachments'][0];

        $this->actingAs($admin)
            ->get(route('admin.communication.attachments.show', $attachment))
            ->assertOk();
    }

    public function test_guest_cannot_download_attachment(): void
    {
        $admin = $this->superAdmin();
        $order = $this->order('WEB-P4-4');
        $file = UploadedFile::fake()->create('secret.pdf', 40, 'application/pdf');

        $result = app(InternalNotesService::class)->postNote(
            $admin,
            'online_sale_order',
            $order->id,
            'Secret',
            [$file],
        );

        $this->get(route('admin.communication.attachments.show', $result['attachments'][0]))
            ->assertRedirect();
    }

    public function test_livewire_notes_panel_posts_note_on_order(): void
    {
        $admin = $this->superAdmin();
        $order = $this->order('WEB-P4-5');

        Livewire::actingAs($admin)
            ->test(InternalNotesPanel::class, [
                'linkableType' => 'online_sale_order',
                'linkableId' => $order->id,
            ])
            ->set('noteBody', 'Panel note from Livewire.')
            ->call('postNote')
            ->assertHasNoErrors()
            ->assertSee('Panel note from Livewire.', false);

        $this->assertTrue(
            Message::query()
                ->where('body', 'Panel note from Livewire.')
                ->whereHas('conversation.channel', fn ($q) => $q->where('key', 'internal_notes'))
                ->exists()
        );
    }

    public function test_staff_reply_with_file_mirrors_attachment_on_staff_ops(): void
    {
        [$admin, $order, $staff] = $this->seedStaffScenario();

        $thread = app(StaffMessagingService::class)
            ->forwardOrderToStaff($order, $staff, $admin, 'invoice');

        $file = UploadedFile::fake()->create('packing-slip.pdf', 60, 'application/pdf');

        Livewire::actingAs($staff)
            ->test(ListInbox::class)
            ->call('openThread', $thread->id)
            ->set('replyText', 'Slip attached.')
            ->set('replyFiles', [$file])
            ->call('sendReply')
            ->assertHasNoErrors();

        $conversation = app(StaffOpsBridge::class)->findConversation($thread->fresh());
        $this->assertNotNull($conversation);

        $this->assertTrue(
            Attachment::query()
                ->whereHas('message', fn ($q) => $q->where('conversation_id', $conversation->id))
                ->where('original_name', 'packing-slip.pdf')
                ->exists()
        );

        Livewire::actingAs($staff)
            ->test(ListInbox::class)
            ->call('openThread', $thread->id)
            ->assertSee(__('communication.internal_notes.heading'), false)
            ->assertSee('packing-slip.pdf', false);
    }

    /**
     * @return array{0: User, 1: OnlineSaleOrder, 2: User}
     */
    private function seedStaffScenario(): array
    {
        $admin = $this->superAdmin();
        $staff = User::factory()->create([
            'role_id' => $admin->role_id,
            'status' => User::STATUS_ACTIVE,
            'name' => 'Phase4 Staff',
            'email' => 'phase4-staff@test.test',
        ]);

        $customer = Customer::query()->create([
            'customer_number' => 'CUS-P4STAFF',
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Phase',
            'last_name' => 'Four',
            'email' => 'phase4-staff-customer@test.test',
            'phone' => '03001112233',
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);

        $category = Category::query()->create([
            'slug' => 'cat-p4-staff',
            'status' => 'active',
            'name_en' => 'Phase4 Category',
            'name_ur' => 'مرحلہ زمرہ',
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'status' => 'active',
            'code' => 'PRD-P4S',
            'sku' => 'SKU-p4-staff',
            'name_en' => 'Phase4 Product',
            'name_ur' => 'مرحلہ پروڈکٹ',
            'slug' => 'phase4-staff-product',
        ]);

        $sale = Sale::query()->create([
            'sale_number' => 'INV-P4S1',
            'customer_id' => $customer->id,
            'status' => Sale::STATUS_COMPLETED,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
            'subtotal' => 500,
            'total_amount' => 500,
            'amount_paid' => 500,
            'sale_date' => now()->toDateString(),
        ]);

        $order = OnlineSaleOrder::query()->create([
            'order_number' => 'WEB-P4-STAFF',
            'customer_id' => $customer->id,
            'sale_id' => $sale->id,
            'order_date' => now()->toDateString(),
            'locale' => 'en',
            'order_status' => OnlineSaleOrder::STATUS_CONVERTED,
            'fulfillment_status' => OnlineSaleOrder::FULFILLMENT_AWAITING,
            'payment_status' => 'paid',
            'subtotal' => 500,
            'total_amount' => 500,
            'amount_paid' => 500,
            'ship_contact_name' => 'Phase Four',
            'ship_phone' => '03001112233',
            'ship_address' => '1 Test Street',
            'ship_city' => 'Lahore',
            'converted_at' => now(),
            'queued_at' => now(),
            'queue_document' => OnlineSaleOrder::QUEUE_DOCUMENT_INVOICE,
        ]);

        OnlineSaleOrderItem::query()->create([
            'online_sale_order_id' => $order->id,
            'product_id' => $product->id,
            'sort_order' => 1,
            'product_name_en' => $product->name_en,
            'quantity' => 1,
            'unit_price' => 500,
            'line_total' => 500,
        ]);

        return [$admin, $order->fresh(), $staff];
    }

    private function order(string $number): OnlineSaleOrder
    {
        $customer = Customer::query()->create([
            'customer_number' => 'CUS-'.substr(md5($number), 0, 8),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Phase',
            'last_name' => 'Four',
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
