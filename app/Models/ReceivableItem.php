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
