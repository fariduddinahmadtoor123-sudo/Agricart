<?php

use App\Enums\AiLogType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ai_logs')
            ->where('type', AiLogType::UserEnrichment->value)
            ->update([
                'prompt' => null,
                'response' => null,
            ]);
    }

    public function down(): void
    {
        // Redacted personal data cannot and should not be restored.
    }
};
