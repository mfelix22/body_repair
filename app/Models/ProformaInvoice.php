<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaInvoice extends Model
{
    protected $fillable = [
        'proforma_number',
        'work_order_id',
        'created_by',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_percentage',
        'discount_amount',
        'total',
        'status',
        'approvals_required',
        'approver1_id',
        'approver1_approved_at',
        'approver1_rejected_at',
        'approver2_id',
        'approver2_approved_at',
        'approver2_rejected_at',
        'approver3_id',
        'approver3_approved_at',
        'approver3_rejected_at',
        'notes',
        'voucher_code',
        'voucher_amount',
    ];

    protected $casts = [
        'approver1_approved_at' => 'datetime',
        'approver1_rejected_at' => 'datetime',
        'approver2_approved_at' => 'datetime',
        'approver2_rejected_at' => 'datetime',
        'approver3_approved_at' => 'datetime',
        'approver3_rejected_at' => 'datetime',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver1()
    {
        return $this->belongsTo(User::class, 'approver1_id');
    }

    public function approver2()
    {
        return $this->belongsTo(User::class, 'approver2_id');
    }

    public function approver3()
    {
        return $this->belongsTo(User::class, 'approver3_id');
    }

    public function discountLines()
    {
        return $this->hasMany(ProformaDiscountLine::class);
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'no_discount']);
    }

    /**
     * Recompute aggregate discount/total from lines and update parent status.
     * Called after any line status change.
     */
    public function recomputeFromLines(): void
    {
        $lines      = $this->discountLines()->get();
        $subtotal   = (float) $this->subtotal;
        $voucherAmt = (float) $this->voucher_amount;

        if ($lines->isEmpty() && $voucherAmt <= 0) {
            $this->discount_amount     = 0;
            $this->discount_percentage = 0;
            $this->total               = $subtotal;
            $this->status              = 'no_discount';
            $this->save();
            return;
        }

        // Sum discount only from approved lines (rejected lines = no discount applied)
        $totalLineDiscount = $lines->isEmpty() ? 0 : (float) $lines->where('status', 'approved')->sum('discount_amount');
        $this->discount_amount     = $totalLineDiscount;
        $this->discount_percentage = $subtotal > 0 ? round($totalLineDiscount / $subtotal * 100, 4) : 0;
        $this->total               = $subtotal - $totalLineDiscount - $voucherAmt;

        // Voucher-only proforma = immediately approved (flat pre-authorised code, no line approval needed)
        if ($voucherAmt > 0 && $lines->isEmpty()) {
            $this->status = 'approved';
        } elseif ($lines->where('status', 'pending_approval')->count() > 0) {
            $this->status = 'pending_approval';
        } else {
            $this->status = 'approved';
        }
        $this->save();
    }

    /**
     * Is this user pending approval on ANY line of this proforma?
     */
    public function isPendingMyApproval(int $userId): bool
    {
        if ($this->status !== 'pending_approval') {
            return false;
        }
        foreach ($this->discountLines as $line) {
            if ($line->isPendingMyApproval($userId)) {
                return true;
            }
        }
        return false;
    }
}

