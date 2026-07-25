<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_method_rules', function (Blueprint $table): void {
            $table->decimal('shipping_fee', 12, 2)->default(0)->after('shipping_charge_mode');
        });

        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->decimal('shipping_amount', 14, 2)->default(0)->after('subtotal');
        });
    }

    public function down(): void
    {
        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->dropColumn('shipping_amount');
        });

        Schema::table('delivery_method_rules', function (Blueprint $table): void {
            $table->dropColumn('shipping_fee');
        });
    }
};
