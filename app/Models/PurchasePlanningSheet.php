<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchasePlanningSheet extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'planning_number',
        'paper_size',
        'locale',
        'notes',
        'created_by',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchasePlanningItem::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function itemCount(): int
    {
        return $this->items()->count();
    }

    public function displayLocale(): string
    {
        return in_array($this->locale, ['en', 'ur'], true) ? $this->locale : 'en';
    }

    public function localeLabel(): string
    {
        return __('purchase_planning.locales.' . $this->displayLocale());
    }
}
