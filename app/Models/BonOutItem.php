<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonOutItem extends Model
{
    protected $fillable = [
        'bon_out_id',
        'work_order_item_id',
        'item_id',
        'uom_id',
        'demand_quantity',
        'actual_quantity',
        'unit_cost',
        'unit_price',
        'remark',
    ];

    protected $casts = [
        'demand_quantity' => 'decimal:2',
        'actual_quantity' => 'decimal:2',
        'unit_cost'       => 'decimal:2',
        'unit_price'      => 'decimal:2',
    ];

    public function bonOut(): BelongsTo
    {
        return $this->belongsTo(BonOut::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UOM::class);
    }
}
