<?php

use App\Models\DeliveryMethod;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_delivery_method', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('delivery_method_id')->constrained('delivery_methods')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'delivery_method_id']);
        });

        $activeMethodIds = DeliveryMethod::query()
            ->where('status', DeliveryMethod::STATUS_ACTIVE)
            ->pluck('id')
            ->all();

        if ($activeMethodIds === []) {
            return;
        }

        $now = now();

        Product::query()->orderBy('id')->chunkById(200, function ($products) use ($activeMethodIds, $now): void {
            $rows = [];

            foreach ($products as $product) {
                foreach ($activeMethodIds as $methodId) {
                    $rows[] = [
                        'product_id' => $product->id,
                        'delivery_method_id' => $methodId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if ($rows !== []) {
                DB::table('product_delivery_method')->insert($rows);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_delivery_method');
    }
};
