<?php

namespace Tests\Feature\Communication;

use App\Livewire\Communication\RecordChannelThreadPanel;
use App\Models\Brand;
use App\Models\Communication\ConversationLink;
use App\Models\Communication\Message;
use App\Models\Communication\Participant;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Communication\ChannelRegistry;
use App\Services\Communication\RecordChannelThreadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierBrandThreadPhase6Test extends TestCase
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

    public function test_supplier_thread_creates_conversation_with_supplier_participant(): void
    {
        $admin = $this->superAdmin();
        $supplier = $this->supplier('SUP-P6-1');

        $result = app(RecordChannelThreadService::class)->postMessage(
            $admin,
            'supplier',
            $supplier->id,
            'Please confirm next week stock for seed drills.',
        );

        $this->assertSame('supplier', $result['conversation']->channel->key);
        $this->assertSame(
            'Please confirm next week stock for seed drills.',
            $result['message']->body,
        );

        $this->assertTrue(
            ConversationLink::query()
                ->where('conversation_id', $result['conversation']->id)
                ->where('linkable_type', 'supplier')
                ->where('linkable_id', $supplier->id)
                ->exists()
        );

        $this->assertTrue(
            Participant::query()
                ->where('conversation_id', $result['conversation']->id)
                ->where('actor_type', 'supplier')
                ->where('actor_id', $supplier->id)
                ->whereNull('left_at')
                ->exists()
        );

        $again = app(RecordChannelThreadService::class)->conversationFor(
            $admin,
            'supplier',
            $supplier->id,
        );
        $this->assertSame($result['conversation']->id, $again->id);
    }

    public function test_brand_thread_is_staff_only_channel(): void
    {
        $admin = $this->superAdmin();
        $brand = $this->brand('brand-p6-1');

        $result = app(RecordChannelThreadService::class)->postMessage(
            $admin,
            'brand',
            $brand->id,
            'Need updated brand story for spring catalog.',
        );

        $this->assertSame('brand', $result['conversation']->channel->key);
        $this->assertTrue(
            ConversationLink::query()
                ->where('conversation_id', $result['conversation']->id)
                ->where('linkable_type', 'brand')
                ->where('linkable_id', $brand->id)
                ->exists()
        );

        $this->assertFalse(
            Participant::query()
                ->where('conversation_id', $result['conversation']->id)
                ->where('actor_type', '!=', 'user')
                ->exists()
        );
    }

    public function test_supplier_thread_supports_attachment(): void
    {
        $admin = $this->superAdmin();
        $supplier = $this->supplier('SUP-P6-2');
        $file = UploadedFile::fake()->create('quote.pdf', 64, 'application/pdf');

        $result = app(RecordChannelThreadService::class)->postMessage(
            $admin,
            'supplier',
            $supplier->id,
            'Attached draft quote.',
            [$file],
        );

        $this->assertNotEmpty($result['attachments']);
        $this->assertSame('quote.pdf', $result['attachments'][0]->original_name);
        $this->assertTrue(
            Storage::disk($result['attachments'][0]->disk)->exists($result['attachments'][0]->path)
        );
    }

    public function test_livewire_brand_panel_posts_message(): void
    {
        $admin = $this->superAdmin();
        $brand = $this->brand('brand-p6-live');

        Livewire::actingAs($admin)
            ->test(RecordChannelThreadPanel::class, [
                'profileKey' => 'brand',
                'linkableId' => $brand->id,
            ])
            ->set('messageBody', 'Brand panel message from Livewire.')
            ->call('postMessage')
            ->assertHasNoErrors()
            ->assertSee('Brand panel message from Livewire.', false);

        $this->assertTrue(
            Message::query()
                ->where('body', 'Brand panel message from Livewire.')
                ->whereHas('conversation.channel', fn ($q) => $q->where('key', 'brand'))
                ->exists()
        );
    }

    public function test_record_thread_profiles_are_config_driven(): void
    {
        $service = app(RecordChannelThreadService::class);

        $supplier = $service->profile('supplier');
        $brand = $service->profile('brand');

        $this->assertSame('supplier', $supplier['channel_key']);
        $this->assertSame('supplier', $supplier['linkable_type']);
        $this->assertTrue($supplier['include_linked_actor']);

        $this->assertSame('brand', $brand['channel_key']);
        $this->assertSame('brand', $brand['linkable_type']);
        $this->assertFalse($brand['include_linked_actor']);
    }

    private function supplier(string $code): Supplier
    {
        return Supplier::query()->create([
            'supplier_code' => $code,
            'status' => 'active',
            'business_name_en' => 'Phase6 Supplier '.$code,
            'business_name_ur' => 'مرحلہ سپلائر',
            'email' => strtolower($code).'@test.test',
            'address_en' => '1 Street',
            'city_en' => 'Lahore',
            'opening_balance' => 0,
            'contacts' => [['mobile_number' => '03001112233', 'contact_name_en' => 'Contact']],
        ]);
    }

    private function brand(string $slug): Brand
    {
        return Brand::query()->create([
            'code' => 'BRD-'.strtoupper(substr(md5($slug), 0, 6)),
            'slug' => $slug,
            'status' => 'active',
            'name_en' => 'Phase6 Brand',
            'name_ur' => 'مرحلہ برانڈ',
            'image_path' => 'brands/'.$slug.'.webp',
            'ai_status' => 'none',
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
