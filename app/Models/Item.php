<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;

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
            'P' => 'Cat',
            'D' => 'Body',
        ];
    }

    /**
     * Get the item type name
     */
    public function getItemTypeNameAttribute(): string
    {
        return self::getItemTypes()[$this->item_type] ?? $this->item_type;
    }

    /**
     * Generate the next sequential code for an item type (e.g. B0028).
     * Based on the highest numeric suffix already in use for the prefix,
     * not the latest row id, so recoded/imported rows can't cause collisions.
     */
    public static function generateCode(string $itemType): string
    {
        $prefixLength = strlen($itemType) + 1;

        $row = static::whereRaw('code REGEXP ?', ['^' . $itemType . '[0-9]+$'])
            ->selectRaw("MAX(CAST(SUBSTRING(code, {$prefixLength}) AS UNSIGNED)) AS max_num")
            ->first();

        $nextNumber = ((int) ($row?->max_num ?? 0)) + 1;

        return $itemType . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create an item with an auto-generated code, retrying if a concurrent
     * request claims the same code first (relies on the unique index on `code`).
     */
    public static function createWithAutoCode(string $itemType, array $attributes): self
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return static::create(array_merge($attributes, [
                    'item_type' => $itemType,
                    'code'      => static::generateCode($itemType),
                ]));
            } catch (QueryException $e) {
                if (($e->errorInfo[1] ?? null) !== 1062 || $attempt === 2) {
                    throw $e;
                }
            }
        }
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
