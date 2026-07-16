<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransaction extends Model
{
    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_OPENING = 'opening';

    public static function transactionTypes(): array
    {
        return [
            self::TYPE_IN => 'In',
            self::TYPE_OUT => 'Out',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_OPENING => 'Opening',
        ];
    }

    public function typeLabel(): string
    {
        return self::transactionTypes()[$this->transaction_type] ?? ucfirst($this->transaction_type);
    }

    protected $fillable = [
        'item_id',
        'transaction_type',
        'quantity',
        'unit_cost',
        'balance_after',
        'location',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
