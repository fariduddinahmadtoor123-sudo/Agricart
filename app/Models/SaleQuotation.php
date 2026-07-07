<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleQuotation extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'quotation_number',
        'customer_id',
        'prepared_by_id',
        'quotation_date',
        'locale',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'quotation_amount',
        'print_controls',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quotation_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'quotation_amount' => 'decimal:2',
            'print_controls' => 'boolean',
        ];
    }

    public function displayLocale(): string
    {
        return in_array($this->locale, ['en', 'ur'], true) ? $this->locale : 'en';
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleQuotationItem::class)->orderBy('sort_order');
    }
}
