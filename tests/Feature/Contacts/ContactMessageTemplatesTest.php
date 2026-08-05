<?php

namespace Tests\Feature\Contacts;

use App\Livewire\Contacts\ManageMessageTemplates;
use App\Livewire\Contacts\MessageTemplateForm;
use App\Livewire\Customers\ListCustomers;
use App\Models\AgricartConversation;
use App\Models\AgricartMessage;
use App\Models\ContactMessageTemplate;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Store\AgricartInboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ContactMessageTemplatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_template_is_seeded_bilingual_by_migration(): void
    {
        $welcome = ContactMessageTemplate::query()->where('key', 'welcome')->sole();

        $this->assertSame(ContactMessageTemplate::AUDIENCE_CUSTOMER, $welcome->audience);
        $this->assertTrue($welcome->is_active);
        $this->assertTrue(filled($welcome->subject_en));
        $this->assertTrue(filled($welcome->body_en));
        $this->assertStringContainsString(':name', (string) $welcome->body_en);
        $this->assertTrue(filled($welcome->subject_ur));
        $this->assertTrue(filled($welcome->body_ur));
        $this->assertStringContainsString(':name', (string) $welcome->body_ur);
    }

    public function test_admin_can_create_and_update_customer_templates_via_form_panel(): void
    {
        $admin = $this->superAdmin();

        Livewire::actingAs($admin)
            ->test(MessageTemplateForm::class)
            ->call('openForm', null, 'create')
            ->set('name_en', 'Launch Query')
            ->set('name_ur', 'لانچ سوال')
            ->set('subject_en', 'Quick question for :name')
            ->set('subject_ur', ':name کے لیے مختصر سوال')
            ->set('body_en', "Hello :name,\nDo you need anything before launch?")
            ->set('body_ur', "ہیلو :name،\nلانچ سے پہلے کچھ چاہیے؟")
            ->call('saveAndClose')
            ->assertHasNoErrors()
            ->assertDispatched('close-form-panel');

        $template = ContactMessageTemplate::query()->where('key', 'launch-query')->sole();
        $this->assertSame('Launch Query', $template->name_en);

        Livewire::actingAs($admin)
            ->test(MessageTemplateForm::class)
            ->call('openForm', $template->id, 'edit')
            ->set('subject_en', 'Updated question for :name')
            ->call('saveAndClose')
            ->assertHasNoErrors();

        $this->assertSame('Updated question for :name', $template->fresh()->subject_en);
    }

    public function test_list_dispatches_form_panel_for_view_and_edit(): void
    {
        $admin = $this->superAdmin();
        $welcome = ContactMessageTemplate::query()->where('key', 'welcome')->sole();

        Livewire::actingAs($admin)
            ->test(ManageMessageTemplates::class)
            ->call('openView', $welcome->id)
            ->assertDispatched('open-form-panel');

        Livewire::actingAs($admin)
            ->test(ManageMessageTemplates::class)
            ->call('openEdit', $welcome->id)
            ->assertDispatched('open-form-panel');
    }

    public function test_welcome_template_cannot_be_deleted(): void
    {
        $admin = $this->superAdmin();
        $welcome = ContactMessageTemplate::query()->where('key', 'welcome')->sole();

        Livewire::actingAs($admin)
            ->test(ManageMessageTemplates::class)
            ->call('delete', $welcome->id);

        $this->assertDatabaseHas('contact_message_templates', ['id' => $welcome->id, 'key' => 'welcome']);
    }

    public function test_view_only_contacts_user_cannot_edit_templates(): void
    {
        $viewer = $this->contactsUser(['contacts.view']);

        Livewire::actingAs($viewer)
            ->test(ManageMessageTemplates::class)
            ->call('openCreate')
            ->assertSet('statusTone', 'danger')
            ->assertNotDispatched('open-form-panel');
    }

    public function test_send_message_uses_locale_template_and_can_update_it(): void
    {
        $admin = $this->superAdmin();
        $customer = $this->createCustomer('template-send@test.test');
        $welcome = ContactMessageTemplate::query()->where('key', 'welcome')->sole();

        $query = ContactMessageTemplate::query()->create([
            'key' => 'query',
            'name_en' => 'Query',
            'name_ur' => 'سوال',
            'subject_en' => 'Query for :name',
            'subject_ur' => ':name کے لیے سوال',
            'body_en' => "Hello :name,\nPlease reply when free.",
            'body_ur' => "ہیلو :name،\nفارغ ہوں تو جواب دیں۔",
            'audience' => ContactMessageTemplate::AUDIENCE_CUSTOMER,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        app()->setLocale('en');

        Livewire::actingAs($admin)
            ->test(ListCustomers::class)
            ->call('openSendMessage', $customer->id)
            ->assertSet('messageTemplateId', $welcome->id)
            ->set('messageTemplateId', $query->id)
            ->assertSet('messageSubject', 'Query for '.$customer->displayName())
            ->assertSet('messageBody', "Hello {$customer->displayName()},\nPlease reply when free.")
            ->set('messageBody', "Hello {$customer->displayName()},\nUpdated query body.")
            ->call('saveMessageTemplate')
            ->assertSet('portableStatusTone', 'success');

        $this->assertStringContainsString('Updated query body.', (string) $query->fresh()->body_en);
        $this->assertStringContainsString('Hello :name,', (string) $query->fresh()->body_en);
        $this->assertSame("ہیلو :name،\nفارغ ہوں تو جواب دیں۔", (string) $query->fresh()->body_ur);

        app()->setLocale('ur');

        Livewire::actingAs($admin)
            ->test(ListCustomers::class)
            ->call('openSendMessage', $customer->id)
            ->set('messageTemplateId', $query->id)
            ->assertSet('messageSubject', str_replace(':name', $customer->displayName(), (string) $query->fresh()->subject_ur))
            ->call('sendMessageToCustomer')
            ->assertSet('showMessagePanel', false);

        $conversation = AgricartConversation::query()->where('customer_id', $customer->id)->sole();
        $this->assertSame(str_replace(':name', $customer->displayName(), (string) $query->fresh()->subject_ur), $conversation->subject);
        $this->assertSame(1, app(AgricartInboxService::class)->unreadCountForCustomer($customer));
        $this->assertDatabaseHas('agricart_messages', [
            'agricart_conversation_id' => $conversation->id,
            'sender_type' => AgricartMessage::SENDER_ADMIN,
            'user_id' => $admin->id,
        ]);
    }

    /**
     * @param  list<string>  $permissionKeys
     */
    private function contactsUser(array $permissionKeys): User
    {
        $role = Role::query()->create([
            'name' => 'Contacts Viewer',
            'slug' => 'contacts-viewer-'.uniqid(),
            'is_system' => false,
        ]);

        $permissionIds = [];
        foreach ($permissionKeys as $index => $key) {
            $permission = Permission::query()->firstOrCreate(
                ['key' => $key],
                [
                    'group_key' => 'contacts',
                    'label_en' => $key,
                    'label_ur' => $key,
                    'sort_order' => $index,
                ],
            );
            $permissionIds[] = $permission->id;
        }

        $role->permissions()->sync($permissionIds);

        return User::factory()->create([
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function createCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'customer_number' => 'CUS-'.substr(md5($email), 0, 8),
            'customer_channel' => Customer::CHANNEL_ONLINE,
            'first_name' => 'Inbox',
            'last_name' => 'Customer',
            'email' => $email,
            'phone' => '0300'.random_int(1000000, 9999999),
            'password' => Hash::make('password'),
            'address_line1' => '1 Test Street',
            'city' => 'Lahore',
            'state' => 'Punjab',
            'country' => 'Pakistan',
            'is_active' => true,
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
