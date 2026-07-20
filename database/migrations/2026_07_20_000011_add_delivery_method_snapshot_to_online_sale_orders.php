<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->foreignId('delivery_method_id')->nullable()->after('ship_city')->constrained()->nullOnDelete();
            $table->string('delivery_method_name_en')->nullable()->after('delivery_method_id');
            $table->string('delivery_method_name_ur')->nullable()->after('delivery_method_name_en');
            $table->string('shipping_charge_mode')->nullable()->after('delivery_method_name_ur');
            $table->decimal('cod_min_advance_percent', 5, 2)->nullable()->after('shipping_charge_mode');
            $table->boolean('full_advance_required')->nullable()->after('cod_min_advance_percent');
            $table->text('delivery_customer_notice_en')->nullable()->after('full_advance_required');
            $table->text('delivery_customer_notice_ur')->nullable()->after('delivery_customer_notice_en');
        });
    }

    public function down(): void
    {
        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('delivery_method_id');
            $table->dropColumn([
                'delivery_method_name_en',
                'delivery_method_name_ur',
                'shipping_charge_mode',
                'cod_min_advance_percent',
                'full_advance_required',
                'delivery_customer_notice_en',
                'delivery_customer_notice_ur',
            ]);
        });
    }
};
