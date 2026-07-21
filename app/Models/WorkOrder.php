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
        'billing_customer_id',
        'vehicle_id',
        'account_code',
        'reference_wo_id',
        'work_date',
        'deadline',
        'vehicle_info',
        'vehicle_merk',
        'vehicle_type_year',
        'vehicle_plate',
        'vehicle_km',
        'vehicle_price_tier',
        'chasis_no',
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
        'started_at'         => 'datetime',
        'completed_at'       => 'datetime',
        'vehicle_price_tier' => 'string',
    ];



    public function referenceWo(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'reference_wo_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function billingCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'billing_customer_id');
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

    public function panelLabors(): HasMany
    {
        return $this->hasMany(WorkOrderLabor::class)->whereNotNull('panel_id');
    }

    public function generalLabors(): HasMany
    {
        return $this->hasMany(WorkOrderLabor::class)->whereNotNull('labor_id');
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

    public function estimasis(): HasMany
    {
        return $this->hasMany(Estimasi::class)->orderBy('id', 'desc');
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
     * Base labor comes from master labors selected at WO creation.
     * Extra billed materials (from Bon Out) and extra priced labors are added on top.
     */
    public function calculateTotals(): void
    {
        // Billed materials from Bon Out completions (actual spare parts used)
        $extraMaterial = (float) $this->items()->whereNotNull('total_price')->sum('total_price');
        // Panel labors selected at WO creation
        $baseLabor = (float) $this->labors()->where('is_extra', false)->sum('total_price');
        // Extra priced labors added by SA after creation
        $extraLabor = (float) $this->labors()->where('is_extra', true)->sum('total_price');

        $this->material_total = $extraMaterial;
        $this->labor_total    = $baseLabor + $extraLabor;
        $this->grand_total    = $this->material_total + $this->labor_total;
        $this->save();
    }
}
