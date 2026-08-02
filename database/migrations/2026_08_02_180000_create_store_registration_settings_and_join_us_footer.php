<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_registration_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('staff_title_en')->nullable();
            $table->string('staff_title_ur')->nullable();
            $table->text('staff_body_en')->nullable();
            $table->text('staff_body_ur')->nullable();
            $table->boolean('staff_require_acceptance')->default(true);
            $table->string('supplier_title_en')->nullable();
            $table->string('supplier_title_ur')->nullable();
            $table->text('supplier_body_en')->nullable();
            $table->text('supplier_body_ur')->nullable();
            $table->boolean('supplier_require_acceptance')->default(false);
            $table->timestamps();
        });

        DB::table('store_registration_settings')->insert([
            'staff_title_en' => 'Join the Agricart team',
            'staff_title_ur' => 'ایگری کارٹ ٹیم میں شامل ہوں',
            'staff_body_en' => "If you want to work with Agricart, submit your application below.\n\nAn administrator will review your details before you can sign in. Keep your documents clear and your contact information up to date.",
            'staff_body_ur' => "اگر آپ ایگری کارٹ کے ساتھ کام کرنا چاہتے ہیں تو نیچے درخواست جمع کرائیں۔\n\nسائن اِن سے پہلے ایڈمن آپ کی تفصیلات چیک کرے گا۔ دستاویزات واضح رکھیں اور رابطہ کی معلومات درست لکھیں۔",
            'staff_require_acceptance' => true,
            'supplier_title_en' => 'Partner with Agricart',
            'supplier_title_ur' => 'ایگری کارٹ کے ساتھ شراکت',
            'supplier_body_en' => "If you want to connect with Agricart — whether you keep hardware stock or have any product you want to sell — we can help sell it through our channels.\n\nTell us about your business below. Our team will review your application before you appear in our supplier list.",
            'supplier_body_ur' => "اگر آپ ایگری کارٹ سے منسلک ہونا چاہتے ہیں — خواہ آپ ہارڈویئر رکھتے ہوں یا کوئی بھی پروڈکٹ بیچنا چاہتے ہوں — ہم اپنے چینلز سے فروخت میں مدد کر سکتے ہیں۔\n\nنیچے اپنے کاروبار کی تفصیل دیں۔ سپلائر فہرست میں آنے سے پہلے ہماری ٹیم جائزہ لے گی۔",
            'supplier_require_acceptance' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (Schema::hasTable('store_settings')) {
            DB::table('store_settings')
                ->where(function ($query): void {
                    $query->whereNull('footer_registration_title_en')
                        ->orWhere('footer_registration_title_en', '')
                        ->orWhere('footer_registration_title_en', 'Registration');
                })
                ->update(['footer_registration_title_en' => 'Join Us']);

            DB::table('store_settings')
                ->where(function ($query): void {
                    $query->whereNull('footer_registration_title_ur')
                        ->orWhere('footer_registration_title_ur', '')
                        ->orWhere('footer_registration_title_ur', 'رجسٹریشن');
                })
                ->update(['footer_registration_title_ur' => 'ہمارے ساتھ شامل ہوں']);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_registration_settings');
    }
};
