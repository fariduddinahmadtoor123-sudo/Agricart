<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_sale_order_supplier_assignments', function (Blueprint $table): void {
            $table->timestamp('acknowledged_at')->nullable()->after('sent_at');
            $table->timestamp('shipped_at')->nullable()->after('acknowledged_at');
            $table->string('carrier_name')->nullable()->after('shipped_at');
            $table->string('tracking_number')->nullable()->after('carrier_name');
            $table->string('shipping_reference')->nullable()->after('tracking_number');
            $table->string('shipping_proof_path')->nullable()->after('shipping_reference');
            $table->text('supplier_response_notes')->nullable()->after('shipping_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('online_sale_order_supplier_assignments', function (Blueprint $table): void {
            $table->dropColumn([
                'acknowledged_at',
                'shipped_at',
                'carrier_name',
                'tracking_number',
                'shipping_reference',
                'shipping_proof_path',
                'supplier_response_notes',
            ]);
        });
    }
};
