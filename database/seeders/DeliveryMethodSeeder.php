<?php

namespace Database\Seeders;

use App\Enums\ShippingChargeMode;
use App\Services\Logistics\DeliveryMethodService;
use App\Services\Sales\SaleService;
use Illuminate\Database\Seeder;

class DeliveryMethodSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(DeliveryMethodService::class);

        $defaults = [
            [
                'name_en' => 'Courier Delivery',
                'name_ur' => 'کورئیر ڈیلیوری',
                'description_en' => 'Doorstep courier delivery.',
                'description_ur' => 'دروازے تک کورئیر ڈیلیوری۔',
                'sort_order' => 10,
                'status' => 'active',
                'allowed_payment_methods' => SaleService::PAYMENT_METHODS,
                'cod_allowed' => true,
                'cod_min_advance_percent' => 0,
                'full_advance_required' => false,
                'customer_notice_en' => 'Courier will contact you before delivery.',
                'customer_notice_ur' => 'ڈیلیوری سے پہلے کورئیر آپ سے رابطہ کرے گا۔',
                'shipping_charge_mode' => ShippingChargeMode::CalculateLater->value,
            ],
            [
                'name_en' => 'Cargo Delivery',
                'name_ur' => 'کارگو ڈیلیوری',
                'description_en' => 'Goods sent via cargo agency.',
                'description_ur' => 'مال کارگو ایجنسی کے ذریعے بھیجا جاتا ہے۔',
                'sort_order' => 20,
                'status' => 'active',
                'allowed_payment_methods' => SaleService::PAYMENT_METHODS,
                'cod_allowed' => true,
                'cod_min_advance_percent' => 20,
                'full_advance_required' => false,
                'customer_notice_en' => 'Cargo charges may be confirmed after booking.',
                'customer_notice_ur' => 'کارگو چارجز بکنگ کے بعد تصدیق ہو سکتے ہیں۔',
                'shipping_charge_mode' => ShippingChargeMode::CalculateLater->value,
            ],
            [
                'name_en' => 'Transport Booking',
                'name_ur' => 'ٹرانسپورٹ بکنگ',
                'description_en' => 'We arrange a transport booking for your order.',
                'description_ur' => 'ہم آپ کے آرڈر کے لیے ٹرانسپورٹ بکنگ کرتے ہیں۔',
                'sort_order' => 30,
                'status' => 'active',
                'allowed_payment_methods' => ['bank_transfer'],
                'cod_allowed' => false,
                'cod_min_advance_percent' => 0,
                'full_advance_required' => true,
                'customer_notice_en' => 'Full advance is required before transport booking.',
                'customer_notice_ur' => 'ٹرانسپورٹ بکنگ سے پہلے مکمل ایڈوانس ضروری ہے۔',
                'shipping_charge_mode' => ShippingChargeMode::CollectUpfront->value,
            ],
            [
                'name_en' => 'Freight Delivery',
                'name_ur' => 'فریٹ ڈیلیوری',
                'description_en' => 'Customer pays the freight company directly.',
                'description_ur' => 'گاہک فریٹ کمپنی کو براہِ راست ادا کرتا ہے۔',
                'sort_order' => 40,
                'status' => 'active',
                'allowed_payment_methods' => SaleService::PAYMENT_METHODS,
                'cod_allowed' => true,
                'cod_min_advance_percent' => 0,
                'full_advance_required' => false,
                'customer_notice_en' => 'Freight charges are paid by you to the transport company.',
                'customer_notice_ur' => 'فریٹ چارجز آپ ٹرانسپورٹ کمپنی کو ادا کریں گے۔',
                'shipping_charge_mode' => ShippingChargeMode::CustomerPaysCarrier->value,
            ],
        ];

        foreach ($defaults as $row) {
            $existing = \App\Models\DeliveryMethod::query()->where('slug', \Illuminate\Support\Str::slug($row['name_en']))->first();

            if ($existing !== null) {
                $service->update($existing, $row);

                continue;
            }

            $service->create($row);
        }

        $service->attachActiveMethodsToUnlinkedProducts();
    }
}
