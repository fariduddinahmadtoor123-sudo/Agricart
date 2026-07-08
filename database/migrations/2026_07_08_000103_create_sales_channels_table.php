<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_channels', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_en');
            $table->string('name_ur')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->string('offline_message_en')->nullable();
            $table->string('offline_message_ur')->nullable();
            $table->string('banner_message_en')->nullable();
            $table->string('banner_message_ur')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('sales_channels')->insert([
            'slug' => 'online_store',
            'name_en' => 'Online Store',
            'name_ur' => 'آن لائن اسٹور',
            'is_enabled' => true,
            'offline_message_en' => 'Wait for some time',
            'offline_message_ur' => 'تھوڑی دیر انتظار کریں',
            'banner_message_en' => 'Online ordering is temporarily unavailable. You can still browse our catalog.',
            'banner_message_ur' => 'آن لائن آرڈرنگ عارضی بند ہے۔ آپ مصنوعات دیکھ سکتے ہیں۔',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_channels');
    }
};
