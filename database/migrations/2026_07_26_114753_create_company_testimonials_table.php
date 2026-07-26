<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_testimonials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('display_name', 120);
            $table->string('title', 160)->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->string('status', 20)->default('pending');
            $table->boolean('is_featured')->default(false);
            $table->string('source', 20)->default('customer');
            $table->text('admin_reply')->nullable();
            $table->foreignId('admin_replied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('admin_replied_at')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->unique('customer_id', 'company_testimonials_customer_uq');
            $table->index(['status', 'is_featured', 'created_at'], 'company_testimonials_public_idx');
            $table->index(['status', 'created_at'], 'company_testimonials_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_testimonials');
    }
};
