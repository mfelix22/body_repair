<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number',
        'ppb_number',
        'po_type',
        'purchase_request_id',
        'supplier_id',
        'order_date',
        'expected_delivery_date',
        'supplier_name',
        'supplier_address',
        'supplier_phone',
        'supplier_contact_person',
        'lokasi_pengerjaan',
        'lokasi_pengiriman',
        'total_amount',
        'misc_cost',
        'misc_cost_description',
        'status',
        'notes',
        'cancellation_reason',
        'include_ppn',
        'pph_type',
        'waktu_pengerjaan',
        'pembayaran',
        'payment_method',
        'bank_account',
        'jatuh_tempo',
        'payment_terms',
        'invoice_number',
        'invoice_date',
        'invoice_due_date',
        'invoice_notes',
        'invoice_recorded_by',
        'invoice_recorded_at',
        'created_by',
        'approved_by',
        'approved_at',
        'revoked_by',
        'revoked_at',
        'revocation_reason',
        'printed_at',
        'printed_by',
        'closed_by',
        'closed_at',
        'nomor_nota',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'invoice_date' => 'date',
        'invoice_due_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'revoked_at'  => 'datetime',
        'invoice_recorded_at' => 'datetime',
        'printed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function invoiceRecorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invoice_recorded_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(Receivable::class);
    }

    public function miscCosts(): HasMany
    {
        return $this->hasMany(PurchaseOrderMiscCost::class);
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseOrderInvoice::class);
    }

    public function hasOpenReceiptLines(): bool
    {
        return $this->details->contains(function ($detail) {
            return $detail->getOpenQuantity() > 0;
        });
    }

    public function hasClosedShortageLines(): bool
    {
        return $this->details->contains(function ($detail) {
            return (float) ($detail->closed_shortage_quantity ?? 0) > 0;
        });
    }

    /**
     * Get total of all misc costs
     */
    public function getTotalMiscCostAttribute(): float
    {
        return (float) $this->miscCosts()->sum('amount');
    }

    /**
     * Calculate total amount from details
     */
    public function calculateTotal(): void
    {
        $this->total_amount = $this->details()->sum('total_price');
        $this->save();
    }
}
