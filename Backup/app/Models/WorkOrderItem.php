<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderItem extends Model
{
    protected $fillable = [
        'work_order_id',
        'item_id',
        'uom_id',
        'demand_quantity',
        'actual_quantity',
        'remark',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'demand_quantity'  => 'decimal:2',
        'actual_quantity'  => 'decimal:2',
        'unit_price'       => 'decimal:2',
        'total_price'      => 'decimal:2',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
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
