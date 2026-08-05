<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->string('customer_inbox_message_subject', 200)->nullable()->after('meta_pixel_id');
            $table->text('customer_inbox_message_body')->nullable()->after('customer_inbox_message_subject');
        });

        $subject = 'Welcome to Agricart.pk · Agricart.pk میں خوش آمدید';
        $body = "Hello :name,\nWelcome to Agricart.pk! 🎉\nThank you for registering with us. We are delighted to have you as a member of the Agricart community.\nOur official launch is scheduled for 14 August 2026. We look forward to serving you with quality products, transparent information, and a reliable shopping experience.\nThank you for choosing Agricart.pk. We look forward to serving you.\nBest Regards,\nTeam Agricart.pk\n\nہیلو :name،\nAgricart.pk میں خوش آمدید! 🎉\nہمارے ساتھ رجسٹر ہونے کا شکریہ۔ ہمیں خوشی ہے کہ آپ Agricart کمیونٹی کا حصہ بنے ہیں۔\nہمارا باقاعدہ آغاز 14 اگست 2026 کو متوقع ہے۔ ہم آپ کو معیاری مصنوعات، شفاف معلومات اور خریداری کا ایک قابلِ اعتماد تجربہ فراہم کرنے کے منتظر ہیں۔\nAgricart.pk کا انتخاب کرنے کا شکریہ۔ ہم آپ کی خدمت کے لیے پرعزم ہیں۔\nنیک تمنائیں،\nٹیم Agricart.pk";

        if (Schema::hasTable('store_settings')) {
            DB::table('store_settings')->update([
                'customer_inbox_message_subject' => $subject,
                'customer_inbox_message_body' => $body,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'customer_inbox_message_subject',
                'customer_inbox_message_body',
            ]);
        });
    }
};
