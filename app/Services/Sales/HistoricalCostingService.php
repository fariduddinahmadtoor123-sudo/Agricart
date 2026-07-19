<?php

namespace App\Services\Sales;

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

/**
 * Costing policy: latest received/partially-received purchase item on or before
 * the sale date; prefer cost_price, then current_rate. No future purchase is used.
 */
class HistoricalCostingService
{
    public const POLICY = 'latest_received_purchase_v1';

    /**
     * @return array{unit_cost: float, policy: string, ambiguous: bool}|null
     */
    public function resolve(int $productId, string $saleDate, bool $detectAmbiguity = false): ?array
    {
        $base = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchase_items.product_id', $productId)
            ->where('purchases.purchase_date', '<=', substr($saleDate, 0, 10).' 23:59:59')
            ->whereIn('purchases.delivery_status', [
                Purchase::DELIVERY_RECEIVED,
                Purchase::DELIVERY_PARTIAL,
            ])
            ->where(function ($query): void {
                $query->where(function ($inner): void {
                    $inner->whereNotNull('purchase_items.cost_price')
                        ->where('purchase_items.cost_price', '!=', '');
                })->orWhere(function ($inner): void {
                    $inner->whereNotNull('purchase_items.current_rate')
                        ->where('purchase_items.current_rate', '!=', '');
                });
            });

        $latest = (clone $base)
            ->orderByDesc('purchases.purchase_date')
            ->orderByDesc('purchases.id')
            ->orderByDesc('purchase_items.id')
            ->first([
                'purchases.purchase_date',
                'purchases.id as purchase_id',
                'purchase_items.id as purchase_item_id',
                'purchase_items.cost_price',
                'purchase_items.current_rate',
            ]);

        if ($latest === null) {
            return null;
        }

        $cost = (float) ($latest->cost_price ?: 0);
        $cost = $cost > 0 ? $cost : (float) ($latest->current_rate ?: 0);

        if ($cost <= 0) {
            return null;
        }

        return [
            'unit_cost' => round($cost, 4),
            'policy' => self::POLICY,
            // purchase_items.id is unique, so the complete policy ordering cannot tie.
            'ambiguous' => false,
        ];
    }
}
