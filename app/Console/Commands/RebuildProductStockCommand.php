<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Purchasing\ReorderService;
use Illuminate\Console\Command;

class RebuildProductStockCommand extends Command
{
    protected $signature = 'inventory:rebuild-stock {product? : Optional product ID}';

    protected $description = 'Rebuild product on-hand stock from purchases, completed sales, and returns';

    public function handle(ReorderService $reorder): int
    {
        $productId = $this->argument('product');

        if (filled($productId)) {
            $product = Product::query()->find($productId);

            if ($product === null) {
                $this->components->error("Product #{$productId} was not found.");

                return self::FAILURE;
            }

            $result = $reorder->rebuildProductStock($product);
            $this->components->info("Product #{$result['product_id']} stock set to {$result['stock_qty']}.");

            return self::SUCCESS;
        }

        $results = $reorder->rebuildAllProductStock();
        $this->components->info('Rebuilt stock for '.count($results).' product(s).');

        return self::SUCCESS;
    }
}
