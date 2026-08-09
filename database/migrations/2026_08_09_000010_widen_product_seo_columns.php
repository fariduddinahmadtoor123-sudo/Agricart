<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE products MODIFY seo_title_en TEXT NULL');
            DB::statement('ALTER TABLE products MODIFY seo_title_ur TEXT NULL');
            DB::statement('ALTER TABLE products MODIFY seo_keywords_en TEXT NULL');
            DB::statement('ALTER TABLE products MODIFY seo_keywords_ur TEXT NULL');
            DB::statement('ALTER TABLE products MODIFY seo_focus_keyword_en TEXT NULL');
            DB::statement('ALTER TABLE products MODIFY seo_focus_keyword_ur TEXT NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE products ALTER COLUMN seo_title_en TYPE TEXT');
            DB::statement('ALTER TABLE products ALTER COLUMN seo_title_ur TYPE TEXT');
            DB::statement('ALTER TABLE products ALTER COLUMN seo_keywords_en TYPE TEXT');
            DB::statement('ALTER TABLE products ALTER COLUMN seo_keywords_ur TYPE TEXT');
            DB::statement('ALTER TABLE products ALTER COLUMN seo_focus_keyword_en TYPE TEXT');
            DB::statement('ALTER TABLE products ALTER COLUMN seo_focus_keyword_ur TYPE TEXT');

            return;
        }

        // SQLite does not enforce VARCHAR length limits; application validation guards length.
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE products MODIFY seo_title_en VARCHAR(255) NULL');
            DB::statement('ALTER TABLE products MODIFY seo_title_ur VARCHAR(255) NULL');
            DB::statement('ALTER TABLE products MODIFY seo_keywords_en VARCHAR(255) NULL');
            DB::statement('ALTER TABLE products MODIFY seo_keywords_ur VARCHAR(255) NULL');
            DB::statement('ALTER TABLE products MODIFY seo_focus_keyword_en VARCHAR(255) NULL');
            DB::statement('ALTER TABLE products MODIFY seo_focus_keyword_ur VARCHAR(255) NULL');
        }
    }
};
