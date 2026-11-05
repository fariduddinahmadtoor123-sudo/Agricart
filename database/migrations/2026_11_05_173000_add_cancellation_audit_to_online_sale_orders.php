<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->timestamp('cancelled_at')->nullable()->after('admin_notes');
            $table->foreignId('cancelled_by_id')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable()->after('cancelled_by_id');
            $table->string('cancellation_source', 32)->nullable()->after('cancellation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cancelled_by_id');
            $table->dropColumn([
                'cancelled_at',
                'cancellation_reason',
                'cancellation_source',
            ]);
        });
    }
};
