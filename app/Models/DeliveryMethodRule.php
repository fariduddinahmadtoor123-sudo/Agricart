<?php

namespace App\Models;

use App\Enums\ShippingChargeMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryMethodRule extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'delivery_method_id',
        'allowed_payment_methods',
        'cod_allowed',
        'cod_min_advance_percent',
        'full_advance_required',
        'customer_notice_en',
        'customer_notice_ur',
        'shipping_charge_mode',
        'shipping_fee',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allowed_payment_methods' => 'array',
            'cod_allowed' => 'boolean',
            'cod_min_advance_percent' => 'decimal:2',
            'full_advance_required' => 'boolean',
            'shipping_charge_mode' => ShippingChargeMode::class,
            'shipping_fee' => 'decimal:2',
        ];
    }

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class);
    }

    /**
     * @return list<string>
     */
    public function allowedPaymentMethods(): array
    {
        $methods = $this->allowed_payment_methods;

        return is_array($methods) ? array_values(array_filter(array_map('strval', $methods))) : [];
    }

    public function localizedNotice(?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $value = $locale === 'ur' && filled($this->customer_notice_ur)
            ? $this->customer_notice_ur
            : $this->customer_notice_en;

        return filled($value) ? (string) $value : null;
    }
}
