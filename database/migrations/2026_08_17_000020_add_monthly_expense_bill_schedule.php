<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->unsignedTinyInteger('issue_day')->nullable()->after('information');
            $table->unsignedTinyInteger('due_day')->nullable()->after('issue_day');
        });

        if (Schema::hasColumn('expenses', 'issue_date')) {
            foreach (DB::table('expenses')->orderBy('id')->get() as $row) {
                $issueDay = filled($row->issue_date ?? null) ? Carbon::parse($row->issue_date)->day : null;
                $dueDay = filled($row->due_date ?? null) ? Carbon::parse($row->due_date)->day : null;

                DB::table('expenses')->where('id', $row->id)->update([
                    'issue_day' => $issueDay,
                    'due_day' => $dueDay,
                ]);
            }

            Schema::table('expenses', function (Blueprint $table): void {
                try {
                    $table->dropIndex('expenses_issue_date_index');
                } catch (\Throwable) {
                }

                try {
                    $table->dropIndex('expenses_due_date_index');
                } catch (\Throwable) {
                }

                $table->dropColumn(['issue_date', 'due_date']);
            });
        }

        Schema::create('expense_bill_cycles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('expense_id')->constrained('expenses')->cascadeOnDelete();
            $table->unsignedSmallInteger('cycle_year');
            $table->unsignedTinyInteger('cycle_month');
            $table->date('issue_on');
            $table->date('due_on')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('completed_via', 16)->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['expense_id', 'cycle_year', 'cycle_month']);
            $table->index(['cycle_year', 'cycle_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_bill_cycles');

        Schema::table('expenses', function (Blueprint $table): void {
            $table->date('issue_date')->nullable()->after('information');
            $table->date('due_date')->nullable()->after('issue_date');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropColumn(['issue_day', 'due_day']);
        });
    }
};
