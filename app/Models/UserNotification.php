<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    protected $table = 'user_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'url',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Icon class for each notification type.
     */
    public function iconClass(): string
    {
        return match ($this->type) {
            'pr_submitted'       => 'fas fa-file-alt text-secondary',
            'pr_dept_approved'   => 'fas fa-check text-info',
            'pr_needs_gm'        => 'fas fa-stamp text-primary',
            'pr_gm_approved'     => 'fas fa-check-double text-success',
            'pr_ready_for_po'    => 'fas fa-clipboard-list text-warning',
            'pr_rejected'        => 'fas fa-times-circle text-danger',
            'pr_cancelled'       => 'fas fa-ban text-danger',
            'pr_completed'       => 'fas fa-check-circle text-success',
            'pf_submitted'       => 'fas fa-file-invoice text-primary',
            'pf_needs_approval'  => 'fas fa-stamp text-warning',
            'pf_line_approved'   => 'fas fa-check text-success',
            'pf_line_rejected'   => 'fas fa-times-circle text-danger',
            'pf_approved'        => 'fas fa-check-double text-success',
            'po_created'         => 'fas fa-file-purchase-alt text-secondary',
            'po_approved'        => 'fas fa-check-double text-success',
            'po_cancelled'       => 'fas fa-ban text-danger',
            'po_received'        => 'fas fa-box-open text-info',
            'po_completed'       => 'fas fa-check-circle text-success',
            default              => 'fas fa-bell text-muted',
        };
    }
}
