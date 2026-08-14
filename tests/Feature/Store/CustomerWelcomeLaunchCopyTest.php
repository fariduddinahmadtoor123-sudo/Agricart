<?php

namespace Tests\Feature\Store;

use App\Models\ContactMessageTemplate;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerWelcomeLaunchCopyTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_welcome_copy_no_longer_mentions_august_launch(): void
    {
        $body = (string) __('customers.message.default_body');

        $this->assertStringNotContainsString('14 August 2026', $body);
        $this->assertStringNotContainsString('14 اگست 2026', $body);
        $this->assertStringContainsString('Our store is open', $body);
        $this->assertStringContainsString('ہمارا سٹور کھل چکا ہے', $body);
    }

    public function test_migration_rewrites_stored_launch_date_welcome_copy(): void
    {
        $template = ContactMessageTemplate::query()
            ->forAudience(ContactMessageTemplate::AUDIENCE_CUSTOMER)
            ->first();

        if ($template === null) {
            $template = ContactMessageTemplate::query()->create([
                'key' => 'welcome-launch-test',
                'name_en' => 'Welcome',
                'name_ur' => 'خوش آمدید',
                'subject_en' => 'Welcome',
                'subject_ur' => 'خوش آمدید',
                'body_en' => 'placeholder',
                'body_ur' => 'placeholder',
                'audience' => ContactMessageTemplate::AUDIENCE_CUSTOMER,
                'is_active' => true,
                'sort_order' => 1,
            ]);
        }

        $template->update([
            'body_en' => 'Our official launch is scheduled for 14 August 2026. We look forward to serving you with quality products, transparent information, and a reliable shopping experience.',
            'body_ur' => 'ہمارا باقاعدہ آغاز 14 اگست 2026 کو متوقع ہے۔ ہم آپ کو معیاری مصنوعات، شفاف معلومات اور خریداری کا ایک قابلِ اعتماد تجربہ فراہم کرنے کے منتظر ہیں۔',
        ]);

        $settings = StoreSetting::query()->first() ?? StoreSetting::query()->create([]);
        $settings->update([
            'customer_inbox_message_body' => 'Hello. Our official launch is scheduled for 14 August 2026. We look forward to serving you with quality products, transparent information, and a reliable shopping experience.',
        ]);

        $migration = require database_path('migrations/2026_08_14_120000_replace_launch_date_in_customer_welcome_copy.php');
        $migration->up();

        $template->refresh();
        $settings->refresh();

        $this->assertStringNotContainsString('14 August 2026', (string) $template->body_en);
        $this->assertStringNotContainsString('14 اگست 2026', (string) $template->body_ur);
        $this->assertStringContainsString('Our store is open', (string) $template->body_en);
        $this->assertStringNotContainsString('14 August 2026', (string) $settings->customer_inbox_message_body);
    }
}
