<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $fillable = [
        'item_id',
        'quantity',
        'avg_cost',
        'location',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'avg_cost' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Add quantity to stock (always in smallest UOM)
     */
    public function addQuantity(float $quantity): void
    {
        $this->quantity += $quantity;
        $this->save();
    }

    /**
     * Deduct quantity from stock (always in smallest UOM)
     */
    public function deductQuantity(float $quantity): bool
    {
        if ($this->quantity < $quantity) {
            return false; // Insufficient stock
        }

        $this->quantity -= $quantity;
        $this->save();
        return true;
    }
}
