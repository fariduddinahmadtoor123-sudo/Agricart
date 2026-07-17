<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_number', 32)->unique();
            $table->foreignId('expense_id')->constrained('expenses')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('payment_date');
            $table->time('payment_time')->nullable();
            $table->string('source_wallet', 16);
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('payment_date');
        });

        Schema::table('cash_book_transactions', function (Blueprint $table): void {
            $table->foreignId('expense_payment_id')->nullable()->after('expense_id')->constrained('expense_payments')->nullOnDelete();
        });

        Schema::table('bank_account_transactions', function (Blueprint $table): void {
            $table->foreignId('expense_payment_id')->nullable()->after('expense_id')->constrained('expense_payments')->nullOnDelete();
        });

        if (! Schema::hasColumn('expenses', 'information')) {
            Schema::table('expenses', function (Blueprint $table): void {
                $table->text('information')->nullable()->after('name_ur');
            });
        }

        if (Schema::hasColumn('expenses', 'amount')) {
            $this->backfillPaymentsFromLegacyExpenses();
            $this->mergeLegacyTextIntoInformation();
            $this->dropLegacyExpensePaymentColumns();
        }
    }

    public function down(): void
    {
        Schema::table('bank_account_transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('expense_payment_id');
        });

        Schema::table('cash_book_transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('expense_payment_id');
        });

        Schema::dropIfExists('expense_payments');

        if (Schema::hasColumn('expenses', 'information') && ! Schema::hasColumn('expenses', 'amount')) {
            Schema::table('expenses', function (Blueprint $table): void {
                $table->text('description_en')->nullable()->after('name_ur');
                $table->text('description_ur')->nullable()->after('description_en');
                $table->decimal('amount', 14, 2)->default(0)->after('description_ur');
                $table->date('expense_date')->nullable()->after('amount');
                $table->time('expense_time')->nullable()->after('expense_date');
                $table->string('source_wallet', 16)->nullable()->after('expense_time');
                $table->foreignId('bank_account_id')->nullable()->after('source_wallet')->constrained('bank_accounts')->nullOnDelete();
                $table->foreignId('payee_user_id')->nullable()->after('bank_account_id')->constrained('users')->nullOnDelete();
                $table->string('payee_name')->nullable()->after('payee_user_id');
                $table->text('notes')->nullable()->after('payee_name');
                $table->dropColumn('information');
            });
        }
    }

    private function backfillPaymentsFromLegacyExpenses(): void
    {
        $rows = DB::table('expenses')->orderBy('id')->get();
        $paymentSeq = 1;

        foreach ($rows as $row) {
            $amount = (float) ($row->amount ?? 0);

            if ($amount <= 0 || ! filled($row->source_wallet ?? null)) {
                continue;
            }

            do {
                $paymentNumber = 'EXPAY-'.str_pad((string) $paymentSeq, 5, '0', STR_PAD_LEFT);
                $paymentSeq++;
            } while (DB::table('expense_payments')->where('payment_number', $paymentNumber)->exists());

            $paymentId = DB::table('expense_payments')->insertGetId([
                'payment_number' => $paymentNumber,
                'expense_id' => $row->id,
                'amount' => $amount,
                'payment_date' => $row->expense_date ?? now()->toDateString(),
                'payment_time' => $row->expense_time,
                'source_wallet' => $row->source_wallet,
                'bank_account_id' => $row->bank_account_id,
                'notes' => $row->notes,
                'created_by' => $row->created_by,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);

            DB::table('cash_book_transactions')
                ->where('expense_id', $row->id)
                ->whereNull('expense_payment_id')
                ->update(['expense_payment_id' => $paymentId]);

            DB::table('bank_account_transactions')
                ->where('expense_id', $row->id)
                ->whereNull('expense_payment_id')
                ->update(['expense_payment_id' => $paymentId]);
        }
    }

    private function mergeLegacyTextIntoInformation(): void
    {
        $rows = DB::table('expenses')->orderBy('id')->get();

        foreach ($rows as $row) {
            $parts = array_filter([
                filled($row->description_en ?? null) ? (string) $row->description_en : null,
                filled($row->description_ur ?? null) ? (string) $row->description_ur : null,
                filled($row->notes ?? null) ? (string) $row->notes : null,
                filled($row->payee_name ?? null) ? 'Payee: '.$row->payee_name : null,
            ]);

            if ($parts === []) {
                continue;
            }

            DB::table('expenses')->where('id', $row->id)->update([
                'information' => implode("\n\n", $parts),
            ]);
        }
    }

    private function dropLegacyExpensePaymentColumns(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            if (Schema::hasColumn('expenses', 'bank_account_id')) {
                $table->dropConstrainedForeignId('bank_account_id');
            }

            if (Schema::hasColumn('expenses', 'payee_user_id')) {
                $table->dropConstrainedForeignId('payee_user_id');
            }
        });

        // SQLite cannot drop indexed columns without dropping the index first.
        try {
            Schema::table('expenses', function (Blueprint $table): void {
                $table->dropIndex('expenses_expense_date_index');
            });
        } catch (\Throwable) {
            // Index may already be absent (MySQL drop column cascade / fresh schema).
        }

        Schema::table('expenses', function (Blueprint $table): void {
            $drop = [];

            foreach ([
                'description_en',
                'description_ur',
                'amount',
                'expense_date',
                'expense_time',
                'source_wallet',
                'payee_name',
                'notes',
            ] as $column) {
                if (Schema::hasColumn('expenses', $column)) {
                    $drop[] = $column;
                }
            }

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};