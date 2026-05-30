<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkOrder extends Model
{
    protected $fillable = [
        'wo_number',
        'customer_id',
        'vehicle_id',
        'package_id',
        'package_size_id',
        'account_code',
        'reference_wo_id',
        'work_date',
        'deadline',
        'vehicle_info',
        'vehicle_merk',
        'vehicle_type_year',
        'vehicle_plate',
        'vehicle_km',
        'chasis_no',
        'paket_code',
        'paket_name',
        'paket_size',
        'paket_grand_total',
        'description',
        'status',
        'labor_total',
        'material_total',
        'grand_total',
        'notes',
        'sa_sales',
        'created_by',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'work_date'     => 'date',
        'deadline'      => 'date',
        'labor_total'   => 'decimal:2',
        'material_total' => 'decimal:2',
        'grand_total'   => 'decimal:2',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];



    public function referenceWo(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'reference_wo_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function packageSize(): BelongsTo
    {
        return $this->belongsTo(PackageSize::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class);
    }

    public function labors(): HasMany
    {
        return $this->hasMany(WorkOrderLabor::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->orderBy('id', 'desc');
    }

    public function activeInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class)
            ->where('status', '!=', 'cancelled')
            ->latestOfMany();
    }

    public function proformaInvoice(): HasOne
    {
        return $this->hasOne(ProformaInvoice::class);
    }

    public function approvedProforma(): HasOne
    {
        return $this->hasOne(ProformaInvoice::class)->whereIn('status', ['approved', 'no_discount']);
    }

    public function bonOut(): HasOne
    {
        return $this->hasOne(BonOut::class);
    }

    public function bonOuts(): HasMany
    {
        return $this->hasMany(BonOut::class);
    }

    /**
     * Check if all bon outs for this work order are completed
     */
    public function allBonOutsCompleted(): bool
    {
        $bonOuts = $this->bonOuts;

        if ($bonOuts->isEmpty()) {
            return false;
        }

        return $bonOuts->every(function ($bonOut) {
            return $bonOut->status === 'completed';
        });
    }

    /**
     * Check if there are any incomplete bon outs
     */
    public function hasIncompleteBonOuts(): bool
    {
        return $this->bonOuts()->where('status', 'on_progress')->exists();
    }

    /**
     * Calculate totals from items and labor.
     * Package price goes into material_total; actual labor rows sum into labor_total.
     */
    public function calculateTotals(): void
    {
        // Extra billed materials added via Bon Out completions
        $extraMaterial = $this->items()->whereNotNull('total_price')->sum('total_price');
        // Labor total from actual labor rows
        $laborTotal = $this->labors()->whereNotNull('total_price')->sum('total_price');

        $this->material_total = (float)($this->paket_grand_total ?? 0) + (float)$extraMaterial;
        $this->labor_total    = (float)$laborTotal;
        $this->grand_total    = (float)($this->paket_grand_total ?? 0) + (float)$laborTotal + (float)$extraMaterial;
        $this->save();
    }
}
