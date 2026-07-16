<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Panel extends Model
{
    protected $fillable = [
        'panel_code',
        'description',
        'price',
        'multiplier',
        'price_0_300',
        'price_300_500',
        'price_500_800',
        'price_800_2000',
        'is_active',
    ];

    protected $casts = [
        'price'          => 'decimal:2',
        'multiplier'     => 'decimal:2',
        'price_0_300'    => 'decimal:2',
        'price_300_500'  => 'decimal:2',
        'price_500_800'  => 'decimal:2',
        'price_800_2000' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    public function workOrderLabors(): HasMany
    {
        return $this->hasMany(WorkOrderLabor::class);
    }
}
