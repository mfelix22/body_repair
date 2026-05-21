<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UOMConversion extends Model
{
    protected $table = 'uom_conversions';

    protected $fillable = [
        'from_uom_id',
        'to_uom_id',
        'conversion_factor',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:6',
    ];

    /**
     * Get the source UOM
     */
    public function fromUom(): BelongsTo
    {
        return $this->belongsTo(UOM::class, 'from_uom_id');
    }

    /**
     * Get the target UOM
     */
    public function toUom(): BelongsTo
    {
        return $this->belongsTo(UOM::class, 'to_uom_id');
    }

    /**
     * Convert a value using this conversion
     * 
     * @param float $value
     * @return float
     */
    public function convert(float $value): float
    {
        return $value * $this->conversion_factor;
    }
}
