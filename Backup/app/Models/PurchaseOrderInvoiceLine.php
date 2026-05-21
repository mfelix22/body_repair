<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderInvoiceLine extends Model
{
    protected $fillable = [
        'purchase_order_invoice_id',
        'purchase_order_detail_id',
        'qty_billed',
        'unit_price',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'qty_billed' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function purchaseOrderInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderInvoice::class);
    }

    public function purchaseOrderDetail(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderDetail::class);
    }
}
