<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCostAdjustment extends Model
{
    protected $fillable = [
        'item_id',
        'stock_id',
        'old_avg_cost',
        'new_avg_cost',
        'reason',
        'adjusted_by',
    ];

    protected $casts = [
        'old_avg_cost' => 'decimal:2',
        'new_avg_cost' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
