<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools_home_expense_sheets', function (Blueprint $table) {
            $table->string('period_ym', 7)->nullable()->after('title');
            $table->index(['user_id', 'period_ym']);
        });

        $rows = DB::table('tools_home_expense_sheets')->select('id', 'created_at')->get();
        foreach ($rows as $row) {
            DB::table('tools_home_expense_sheets')
                ->where('id', $row->id)
                ->update([
                    'period_ym' => \Carbon\Carbon::parse($row->created_at)->format('Y-m'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('tools_home_expense_sheets', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'period_ym']);
            $table->dropColumn('period_ym');
        });
    }
};
