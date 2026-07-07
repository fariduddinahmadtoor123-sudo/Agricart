<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchasePaymentSummary extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'summary_number',
        'summary_date',
        'locale',
        'paper_size',
        'notes',
        'payment_sources',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'summary_date' => 'date',
            'payment_sources' => 'array',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchasePaymentSummaryItem::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function displayLocale(): string
    {
        return in_array($this->locale, ['en', 'ur'], true) ? $this->locale : 'en';
    }

    public function localeLabel(): string
    {
        return __('purchase_payment_summary.locales.' . $this->displayLocale());
    }

    public function totalPayments(): float
    {
        $total = 0.0;

        foreach ($this->items as $item) {
            $total += $item->numericPaymentAmount();
        }

        return $total;
    }

    public function paidLineCount(): int
    {
        return $this->items->filter(fn (PurchasePaymentSummaryItem $item): bool => filled($item->payment_amount))->count();
    }
}
