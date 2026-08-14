<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimasiLabor extends Model
{
    protected $fillable = [
        'estimasi_id',
        'labor_id',
        'description',
        'quantity',
        'rate',
        'total_price',
    ];

    protected $casts = [
        'quantity'    => 'decimal:2',
        'rate'        => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function estimasi(): BelongsTo
    {
        return $this->belongsTo(Estimasi::class);
    }

    public function labor(): BelongsTo
    {
        return $this->belongsTo(Labor::class);
    }
}
