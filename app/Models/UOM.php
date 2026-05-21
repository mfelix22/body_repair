<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UOM extends Model
{
    protected $table = 'uoms';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get conversions where this UOM is the source
     */
    public function conversionsFrom(): HasMany
    {
        return $this->hasMany(UOMConversion::class, 'from_uom_id');
    }

    /**
     * Get conversions where this UOM is the target
     */
    public function conversionsTo(): HasMany
    {
        return $this->hasMany(UOMConversion::class, 'to_uom_id');
    }

    /**
     * Convert value from this UOM to another UOM
     * 
     * @param float $value
     * @param int $toUomId
     * @return float|null
     */
    public function convertTo(float $value, int $toUomId): ?float
    {
        if ($this->id === $toUomId) {
            return $value;
        }

        $conversion = $this->conversionsFrom()->where('to_uom_id', $toUomId)->first();

        if ($conversion) {
            return $value * $conversion->conversion_factor;
        }

        return null;
    }
}
