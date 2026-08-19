<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimasiItem extends Model
{
    protected $fillable = [
        'estimasi_id',
        'item_id',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'is_supply',
    ];

    protected $casts = [
        'quantity'    => 'decimal:2',
        'unit_price'  => 'decimal:2',
        'total_price' => 'decimal:2',
        'is_supply'   => 'boolean',
    ];

    public function estimasi(): BelongsTo
    {
        return $this->belongsTo(Estimasi::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
