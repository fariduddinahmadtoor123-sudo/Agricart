<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Cache::forget('store.hero_slides.active');
        Cache::forget('store.hero_slides.active_ids');

        // Legacy interval stored seconds (3–30); new format uses steps 1–5 (0.5s–2.5s).
        DB::table('store_settings')->where('hero_slider_interval', '>=', 6)->update([
            'hero_slider_interval' => 5,
        ]);

        DB::table('store_settings')->where('hero_slider_interval', 5)->update([
            'hero_slider_interval' => 4,
        ]);

        DB::table('store_settings')->where('hero_slider_interval', '<', 1)->update([
            'hero_slider_interval' => 4,
        ]);
    }

    public function down(): void
    {
        // no-op — previous values cannot be restored reliably
    }
};
