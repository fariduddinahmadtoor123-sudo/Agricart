<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_message_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('name', 120);
            $table->string('subject', 200);
            $table->text('body');
            $table->string('audience', 32)->default('customer')->index();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $subject = null;
        $body = null;

        if (Schema::hasTable('store_settings') && Schema::hasColumn('store_settings', 'customer_inbox_message_subject')) {
            $row = DB::table('store_settings')->orderBy('id')->first();
            if ($row !== null) {
                $subject = filled($row->customer_inbox_message_subject ?? null)
                    ? (string) $row->customer_inbox_message_subject
                    : null;
                $body = filled($row->customer_inbox_message_body ?? null)
                    ? (string) $row->customer_inbox_message_body
                    : null;
            }
        }

        $subject ??= 'Welcome to Agricart.pk · Agricart.pk میں خوش آمدید';
        $body ??= "Hello :name,\nWelcome to Agricart.pk! 🎉\nThank you for registering with us. We are delighted to have you as a member of the Agricart community.\nOur official launch is scheduled for 14 August 2026. We look forward to serving you with quality products, transparent information, and a reliable shopping experience.\nThank you for choosing Agricart.pk. We look forward to serving you.\nBest Regards,\nTeam Agricart.pk\n\nہیلو :name،\nAgricart.pk میں خوش آمدید! 🎉\nہمارے ساتھ رجسٹر ہونے کا شکریہ۔ ہمیں خوشی ہے کہ آپ Agricart کمیونٹی کا حصہ بنے ہیں۔\nہمارا باقاعدہ آغاز 14 اگست 2026 کو متوقع ہے۔ ہم آپ کو معیاری مصنوعات، شفاف معلومات اور خریداری کا ایک قابلِ اعتماد تجربہ فراہم کرنے کے منتظر ہیں۔\nAgricart.pk کا انتخاب کرنے کا شکریہ۔ ہم آپ کی خدمت کے لیے پرعزم ہیں۔\nنیک تمنائیں،\nٹیم Agricart.pk";

        DB::table('contact_message_templates')->insert([
            'key' => 'welcome',
            'name' => 'Welcome',
            'subject' => $subject,
            'body' => $body,
            'audience' => 'customer',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_message_templates');
    }
};
