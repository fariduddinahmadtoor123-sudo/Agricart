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
            DB::statement('ALTER TABLE categories MODIFY name_en TEXT NOT NULL');
            DB::statement('ALTER TABLE categories MODIFY name_ur TEXT NOT NULL');
            DB::statement('ALTER TABLE categories MODIFY slug VARCHAR(500) NOT NULL');
            DB::statement('ALTER TABLE categories MODIFY code TEXT NULL');
            DB::statement('ALTER TABLE categories MODIFY seo_title_en TEXT NULL');
            DB::statement('ALTER TABLE categories MODIFY seo_title_ur TEXT NULL');
            DB::statement('ALTER TABLE categories MODIFY seo_keywords_en TEXT NULL');
            DB::statement('ALTER TABLE categories MODIFY seo_keywords_ur TEXT NULL');
            DB::statement('ALTER TABLE categories MODIFY seo_focus_keyword_en TEXT NULL');
            DB::statement('ALTER TABLE categories MODIFY seo_focus_keyword_ur TEXT NULL');
            DB::statement('ALTER TABLE categories MODIFY hs_code VARCHAR(128) NULL');

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE categories ALTER COLUMN name_en TYPE TEXT');
            DB::statement('ALTER TABLE categories ALTER COLUMN name_ur TYPE TEXT');
            DB::statement('ALTER TABLE categories ALTER COLUMN slug TYPE VARCHAR(500)');
            DB::statement('ALTER TABLE categories ALTER COLUMN code TYPE TEXT');
            DB::statement('ALTER TABLE categories ALTER COLUMN seo_title_en TYPE TEXT');
            DB::statement('ALTER TABLE categories ALTER COLUMN seo_title_ur TYPE TEXT');
            DB::statement('ALTER TABLE categories ALTER COLUMN seo_keywords_en TYPE TEXT');
            DB::statement('ALTER TABLE categories ALTER COLUMN seo_keywords_ur TYPE TEXT');
            DB::statement('ALTER TABLE categories ALTER COLUMN seo_focus_keyword_en TYPE TEXT');
            DB::statement('ALTER TABLE categories ALTER COLUMN seo_focus_keyword_ur TYPE TEXT');
            DB::statement('ALTER TABLE categories ALTER COLUMN hs_code TYPE VARCHAR(128)');

            return;
        }

        // SQLite does not enforce VARCHAR length limits; application validation was widened separately.
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE categories MODIFY name_en VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE categories MODIFY name_ur VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE categories MODIFY slug VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE categories MODIFY code VARCHAR(255) NULL');
            DB::statement('ALTER TABLE categories MODIFY seo_title_en VARCHAR(255) NULL');
            DB::statement('ALTER TABLE categories MODIFY seo_title_ur VARCHAR(255) NULL');
            DB::statement('ALTER TABLE categories MODIFY seo_keywords_en VARCHAR(255) NULL');
            DB::statement('ALTER TABLE categories MODIFY seo_keywords_ur VARCHAR(255) NULL');
            DB::statement('ALTER TABLE categories MODIFY seo_focus_keyword_en VARCHAR(255) NULL');
            DB::statement('ALTER TABLE categories MODIFY seo_focus_keyword_ur VARCHAR(255) NULL');
            DB::statement('ALTER TABLE categories MODIFY hs_code VARCHAR(255) NULL');
        }
    }
};
