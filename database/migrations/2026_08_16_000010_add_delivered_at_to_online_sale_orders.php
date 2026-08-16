<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->timestamp('delivered_at')->nullable()->after('shipping_customer_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->dropColumn('delivered_at');
        });
    }
};
