<?php

namespace App\Console\Commands;

use App\Models\OnlineSaleOrder;
use App\Services\Security\PrivateDocumentStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillPrivatePaymentProofsCommand extends Command
{
    protected $signature = 'online-orders:backfill-private-payment-proofs
                            {--dry-run : Report changes without writing or deleting files}
                            {--delete-public : Delete a legacy public original only after verifying the private copy}';

    protected $description = 'Copy legacy public customer payment proofs to private storage and optionally remove verified originals';

    public function handle(PrivateDocumentStorage $documents): int
    {
        $copied = 0;
        $verified = 0;
        $deleted = 0;
        $missing = 0;
        $failed = 0;

        OnlineSaleOrder::query()
            ->whereNotNull('customer_bank_receipt_path')
            ->select(['id', 'customer_bank_receipt_path'])
            ->chunkById(100, function ($orders) use ($documents, &$copied, &$verified, &$deleted, &$missing, &$failed): void {
                foreach ($orders as $order) {
                    $path = (string) $order->customer_bank_receipt_path;

                    if (! str_starts_with($path, 'online-orders/customer-receipts/')) {
                        continue;
                    }

                    $private = Storage::disk('local');
                    $public = Storage::disk('public');
                    $privateExists = $private->exists($path);
                    $publicExists = $public->exists($path);

                    if (! $publicExists) {
                        if (! $privateExists) {
                            $missing++;
                            $this->warn("Missing payment proof for order {$order->id}: {$path}");
                        }

                        continue;
                    }

                    if (! $privateExists) {
                        if ($this->option('dry-run')) {
                            $this->line("Would copy order {$order->id}: {$path}");
                            $copied++;

                            continue;
                        }

                        if (! $documents->ensurePrivate($path, deletePublicWhenVerified: false)) {
                            $failed++;
                            $this->error("Failed to copy order {$order->id}: {$path}");

                            continue;
                        }

                        $copied++;
                    }

                    if (! $documents->copiesMatch($path)) {
                        $failed++;
                        $this->error("Private copy verification failed for order {$order->id}: {$path}");

                        continue;
                    }

                    $verified++;

                    if ($this->option('delete-public')) {
                        if ($this->option('dry-run')) {
                            $this->line("Would delete verified public original for order {$order->id}: {$path}");
                        } elseif ($public->delete($path)) {
                            $deleted++;
                        } else {
                            $failed++;
                            $this->error("Failed to delete verified public original for order {$order->id}: {$path}");
                        }
                    }
                }
            });

        $this->info(
            ($this->option('dry-run') ? 'Dry run: ' : '')
            ."{$copied} copied, {$verified} verified, {$deleted} public original(s) deleted, {$missing} missing, {$failed} failed."
        );

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
