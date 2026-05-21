<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorComparison extends Model
{
    protected $fillable = [
        'comparison_number',
        'purchase_request_id',
        'nomor_permintaan',
        'tanggal',
        'detail_barang_jasa',
        'notes',
        'status',
        'selected_vendor_id',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'tanggal'     => 'date',
        'approved_at' => 'datetime',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(VendorComparisonVendor::class)->orderBy('vendor_order');
    }

    public function selectedVendor(): BelongsTo
    {
        return $this->belongsTo(VendorComparisonVendor::class, 'selected_vendor_id');
    }
}
