<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_counters', function (Blueprint $table): void {
            $table->string('key', 191)->primary();
            $table->unsignedBigInteger('value')->default(0);
            $table->timestamps();
        });

        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->string('checkout_token', 64)->nullable()->after('order_number');
            $table->timestamp('notification_sent_at')->nullable()->after('converted_by_id');
            $table->unique(['customer_id', 'checkout_token'], 'online_orders_customer_checkout_token_unique');
            $table->unique('sale_id', 'online_orders_sale_id_unique');
        });

        Schema::table('reorder_queue_items', function (Blueprint $table): void {
            $table->string('active_key', 64)->nullable()->after('status');
            $table->unique('active_key', 'reorder_queue_items_active_key_unique');
        });

        DB::table('reorder_queue_items')
            ->where('status', 'pending')
            ->orderBy('id')
            ->get()
            ->groupBy('product_id')
            ->each(function ($rows): void {
                $keep = $rows->first();

                DB::table('reorder_queue_items')->where('id', $keep->id)->update([
                    'active_key' => 'product:'.$keep->product_id,
                ]);

                $duplicateIds = $rows->skip(1)->pluck('id');

                if ($duplicateIds->isNotEmpty()) {
                    DB::table('reorder_queue_items')->whereIn('id', $duplicateIds)->update([
                        'status' => 'fulfilled',
                        'fulfilled_at' => now(),
                        'active_key' => null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('reorder_queue_items', function (Blueprint $table): void {
            $table->dropUnique('reorder_queue_items_active_key_unique');
            $table->dropColumn('active_key');
        });

        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->dropUnique('online_orders_customer_checkout_token_unique');
            $table->dropUnique('online_orders_sale_id_unique');
            $table->dropColumn(['checkout_token', 'notification_sent_at']);
        });

        Schema::dropIfExists('document_counters');
    }
};
