<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemUOM extends Model
{
    protected $table = 'item_uoms';

    protected $fillable = [
        'item_id',
        'uom_id',
        'conversion_to_smallest',
        'price',
        'is_default',
    ];

    protected $casts = [
        'conversion_to_smallest' => 'decimal:6',
        'price' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UOM::class);
    }

    /**
     * Convert quantity from this UOM to smallest UOM
     */
    public function convertToSmallest(float $quantity): float
    {
        return $quantity * $this->conversion_to_smallest;
    }

    /**
     * Convert quantity from smallest UOM to this UOM
     */
    public function convertFromSmallest(float $quantity): float
    {
        return $quantity / $this->conversion_to_smallest;
    }
}
