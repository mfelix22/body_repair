<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequestDetail extends Model
{
    protected $fillable = [
        'purchase_request_id',
        'item_id',
        'uom_id',
        'quantity',
        'ordered_quantity',
        'unit_price',
        'total_price',
        'notes',
        'service_description',
        'is_custom_item',
        'custom_item_name',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'ordered_quantity' => 'decimal:2',
        'is_custom_item' => 'boolean',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UOM::class);
    }

    public function purchaseOrderDetails()
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }

    /**
     * Get remaining quantity to order
     */
    public function getRemainingQuantity(): float
    {
        return $this->quantity - $this->ordered_quantity;
    }

    /**
     * Check if this PR item is fully ordered
     */
    public function isFullyOrdered(): bool
    {
        return $this->ordered_quantity >= $this->quantity;
    }

    /**
     * Check if this PR item is partially ordered
     */
    public function isPartiallyOrdered(): bool
    {
        return $this->ordered_quantity > 0 && $this->ordered_quantity < $this->quantity;
    }
}
