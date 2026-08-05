<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderDetail extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'purchase_request_detail_id',
        'item_id',
        'uom_id',
        'quantity',
        'unit_price',
        'total_price',
        'discount',
        'discount_percentage',
        'received_quantity',
        'closed_shortage_quantity',
        'shortage_close_reason',
        'shortage_closed_by',
        'shortage_closed_at',
        'notes',
        'remarks',
        'service_description',
        'conversion_to_smallest',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'received_quantity' => 'decimal:2',
        'closed_shortage_quantity' => 'decimal:2',
        'shortage_closed_at' => 'datetime',
        'conversion_to_smallest' => 'decimal:6',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseRequestDetail(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequestDetail::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UOM::class);
    }

    public function purchaseOrderInvoiceLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderInvoiceLine::class);
    }

    /**
     * Get remaining quantity to receive
     */
    public function getRemainingQuantity(): float
    {
        return $this->getOpenQuantity();
    }

    public function getOpenQuantity(): float
    {
        return max(0, (float) $this->quantity - (float) $this->received_quantity - (float) ($this->closed_shortage_quantity ?? 0));
    }

    public function getBilledQuantity(): float
    {
        return (float) $this->purchaseOrderInvoiceLines->sum('qty_billed');
    }

    public function getRemainingBillableQuantity(): float
    {
        return max(0, (float) $this->received_quantity - $this->getBilledQuantity());
    }

    public function getLineStatusAttribute(): string
    {
        if ($this->getOpenQuantity() <= 0) {
            return (float) ($this->closed_shortage_quantity ?? 0) > 0 ? 'closed_shortage' : 'received_full';
        }

        if ((float) $this->received_quantity > 0 || (float) ($this->closed_shortage_quantity ?? 0) > 0) {
            return 'partial';
        }

        return 'open';
    }

    /**
     * Check if fully received
     */
    public function isFullyReceived(): bool
    {
        return $this->getOpenQuantity() <= 0;
    }
}
