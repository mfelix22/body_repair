<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaDiscountLine extends Model
{
    protected $fillable = [
        'proforma_invoice_id',
        'target_type',
        'target_id',
        'description',
        'original_price',
        'discount_percentage',
        'discount_amount',
        'final_price',
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
    ];

    protected $casts = [
        'approver1_approved_at' => 'datetime',
        'approver1_rejected_at' => 'datetime',
        'approver2_approved_at' => 'datetime',
        'approver2_rejected_at' => 'datetime',
        'approver3_approved_at' => 'datetime',
        'approver3_rejected_at' => 'datetime',
    ];

    public function proformaInvoice()
    {
        return $this->belongsTo(ProformaInvoice::class);
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

    public function workOrderItem()
    {
        return $this->belongsTo(WorkOrderItem::class, 'target_id');
    }

    public function workOrderLabor()
    {
        return $this->belongsTo(WorkOrderLabor::class, 'target_id');
    }

    /**
     * Is this line still waiting on this user's approval?
     */
    public function isPendingMyApproval(int $userId): bool
    {
        if ($this->status !== 'pending_approval') {
            return false;
        }

        // < 20%: any of 3 approving = done
        if ($this->approvals_required === 1) {
            if ($this->approver1_id === $userId && is_null($this->approver1_approved_at) && is_null($this->approver1_rejected_at)) return true;
            if ($this->approver2_id === $userId && is_null($this->approver2_approved_at) && is_null($this->approver2_rejected_at)) return true;
            if ($this->approver3_id === $userId && is_null($this->approver3_approved_at) && is_null($this->approver3_rejected_at)) return true;
            return false;
        }

        // >= 20%: Mgr/Acc (approver1) first, then Director (approver2)
        if ($this->approvals_required === 2) {
            $stage1Done = !is_null($this->approver1_approved_at);
            if (!$stage1Done) {
                if ($this->approver1_id === $userId && is_null($this->approver1_approved_at) && is_null($this->approver1_rejected_at)) return true;
            }
            if ($stage1Done) {
                if ($this->approver2_id === $userId && is_null($this->approver2_approved_at) && is_null($this->approver2_rejected_at)) return true;
            }
            return false;
        }

        return false;
    }

    /**
     * Compute line status label info for display.
     */
    public function getStatusBadge(): array
    {
        return match ($this->status) {
            'approved' => ['color' => 'success', 'label' => 'Approved'],
            'rejected' => ['color' => 'danger',  'label' => 'Rejected'],
            default    => ['color' => 'warning',  'label' => 'Pending'],
        };
    }
}
