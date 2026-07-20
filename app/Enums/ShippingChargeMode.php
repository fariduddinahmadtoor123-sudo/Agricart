<?php

namespace App\Enums;

enum ShippingChargeMode: string
{
    case CollectUpfront = 'collect_upfront';
    case CalculateLater = 'calculate_later';
    case CustomerPaysCarrier = 'customer_pays_carrier';

    public function label(): string
    {
        return __('agricart.delivery_methods.shipping_modes.'.$this->value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
