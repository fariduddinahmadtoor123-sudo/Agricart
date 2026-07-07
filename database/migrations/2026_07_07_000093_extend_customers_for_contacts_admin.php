<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('customer_channel', 16)->default('online')->after('customer_number');
            $table->string('display_name')->nullable()->after('last_name');
            $table->string('name_en')->nullable()->after('display_name');
            $table->string('name_ur')->nullable()->after('name_en');
            $table->text('address_en')->nullable()->after('address_line2');
            $table->text('address_ur')->nullable()->after('address_en');
            $table->string('address_quick')->nullable()->after('address_ur');
            $table->string('city_key')->nullable()->after('city');
            $table->string('city_en')->nullable()->after('city_key');
            $table->string('city_ur')->nullable()->after('city_en');
            $table->decimal('opening_balance', 15, 2)->default(0)->after('country');
            $table->decimal('credit_limit', 15, 2)->nullable()->after('opening_balance');
            $table->json('contacts')->nullable()->after('credit_limit');
            $table->string('profile_photo_path')->nullable()->after('contacts');
            $table->string('ai_status')->default('none')->after('profile_photo_path');

            $table->index('customer_channel');
            $table->index('name_en');
            $table->index('city_en');
        });

        DB::table('customers')->orderBy('id')->lazy()->each(function (object $row): void {
            $displayName = trim(((string) $row->first_name) . ' ' . ((string) $row->last_name));

            DB::table('customers')->where('id', $row->id)->update([
                'customer_channel' => 'online',
                'display_name' => $displayName !== '' ? $displayName : null,
                'name_en' => $displayName !== '' ? $displayName : null,
                'contacts' => filled($row->phone)
                    ? json_encode([['mobile_number' => (string) $row->phone]], JSON_UNESCAPED_UNICODE)
                    : null,
                'city_en' => filled($row->city) ? (string) $row->city : null,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['customer_channel']);
            $table->dropIndex(['name_en']);
            $table->dropIndex(['city_en']);

            $table->dropColumn([
                'customer_channel',
                'display_name',
                'name_en',
                'name_ur',
                'address_en',
                'address_ur',
                'address_quick',
                'city_key',
                'city_en',
                'city_ur',
                'opening_balance',
                'credit_limit',
                'contacts',
                'profile_photo_path',
                'ai_status',
            ]);
        });
    }
};
