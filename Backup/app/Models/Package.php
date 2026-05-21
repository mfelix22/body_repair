<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use Auditable;

    // BOM items for this package
    public function bomItems(): HasMany
    {
        return $this->hasMany(PackageBomItem::class)->with(['item.smallestUom', 'uom']);
    }

    protected $fillable = [
        'category',
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sizes(): HasMany
    {
        return $this->hasMany(PackageSize::class);
    }

    public function activeSizes(): HasMany
    {
        return $this->sizes()->where('is_active', true)->orderBy('price');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
