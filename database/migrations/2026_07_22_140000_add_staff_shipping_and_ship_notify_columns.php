<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_sale_order_staff_assignments', function (Blueprint $table): void {
            $table->string('carrier_name')->nullable()->after('released_at');
            $table->string('tracking_number')->nullable()->after('carrier_name');
            $table->string('shipping_reference')->nullable()->after('tracking_number');
            $table->string('shipping_proof_path')->nullable()->after('shipping_reference');
            $table->timestamp('shipped_at')->nullable()->after('shipping_proof_path');
            $table->index(['released_at', 'shipped_at'], 'ososta_released_shipped_idx');
        });

        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->timestamp('shipping_customer_notified_at')->nullable()->after('notification_dispatched_at');
        });
    }

    public function down(): void
    {
        Schema::table('online_sale_order_staff_assignments', function (Blueprint $table): void {
            $table->dropIndex('ososta_released_shipped_idx');
            $table->dropColumn([
                'carrier_name',
                'tracking_number',
                'shipping_reference',
                'shipping_proof_path',
                'shipped_at',
            ]);
        });

        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->dropColumn('shipping_customer_notified_at');
        });
    }
};
