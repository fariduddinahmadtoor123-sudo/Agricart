<?php

namespace Tests\Feature\Store;

use App\Models\StoreRegistrationSetting;
use App\Services\Store\StoreRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationNoticesTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_register_page_shows_editable_notice(): void
    {
        StoreRegistrationSetting::current()->update([
            'staff_title_en' => 'Join the team notice',
            'staff_body_en' => "Hardware partners welcome.\nWe sell with you.",
            'staff_require_acceptance' => true,
        ]);

        $this->get(route('auth.staff.register'))
            ->assertOk()
            ->assertSee('Join the team notice', false)
            ->assertSee('Hardware partners welcome.', false)
            ->assertSee(__('agricart.auth.terms_accepted'), false);
    }

    public function test_supplier_register_page_shows_editable_notice(): void
    {
        StoreRegistrationSetting::current()->update([
            'supplier_title_en' => 'Sell with Agricart',
            'supplier_body_en' => 'Bring your product line and we will help sell it.',
            'supplier_require_acceptance' => false,
        ]);

        $this->get(route('auth.supplier.register'))
            ->assertOk()
            ->assertSee('Sell with Agricart', false)
            ->assertSee('Bring your product line and we will help sell it.', false)
            ->assertDontSee(__('agricart.supplier_register.fields.notice_accepted'), false);
    }

    public function test_staff_submit_requires_notice_acceptance_when_enabled(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        StoreRegistrationSetting::current()->update([
            'staff_body_en' => 'Please read this notice.',
            'staff_require_acceptance' => true,
        ]);

        Livewire::test(\App\Livewire\Auth\RegisterForm::class)
            ->assertSet('requireNoticeAcceptance', true)
            ->set('name', 'Test User')
            ->set('name_ur', 'ٹیسٹ')
            ->set('email', 'staff-notice@example.com')
            ->set('address', 'Lahore')
            ->set('address_ur', 'لاہور')
            ->set('password', 'password123')
            ->set('password_confirmation', 'password123')
            ->set('phoneRows.0.contact_person', 'Test')
            ->set('phoneRows.0.contact_person_ur', 'ٹیسٹ')
            ->set('phoneRows.0.phone_number', '03001234567')
            ->set('bankRows.0.bank_name', 'National Bank of Pakistan (NBP)')
            ->set('bankRows.0.account_title', 'Test User')
            ->set('bankRows.0.account_title_ur', 'ٹیسٹ')
            ->set('bankRows.0.account_number', 'PK00NBPA0000000000000000')
            ->set('profilePhotoUpload', \Illuminate\Http\UploadedFile::fake()->image('profile.jpg', 500, 500))
            ->set('cnicFrontUpload', \Illuminate\Http\UploadedFile::fake()->image('cnic-front.jpg', 800, 500))
            ->set('cnicBackUpload', \Illuminate\Http\UploadedFile::fake()->image('cnic-back.jpg', 800, 500))
            ->set('terms_accepted', false)
            ->call('submit')
            ->assertSet('submitted', false)
            ->assertSet('statusMessage', __('agricart.auth.terms_required'));
    }

    public function test_registration_service_save_updates_notice(): void
    {
        $service = app(StoreRegistrationService::class);

        $service->save([
            'staff_title_en' => 'Updated staff title',
            'staff_body_en' => 'Updated staff body',
            'staff_require_acceptance' => false,
            'supplier_title_en' => 'Updated supplier title',
            'supplier_body_en' => 'Updated supplier body',
            'supplier_require_acceptance' => true,
        ]);

        $notice = $service->publicNotice('staff');

        $this->assertSame('Updated staff title', $notice['title']);
        $this->assertSame('Updated staff body', $notice['body']);
        $this->assertFalse($notice['requireAcceptance']);
        $this->assertStringContainsString('Updated staff body', (string) $notice['bodyHtml']);
    }
}
