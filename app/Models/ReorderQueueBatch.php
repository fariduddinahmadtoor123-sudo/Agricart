<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReorderQueueBatch extends Model
{
    public const STATUS_PENDING = 'pending';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'batch_number',
        'notes',
        'created_by',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ReorderQueueItem::class)->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pendingItemCount(): int
    {
        return $this->items()->where('status', ReorderQueueItem::STATUS_PENDING)->count();
    }
}
