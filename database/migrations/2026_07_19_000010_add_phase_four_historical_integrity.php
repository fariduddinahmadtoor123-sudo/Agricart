<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->decimal('unit_cost_snapshot', 14, 4)->nullable()->after('unit_price');
            $table->decimal('cogs_snapshot', 14, 4)->nullable()->after('unit_cost_snapshot');
            $table->string('costing_policy', 64)->nullable()->after('cogs_snapshot');
            $table->index('unit_cost_snapshot');
        });

        Schema::table('sale_return_items', function (Blueprint $table): void {
            $table->decimal('unit_cost_snapshot', 14, 4)->nullable()->after('unit_price');
            $table->decimal('cogs_reversal_snapshot', 14, 4)->nullable()->after('unit_cost_snapshot');
        });

        Schema::create('customer_phones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('phone', 30);
            $table->string('canonical_phone', 32)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['customer_id', 'sort_order']);
        });

        // Lowest customer id wins any pre-existing collision. Legacy JSON remains
        // untouched so conflicts can be reviewed instead of silently rewritten.
        DB::table('customers')->orderBy('id')->chunkById(200, function ($customers): void {
            foreach ($customers as $customer) {
                $contacts = json_decode((string) ($customer->contacts ?? '[]'), true);
                $phones = [];

                if (filled($customer->phone)) {
                    $phones[] = (string) $customer->phone;
                }

                foreach (is_array($contacts) ? $contacts : [] as $contact) {
                    if (is_array($contact) && filled($contact['mobile_number'] ?? null)) {
                        $phones[] = (string) $contact['mobile_number'];
                    }
                }

                $seen = [];
                foreach ($phones as $sort => $phone) {
                    $canonical = $this->canonicalPhone($phone);
                    if ($canonical === '' || isset($seen[$canonical])) {
                        continue;
                    }

                    $seen[$canonical] = true;
                    DB::table('customer_phones')->insertOrIgnore([
                        'customer_id' => $customer->id,
                        'phone' => trim($phone),
                        'canonical_phone' => $canonical,
                        'sort_order' => $sort,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_phones');

        Schema::table('sale_return_items', function (Blueprint $table): void {
            $table->dropColumn(['unit_cost_snapshot', 'cogs_reversal_snapshot']);
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            $table->dropIndex(['unit_cost_snapshot']);
            $table->dropColumn(['unit_cost_snapshot', 'cogs_snapshot', 'costing_policy']);
        });
    }

    private function canonicalPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) > 10 && str_starts_with($digits, '92')) {
            $digits = substr($digits, -10);
        } elseif (strlen($digits) > 10) {
            $digits = substr($digits, -10);
        }

        return $digits;
    }
};
