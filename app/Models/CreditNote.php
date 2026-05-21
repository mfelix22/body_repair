<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNote extends Model
{
    protected $fillable = [
        'credit_note_number',
        'invoice_id',
        'work_order_id',
        'customer_id',
        'qq',
        'credit_note_date',
        'subtotal',
        'discount_percentage',
        'discount_amount',
        'grand_total',
        'notes',
        'cancellation_reason',
        'created_by',
    ];

    protected $casts = [
        'credit_note_date'    => 'date',
        'subtotal'            => 'decimal:2',
        'discount_amount'     => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'grand_total'         => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

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
}
