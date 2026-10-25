<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools_home_expense_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->string('status', 32)->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('tools_home_expense_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_id')->constrained('tools_home_expense_sheets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->decimal('expected_amount', 12, 2)->default(0);
            $table->date('expense_date')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['sheet_id', 'is_paid']);
            $table->index(['user_id', 'sheet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tools_home_expense_items');
        Schema::dropIfExists('tools_home_expense_sheets');
    }
};
