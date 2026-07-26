<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('store_pages', function (Blueprint $table) {
            $table->boolean('show_in_legal')->default(false)->after('show_in_footer');
        });

        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('footer_legal_title_en')->nullable()->after('footer_policies_title_ur');
            $table->string('footer_legal_title_ur')->nullable()->after('footer_legal_title_en');
        });

        DB::table('store_settings')->update([
            'footer_legal_title_en' => 'Legal',
            'footer_legal_title_ur' => 'قانونی',
        ]);

        // Split long Policies lists: privacy / cookie / seller → Legal column.
        $legalPatterns = [
            '%privacy%',
            '%cookie%',
            '%seller%',
            '%terms%',
            '%disclaimer%',
        ];

        $query = DB::table('store_pages')->where('show_in_footer', true);

        $query->where(function ($builder) use ($legalPatterns): void {
            foreach ($legalPatterns as $pattern) {
                $builder->orWhere('slug', 'like', $pattern)
                    ->orWhere('title_en', 'like', $pattern);
            }
        });

        $query->update([
            'show_in_footer' => false,
            'show_in_legal' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('store_pages')
            ->where('show_in_legal', true)
            ->update([
                'show_in_footer' => true,
                'show_in_legal' => false,
            ]);

        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn(['footer_legal_title_en', 'footer_legal_title_ur']);
        });

        Schema::table('store_pages', function (Blueprint $table) {
            $table->dropColumn('show_in_legal');
        });
    }
};
