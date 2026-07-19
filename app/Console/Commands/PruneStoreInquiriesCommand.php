<?php

namespace App\Console\Commands;

use App\Models\StoreInquiry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PruneStoreInquiriesCommand extends Command
{
    protected $signature = 'store-inquiries:prune
        {--closed-days= : Delete closed inquiries after this many days}
        {--metadata-days= : Remove IP/user-agent metadata after this many days}';

    protected $description = 'Apply configured retention to closed inquiries and transient request metadata';

    public function handle(): int
    {
        if (! Schema::hasTable('store_inquiries')) {
            return self::SUCCESS;
        }

        $closedDays = max(30, (int) ($this->option('closed-days') ?: config('retention.store_inquiry_closed_days')));
        $metadataDays = max(1, (int) ($this->option('metadata-days') ?: config('retention.store_inquiry_metadata_days')));

        $metadata = StoreInquiry::query()
            ->where('created_at', '<', now()->subDays($metadataDays))
            ->where(fn ($query) => $query->whereNotNull('ip_hash')->orWhereNotNull('user_agent'))
            ->update(['ip_hash' => null, 'user_agent' => null]);

        $deleted = StoreInquiry::query()
            ->where('status', StoreInquiry::STATUS_CLOSED)
            ->whereNotNull('closed_at')
            ->where('closed_at', '<', now()->subDays($closedDays))
            ->delete();

        $this->components->info("Redacted metadata on {$metadata} inquiry record(s); deleted {$deleted} expired closed inquiry record(s).");

        return self::SUCCESS;
    }
}
