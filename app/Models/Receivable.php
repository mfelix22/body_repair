<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receivable extends Model
{
    protected $fillable = [
        'receive_number',
        'bon_in_type',
        'purchase_order_id',
        'supplier_id',
        'supplier_name',
        'received_date',
        'status',
        'notes',
        'printed_at',
        'printed_by',
    ];

    protected $casts = [
        'received_date' => 'date',
        'bon_in_type' => 'integer',
        'printed_at' => 'datetime',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class)->withDefault();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(ReceivableItem::class);
    }
}
