<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderLabor extends Model
{
    protected $fillable = [
        'work_order_id',
        'labor_id',
        'panel_id',
        'description',
        'qty',
        'remarks',
        'hours',
        'rate',
        'total_price',
        'is_extra',
        'is_three_coat',
        'is_special_repair',
    ];

    protected $casts = [
        'qty'               => 'decimal:2',
        'hours'             => 'decimal:2',
        'rate'              => 'decimal:2',
        'total_price'       => 'decimal:2',
        'is_extra'          => 'boolean',
        'is_three_coat'     => 'boolean',
        'is_special_repair' => 'boolean',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function labor(): BelongsTo
    {
        return $this->belongsTo(Labor::class);
    }

    public function panel(): BelongsTo
    {
        return $this->belongsTo(Panel::class);
    }

    public function masterItem(): ?Model
    {
        return $this->panel ?? $this->labor;
    }

    public function isPanel(): bool
    {
        return !is_null($this->panel_id);
    }
}
