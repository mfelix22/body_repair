<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends Model
{
    protected $fillable = [
        'pr_number',
        'request_date',
        'requested_by',
        'notes',
        'cancellation_reason',
        'status',
        'type',
        'require_acknowledgement',
        'dept_head_by',
        'dept_head_at',
        'gm_by',
        'gm_at',
        'purchasing_received_by',
        'purchasing_received_at',
        'attachment_path',
        'berita_acara_path',
        'berita_acara_uploaded_by',
        'berita_acara_uploaded_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'dept_head_at' => 'datetime',
        'gm_at' => 'datetime',
        'purchasing_received_at' => 'datetime',
        'berita_acara_uploaded_at' => 'datetime',
    ];

    public function requestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function deptHeadApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dept_head_by');
    }

    public function gmApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gm_by');
    }

    public function purchasingReceiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchasing_received_by');
    }

    public function beritaAcaraUploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'berita_acara_uploaded_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PurchaseRequestDetail::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PurchaseRequestAttachment::class);
    }

    /**
     * True when every PR detail has been fully ordered (ordered_quantity >= quantity).
     */
    public function isFullyOrdered(): bool
    {
        return $this->details->every(fn($d) => $d->ordered_quantity >= $d->quantity);
    }
}
