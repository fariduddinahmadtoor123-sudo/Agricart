<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->renameColumn('notification_sent_at', 'notification_dispatched_at');
        });
    }

    public function down(): void
    {
        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->renameColumn('notification_dispatched_at', 'notification_sent_at');
        });
    }
};
