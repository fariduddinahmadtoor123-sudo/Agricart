<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->string('customer_payment_reference', 128)->nullable()->after('customer_payment_note');
            $table->string('customer_bank_receipt_path')->nullable()->after('customer_payment_reference');
            $table->string('customer_payment_proof_status', 32)->default('none')->after('customer_bank_receipt_path');
            $table->text('payment_proof_rejection_note')->nullable()->after('customer_payment_proof_status');
            $table->timestamp('payment_proof_reviewed_at')->nullable()->after('payment_proof_rejection_note');
            $table->foreignId('payment_proof_reviewed_by_id')->nullable()->after('payment_proof_reviewed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('online_sale_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('payment_proof_reviewed_by_id');
            $table->dropColumn([
                'customer_payment_reference',
                'customer_bank_receipt_path',
                'customer_payment_proof_status',
                'payment_proof_rejection_note',
                'payment_proof_reviewed_at',
            ]);
        });
    }
};
