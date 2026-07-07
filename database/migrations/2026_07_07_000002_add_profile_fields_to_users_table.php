<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('user_number')->nullable()->unique()->after('id');
            $table->foreignId('role_id')->nullable()->after('name')->constrained()->nullOnDelete();
            $table->string('status')->default('active')->after('role_id');
            $table->text('address')->nullable()->after('email');
            $table->json('phone_numbers')->nullable()->after('address');
            $table->json('bank_accounts')->nullable()->after('phone_numbers');
            $table->string('profile_photo_path')->nullable()->after('bank_accounts');
            $table->string('cnic_front_path')->nullable()->after('profile_photo_path');
            $table->string('cnic_back_path')->nullable()->after('cnic_front_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn([
                'user_number',
                'status',
                'address',
                'phone_numbers',
                'bank_accounts',
                'profile_photo_path',
                'cnic_front_path',
                'cnic_back_path',
            ]);
        });
    }
};
