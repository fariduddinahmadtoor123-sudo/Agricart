<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_errors', function (Blueprint $table): void {
            $table->id();
            $table->ulid('reference')->unique();
            $table->string('fingerprint', 64)->unique();
            $table->string('source', 30)->default('exception');
            $table->string('severity', 20)->default('error');
            $table->string('status', 20)->default('open');
            $table->string('exception_class', 500)->nullable();
            $table->text('message');
            $table->text('file')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->longText('trace')->nullable();
            $table->string('request_method', 12)->nullable();
            $table->text('request_path')->nullable();
            $table->string('route_name', 255)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('environment', 50)->nullable();
            $table->string('release', 100)->nullable();
            $table->json('context')->nullable();
            $table->unsignedBigInteger('occurrences')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'last_seen_at']);
            $table->index(['severity', 'last_seen_at']);
            $table->index(['source', 'last_seen_at']);
        });

        $sortOrder = ((int) DB::table('permissions')->max('sort_order')) + 1;

        foreach ([
            [
                'key' => 'settings.system-errors.view',
                'label_en' => 'View sanitized system errors',
                'label_ur' => 'محفوظ سسٹم ایررز دیکھیں',
            ],
            [
                'key' => 'settings.system-errors.manage',
                'label_en' => 'Resolve and manage system errors',
                'label_ur' => 'سسٹم ایررز حل اور مینج کریں',
            ],
        ] as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $permission['key']],
                [
                    'group_key' => 'settings',
                    'label_en' => $permission['label_en'],
                    'label_ur' => $permission['label_ur'],
                    'sort_order' => $sortOrder++,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_errors');
        DB::table('permissions')
            ->whereIn('key', ['settings.system-errors.view', 'settings.system-errors.manage'])
            ->delete();
    }
};
