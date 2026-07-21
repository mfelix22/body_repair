<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estimasi extends Model
{
    protected $fillable = [
        'estimasi_number',
        'work_order_id',
        'created_by',
        'subtotal',
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
        'notes',
    ];

    protected $casts = [
        'approver1_approved_at' => 'datetime',
        'approver1_rejected_at' => 'datetime',
        'approver2_approved_at' => 'datetime',
        'approver2_rejected_at' => 'datetime',
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

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'no_discount']);
    }

    /**
     * Is this user pending approval on this Estimasi right now?
     */
    public function isPendingMyApproval(int $userId): bool
    {
        if ($this->status !== 'pending_approval') {
            return false;
        }

        if ($this->approvals_required === 1) {
            return $this->approver1_id === $userId
                && is_null($this->approver1_approved_at)
                && is_null($this->approver1_rejected_at);
        }

        if ($this->approvals_required === 2) {
            $stage1Done = !is_null($this->approver1_approved_at);
            if (!$stage1Done) {
                return $this->approver1_id === $userId
                    && is_null($this->approver1_approved_at)
                    && is_null($this->approver1_rejected_at);
            }
            return $this->approver2_id === $userId
                && is_null($this->approver2_approved_at)
                && is_null($this->approver2_rejected_at);
        }

        return false;
    }

    /**
     * Compute status label info for display.
     */
    public function getStatusBadge(): array
    {
        return match ($this->status) {
            'approved' => ['color' => 'success', 'label' => 'Approved'],
            'rejected' => ['color' => 'danger', 'label' => 'Rejected'],
            'no_discount' => ['color' => 'secondary', 'label' => 'No Discount'],
            default => ['color' => 'warning', 'label' => 'Pending Approval'],
        };
    }
}
