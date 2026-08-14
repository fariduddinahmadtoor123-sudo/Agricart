<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $replacements = [
            'Our official launch is scheduled for 14 August 2026. We look forward to serving you with quality products, transparent information, and a reliable shopping experience.' => 'Our store is open. Product prices are being added gradually by our small team — thank you for your patience. We look forward to serving you with quality products, transparent information, and a reliable shopping experience.',
            'ہمارا باقاعدہ آغاز 14 اگست 2026 کو متوقع ہے۔ ہم آپ کو معیاری مصنوعات، شفاف معلومات اور خریداری کا ایک قابلِ اعتماد تجربہ فراہم کرنے کے منتظر ہیں۔' => 'ہمارا سٹور کھل چکا ہے۔ مصنوعات کی قیمتیں ہماری چھوٹی ٹیم آہستہ آہستہ لگا رہی ہے — آپ کے صبر کا شکریہ۔ ہم آپ کو معیاری مصنوعات، شفاف معلومات اور خریداری کا ایک قابلِ اعتماد تجربہ فراہم کرنے کے منتظر ہیں۔',
        ];

        if (Schema::hasTable('contact_message_templates')) {
            $rows = DB::table('contact_message_templates')->get(['id', 'body_en', 'body_ur']);

            foreach ($rows as $row) {
                $bodyEn = $this->replaceLaunchCopy((string) ($row->body_en ?? ''), $replacements);
                $bodyUr = $this->replaceLaunchCopy((string) ($row->body_ur ?? ''), $replacements);

                if ($bodyEn === (string) ($row->body_en ?? '') && $bodyUr === (string) ($row->body_ur ?? '')) {
                    continue;
                }

                DB::table('contact_message_templates')->where('id', $row->id)->update([
                    'body_en' => $bodyEn !== '' ? $bodyEn : $row->body_en,
                    'body_ur' => $bodyUr !== '' ? $bodyUr : $row->body_ur,
                    'updated_at' => now(),
                ]);
            }
        }

        if (Schema::hasTable('store_settings') && Schema::hasColumn('store_settings', 'customer_inbox_message_body')) {
            $settings = DB::table('store_settings')->get(['id', 'customer_inbox_message_body']);

            foreach ($settings as $row) {
                $body = $this->replaceLaunchCopy((string) ($row->customer_inbox_message_body ?? ''), $replacements);

                if ($body === (string) ($row->customer_inbox_message_body ?? '')) {
                    continue;
                }

                DB::table('store_settings')->where('id', $row->id)->update([
                    'customer_inbox_message_body' => $body,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Launch-date copy should not be restored.
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function replaceLaunchCopy(string $body, array $replacements): string
    {
        foreach ($replacements as $from => $to) {
            $body = str_replace($from, $to, $body);
        }

        return $body;
    }
};
