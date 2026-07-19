<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPhone extends Model
{
    protected $fillable = [
        'customer_id',
        'phone',
        'canonical_phone',
        'sort_order',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
