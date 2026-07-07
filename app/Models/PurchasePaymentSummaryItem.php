<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePaymentSummaryItem extends Model
{
    /** @var list<string> */
    public const LINE_STATUSES = ['ok', 'dispute'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'purchase_payment_summary_id',
        'supplier_id',
        'sort_order',
        'is_manual',
        'name_en',
        'name_ur',
        'phone',
        'address',
        'payment_amount',
        'line_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_manual' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function summary(): BelongsTo
    {
        return $this->belongsTo(PurchasePaymentSummary::class, 'purchase_payment_summary_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function displayName(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'ur' && filled($this->name_ur)) {
            return (string) $this->name_ur;
        }

        return (string) ($this->name_en ?: $this->name_ur);
    }

    public function numericPaymentAmount(): float
    {
        if (! filled($this->payment_amount)) {
            return 0.0;
        }

        return (float) str_replace(',', '', (string) $this->payment_amount);
    }

    public function lineStatusLabel(): string
    {
        if (! filled($this->line_status)) {
            return '—';
        }

        return __('purchase_payment_summary.line_statuses.' . $this->line_status);
    }
}
