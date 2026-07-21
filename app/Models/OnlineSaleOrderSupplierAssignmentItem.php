<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineSaleOrderSupplierAssignmentItem extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'online_sale_order_supplier_assignment_id',
        'online_sale_order_item_id',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(OnlineSaleOrderSupplierAssignment::class, 'online_sale_order_supplier_assignment_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OnlineSaleOrderItem::class, 'online_sale_order_item_id');
    }
}
