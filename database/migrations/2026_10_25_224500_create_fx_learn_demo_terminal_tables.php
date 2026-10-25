<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fx_learn_demo_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('starting_balance', 14, 2)->default(100000);
            $table->decimal('balance', 14, 2)->default(100000);
            $table->decimal('phase1_pct', 6, 2)->default(8);
            $table->decimal('phase2_pct', 6, 2)->default(6);
            $table->decimal('phase3_pct', 6, 2)->default(3);
            $table->decimal('max_day_loss_pct', 6, 2)->default(5);
            $table->decimal('max_total_loss_pct', 6, 2)->default(10);
            $table->decimal('max_trade_loss_pct', 6, 2)->default(3);
            $table->unsignedTinyInteger('active_phase')->default(1);
            $table->decimal('day_anchor_equity', 14, 2)->nullable();
            $table->date('day_anchor_date')->nullable();
            $table->string('selected_symbol', 32)->default('eurusd');
            $table->timestamps();
        });

        Schema::create('fx_learn_demo_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 32);
            $table->string('label', 32);
            $table->string('category', 24);
            $table->string('side', 8);
            $table->decimal('lots', 10, 2);
            $table->decimal('entry_price', 16, 6);
            $table->decimal('exit_price', 16, 6)->nullable();
            $table->decimal('stop_loss', 16, 6)->nullable();
            $table->decimal('take_profit', 16, 6)->nullable();
            $table->decimal('contract_size', 14, 2)->default(1);
            $table->unsignedTinyInteger('digits')->default(5);
            $table->decimal('pnl', 14, 2)->nullable();
            $table->string('status', 16)->default('open');
            $table->string('close_reason', 32)->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'closed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_learn_demo_trades');
        Schema::dropIfExists('fx_learn_demo_accounts');
    }
};
