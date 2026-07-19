<?php

namespace App\Console\Commands;

use App\Models\SaleItem;
use App\Models\SaleReturnItem;
use App\Services\Sales\HistoricalCostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillSaleItemCostsCommand extends Command
{
    protected $signature = 'sales:backfill-cost-snapshots
        {--commit : Persist unambiguous snapshots; without this option the command is a dry run}
        {--chunk=200 : Number of sale items processed per chunk}';

    protected $description = 'Backfill immutable sale-item COGS snapshots from unambiguous purchase history';

    public function handle(HistoricalCostingService $costing): int
    {
        $commit = (bool) $this->option('commit');
        $chunk = max(1, min(1000, (int) $this->option('chunk')));
        $counts = ['eligible' => 0, 'updated' => 0, 'ambiguous' => 0, 'no_history' => 0];

        SaleItem::query()
            ->whereNull('unit_cost_snapshot')
            ->with('sale:id,sale_date')
            ->orderBy('id')
            ->chunkById($chunk, function ($items) use ($costing, $commit, &$counts): void {
                foreach ($items as $item) {
                    $resolved = $costing->resolve(
                        (int) $item->product_id,
                        $item->sale?->sale_date?->toDateString() ?? now()->toDateString(),
                        true,
                    );

                    if ($resolved === null) {
                        $counts['no_history']++;
                        continue;
                    }

                    if ($resolved['ambiguous']) {
                        $counts['ambiguous']++;
                        continue;
                    }

                    $counts['eligible']++;
                    if (! $commit) {
                        continue;
                    }

                    DB::table('sale_items')->where('id', $item->id)->whereNull('unit_cost_snapshot')->update([
                        'unit_cost_snapshot' => $resolved['unit_cost'],
                        'cogs_snapshot' => round((float) $item->quantity * $resolved['unit_cost'], 4),
                        'costing_policy' => $resolved['policy'],
                        'updated_at' => now(),
                    ]);
                    $counts['updated']++;
                }
            });

        if ($commit) {
            SaleReturnItem::query()
                ->whereNull('unit_cost_snapshot')
                ->orderBy('id')
                ->chunkById($chunk, function ($items): void {
                    $costs = SaleItem::query()
                        ->whereIn('id', $items->pluck('sale_item_id')->filter())
                        ->pluck('unit_cost_snapshot', 'id');

                    foreach ($items as $item) {
                        $unitCost = $costs[$item->sale_item_id] ?? null;
                        if ($unitCost === null) {
                            continue;
                        }

                        DB::table('sale_return_items')->where('id', $item->id)->whereNull('unit_cost_snapshot')->update([
                            'unit_cost_snapshot' => $unitCost,
                            'cogs_reversal_snapshot' => round((float) $item->return_quantity * (float) $unitCost, 4),
                            'updated_at' => now(),
                        ]);
                    }
                });
        }

        $this->table(['Mode', 'Eligible', 'Updated', 'Ambiguous', 'No history'], [[
            $commit ? 'commit' : 'dry-run',
            $counts['eligible'],
            $counts['updated'],
            $counts['ambiguous'],
            $counts['no_history'],
        ]]);

        return self::SUCCESS;
    }
}
