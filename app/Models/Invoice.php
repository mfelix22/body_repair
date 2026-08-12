<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'work_order_id',
        'customer_id',
        'qq',
        'invoice_date',
        'due_date',
        'subtotal',
        'discount_amount',
        'discount_percentage',
        'grand_total',
        'cogm_material',
        'cogm_labor',
        'cogm',
        'or_amount',
        'status',
        'notes',
        'cancellation_reason',
        'created_by',
    ];

    protected $casts = [
        'invoice_date'        => 'date',
        'due_date'            => 'date',
        'subtotal'            => 'decimal:2',
        'discount_amount'     => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'grand_total'         => 'decimal:2',
        'cogm_material'       => 'decimal:2',
        'cogm_labor'          => 'decimal:2',
        'cogm'                => 'decimal:2',
        'or_amount'           => 'decimal:2',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creditNote(): HasOne
    {
        return $this->hasOne(CreditNote::class);
    }

    /**
     * Calculate grand total
     */
    public function calculateTotal(): void
    {
        $this->grand_total = $this->subtotal + $this->tax_amount - $this->discount_amount;
        $this->save();
    }
}
