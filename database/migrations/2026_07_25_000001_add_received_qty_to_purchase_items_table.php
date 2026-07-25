<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table): void {
            $table->string('received_qty')->nullable()->after('purchase_qty');
        });

        // Preserve current on-hand stock for existing rows:
        // received / partial already credited purchase_qty; copy into received_qty.
        // pending stays null (no stock credit).
        $purchaseIds = DB::table('purchases')
            ->whereIn('delivery_status', ['received', 'partial'])
            ->pluck('id');

        if ($purchaseIds->isNotEmpty()) {
            DB::table('purchase_items')
                ->whereIn('purchase_id', $purchaseIds)
                ->update([
                    'received_qty' => DB::raw('purchase_qty'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table): void {
            $table->dropColumn('received_qty');
        });
    }
};
