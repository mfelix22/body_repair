<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BonOut extends Model
{
    protected $fillable = [
        'work_order_id',
        'bon_out_number',
        'bon_out_type',
        'issued_date',
        'issued_to',
        'purpose',
        'notes',
        'status',
        'total_cogs',
        'created_by',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'issued_date'  => 'date',
        'completed_at' => 'datetime',
        'total_cogs'   => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BonOutItem::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
