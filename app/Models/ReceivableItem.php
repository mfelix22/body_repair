<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceivableItem extends Model
{
    protected $fillable = [
        'receivable_id',
        'item_id',
        'uom_id',
        'quantity_ordered',
        'quantity_received',
        'unit_price',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:2',
        'quantity_received' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function receivable()
    {
        return $this->belongsTo(Receivable::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function uom()
    {
        return $this->belongsTo(UOM::class);
    }
}
