<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderLabor extends Model
{
    protected $fillable = [
        'work_order_id',
        'labor_id',
        'description',
        'qty',
        'remarks',
        'hours',
        'rate',
        'total_price',
    ];

    protected $casts = [
        'qty'         => 'decimal:2',
        'hours'       => 'decimal:2',
        'rate'        => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function labor(): BelongsTo
    {
        return $this->belongsTo(Labor::class);
    }
}
