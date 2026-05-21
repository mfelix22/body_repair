<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Labor extends Model
{
    protected $fillable = [
        'labor_code',
        'description',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function workOrderLabors(): HasMany
    {
        return $this->hasMany(WorkOrderLabor::class);
    }
}
