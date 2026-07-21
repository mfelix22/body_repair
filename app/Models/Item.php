<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Item extends Model
{
    use Auditable;

    protected $fillable = [
        'item_type',
        'code',
        'name',
        'description',
        'category',
        'smallest_uom_id',
        'reorder_level',
        'selling_price',
        'is_active',
        'is_complete',
        'is_manual_entry',
    ];

    protected $casts = [
        'reorder_level'  => 'decimal:2',
        'selling_price'  => 'decimal:2',
        'is_active'      => 'boolean',
        'is_complete'    => 'boolean',
        'is_manual_entry' => 'boolean',
    ];

    /**
     * Get item type names
     */
    public static function getItemTypes(): array
    {
        return [
            'A' => 'Coating',
            'B' => 'Chemical',
            'C' => 'Consumable',
            'E' => 'Equipment',
            'T' => 'Tools',
            'TE' => 'Tools & Equipment',
            'SP' => 'Sparepart',
        ];
    }

    /**
     * Get the item type name
     */
    public function getItemTypeNameAttribute(): string
    {
        return self::getItemTypes()[$this->item_type] ?? $this->item_type;
    }

    public function smallestUom(): BelongsTo
    {
        return $this->belongsTo(UOM::class, 'smallest_uom_id');
    }

    public function itemUoms(): HasMany
    {
        return $this->hasMany(ItemUOM::class);
    }

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class)->where('location', 'default');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    /**
     * Get current stock quantity in smallest UOM
     */
    public function getCurrentStock(string $location = 'default'): float
    {
        return $this->stocks()->where('location', $location)->value('quantity') ?? 0;
    }

    /**
     * Check if item needs reorder
     */
    public function needsReorder(string $location = 'default'): bool
    {
        return $this->getCurrentStock($location) <= $this->reorder_level;
    }
}
