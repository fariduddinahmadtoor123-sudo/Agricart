<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_leave_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('monthly_paid_leaves')->default(2);
            $table->timestamps();
        });

        Schema::create('staff_public_holidays', function (Blueprint $table): void {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('title_en');
            $table->string('title_ur')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('staff_attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('status');
            $table->string('leave_type')->nullable();
            $table->boolean('is_paid_day')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'attendance_date']);
            $table->index('attendance_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendance_records');
        Schema::dropIfExists('staff_public_holidays');
        Schema::dropIfExists('staff_leave_settings');
    }
};
