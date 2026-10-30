<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fx_learn_account_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->string('description', 255)->nullable();
            $table->decimal('starting_balance', 14, 2);
            $table->decimal('phase1_pct', 6, 2)->default(8);
            $table->decimal('phase2_pct', 6, 2)->default(6);
            $table->decimal('phase3_pct', 6, 2)->default(3);
            $table->decimal('max_day_loss_pct', 6, 2)->default(5);
            $table->decimal('max_total_loss_pct', 6, 2)->default(10);
            $table->decimal('max_trade_loss_pct', 6, 2)->default(3);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();
        });

        $now = now();
        $rows = [
            ['name' => 'Starter $10k', 'slug' => 'starter-10k', 'description' => 'Entry challenge size', 'starting_balance' => 10000, 'sort_order' => 10],
            ['name' => 'Standard $25k', 'slug' => 'standard-25k', 'description' => 'Common retail challenge size', 'starting_balance' => 25000, 'sort_order' => 20],
            ['name' => 'Pro $50k', 'slug' => 'pro-50k', 'description' => 'Mid challenge size', 'starting_balance' => 50000, 'sort_order' => 30],
            ['name' => 'Challenge $100k', 'slug' => 'challenge-100k', 'description' => 'Full challenge size', 'starting_balance' => 100000, 'sort_order' => 40],
        ];

        foreach ($rows as $row) {
            DB::table('fx_learn_account_types')->insert([
                ...$row,
                'phase1_pct' => 8,
                'phase2_pct' => 6,
                'phase3_pct' => 3,
                'max_day_loss_pct' => 5,
                'max_total_loss_pct' => 10,
                'max_trade_loss_pct' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fx_learn_account_types');
    }
};
