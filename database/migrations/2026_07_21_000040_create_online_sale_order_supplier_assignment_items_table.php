<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_sale_order_supplier_assignment_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('online_sale_order_supplier_assignment_id')
                ->constrained('online_sale_order_supplier_assignments', 'id', 'ososai_assignment_fk')
                ->cascadeOnDelete();
            $table->foreignId('online_sale_order_item_id')
                ->constrained('online_sale_order_items', 'id', 'ososai_item_fk')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['online_sale_order_item_id'], 'ososai_item_unq');
            $table->unique(
                ['online_sale_order_supplier_assignment_id', 'online_sale_order_item_id'],
                'ososai_assignment_item_unq',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_sale_order_supplier_assignment_items');
    }
};
