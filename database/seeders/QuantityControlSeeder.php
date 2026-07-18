<?php

namespace Database\Seeders;

use App\Models\QuantityControl;
use App\Services\PricingControls\QuantityPricingEngine;
use Illuminate\Database\Seeder;

class QuantityControlSeeder extends Seeder
{
    public function run(): void
    {
        $control = QuantityControl::query()->updateOrCreate(
            ['control_number' => 'QTY-00001'],
            [
                'name_en' => 'Default Quantity Channels',
                'name_ur' => 'ڈیفالٹ کوانٹٹی چینلز',
                'status' => 'active',
                'notes' => 'Default product quantity thresholds. Manage these from Marketplace → Quantity Controls.',
                'settings' => [],
            ],
        );

        $defaults = [
            'retail' => 1,
            'wholesale' => 5,
            'super_wholesale' => 10,
            'distributor' => 20,
        ];

        foreach (QuantityPricingEngine::PRICE_TIER_TYPES as $tierKey => $definition) {
            $control->tiers()->updateOrCreate(
                ['tier_key' => $tierKey],
                [
                    'name_en' => $definition['name_en'],
                    'name_ur' => $definition['name_ur'],
                    'min_quantity' => $defaults[$tierKey],
                    'price_field' => $definition['price_field'],
                    'sort_order' => QuantityPricingEngine::TIER_SORT_ORDER[$tierKey],
                    'status' => 'active',
                ],
            );
        }
    }
}
