@extends('layouts.admin')

@section('title', $workOrder->wo_number)
@section('page_title', 'Work Order: ' . $workOrder->wo_number)

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $workOrder->wo_number }}</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canPrint('work_orders'))
                            <a href="{{ \URL::temporarySignedRoute('work_orders.print', now()->addMinutes(5), $workOrder) }}"
                                target="_blank" class="btn btn-info btn-sm">
                                <i class="fas fa-print"></i> Print
                            </a>
                        @endif
                        @if ($workOrder->status === 'on_progress')
                            @if (\App\Helpers\PermissionHelper::canUpdate('work_orders'))
                                <a href="{{ route('work_orders.edit', $workOrder) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            @endif
                            @if (\App\Helpers\PermissionHelper::canCreate('estimasis'))
                                <a href="{{ route('estimasis.create', ['work_order_id' => $workOrder->id]) }}"
                                    class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-file-invoice"></i> Estimasi
                                </a>
                            @endif
                            @if (\App\Helpers\PermissionHelper::canUpdate('work_orders'))
                                <form action="{{ route('work_orders.start', $workOrder) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Start work and issue materials from stock?')">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-play"></i> Start Work
                                    </button>
                                </form>
                                <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#cancelRequestModal">
                                    <i class="fas fa-times"></i> Request Cancellation
                                </button>
                            @endif
                        @elseif($workOrder->status === 'in_progress')
                            @if (\App\Helpers\PermissionHelper::canUpdate('work_orders'))
                                <a href="{{ route('work_orders.edit', $workOrder) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            @endif
                            @if (\App\Helpers\PermissionHelper::canCreate('estimasis'))
                                <a href="{{ route('estimasis.create', ['work_order_id' => $workOrder->id]) }}"
                                    class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-file-invoice"></i> Estimasi
                                </a>
                            @endif
                            @if (\App\Helpers\PermissionHelper::canCreate('bon_outs'))
                                <a href="{{ route('bon_outs.createFromWO', $workOrder) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-dolly-flatbed"></i> Create Bon Out
                                </a>
                            @endif
                            @if (\App\Helpers\PermissionHelper::canUpdate('work_orders'))
                                @php $hasIncompleteBonOuts = $workOrder->hasIncompleteBonOuts(); @endphp
                                @if ($hasIncompleteBonOuts)
                                    <button type="button" class="btn btn-secondary btn-sm" disabled
                                        title="Complete all Bon Outs before completing the Work Order">
                                        <i class="fas fa-check"></i> Complete Work
                                    </button>
                                @else
                                    <form action="{{ route('work_orders.complete', $workOrder) }}" method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('All Bon Outs are completed. Mark Work Order as completed?')">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Complete Work
                                        </button>
                                    </form>
                                @endif
                            @endif
                        @elseif($workOrder->status === 'completed')
                            @foreach ($workOrder->bonOuts as $bo)
                                <a href="{{ route('bon_outs.show', $bo) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-dolly-flatbed"></i> Bon Out #{{ $bo->bon_out_number }}
                                </a>
                            @endforeach
                            @php
                                $pf = $workOrder->proformaInvoice;
                                $inv = $workOrder->activeInvoice;
                            @endphp

                            {{-- SA: Create Proforma (only if no proforma AND no invoice yet) --}}
                            {{-- @if (\App\Helpers\PermissionHelper::canCreate('proforma_invoices') && !$pf && !$inv)
                                <a href="{{ route('proforma_invoices.create', ['work_order_id' => $workOrder->id]) }}"
                                    class="btn btn-warning btn-sm">
                                    <i class="fas fa-file-alt"></i> Create Proforma
                                </a>
                            @endif --}}

                            {{-- Proforma status link (visible to anyone who can view proformas) --}}
                            @if ($pf)
                                @php
                                    $pfBadgeColor = match ($pf->status) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'no_discount' => 'secondary',
                                        default => 'warning',
                                    };
                                    $pfLabel =
                                        $pf->status === 'no_discount'
                                            ? 'No Discount'
                                            : ucwords(str_replace('_', ' ', $pf->status));
                                @endphp
                                <a href="{{ route('proforma_invoices.show', $pf) }}"
                                    class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-file-alt"></i> Proforma
                                    <span class="badge badge-{{ $pfBadgeColor }}">{{ $pfLabel }}</span>
                                </a>
                            @endif

                            {{-- Finance: Create Invoice — locked by proforma state --}}
                            @if (\App\Helpers\PermissionHelper::canCreate('invoices') && !$inv)
                                @if (!$pf || in_array($pf->status, ['approved', 'no_discount']))
                                    {{-- No proforma (no discount) OR proforma approved: Finance can invoice --}}
                                    <a href="{{ route('invoices.create', ['work_order_id' => $workOrder->id]) }}"
                                        class="btn btn-success btn-sm">
                                        <i class="fas fa-file-invoice-dollar"></i> Create Invoice
                                    </a>
                                @elseif ($pf->status === 'pending_approval')
                                    <button type="button" class="btn btn-secondary btn-sm" disabled
                                        title="A proforma with discount is pending approval — cannot invoice until approved.">
                                        <i class="fas fa-file-invoice-dollar"></i> Create Invoice
                                        <span class="badge badge-warning">Awaiting Proforma</span>
                                    </button>
                                @elseif ($pf->status === 'rejected')
                                    <button type="button" class="btn btn-secondary btn-sm" disabled
                                        title="The proforma was rejected. SA must create a new proforma before invoicing.">
                                        <i class="fas fa-file-invoice-dollar"></i> Create Invoice
                                        <span class="badge badge-danger">Proforma Rejected</span>
                                    </button>
                                @endif
                            @endif
                        @elseif($workOrder->status === 'invoiced')
                            @php $activeInv = $workOrder->activeInvoice; @endphp
                            @if ($activeInv)
                                <a href="{{ route('invoices.show', $activeInv) }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-file-invoice-dollar"></i> View Invoice
                                </a>
                            @elseif ($workOrder->invoice)
                                {{-- All invoices cancelled — show the latest one as fallback --}}
                                <a href="{{ route('invoices.show', $workOrder->invoice) }}"
                                    class="btn btn-secondary btn-sm">
                                    <i class="fas fa-file-invoice-dollar"></i> View Invoice
                                    <span class="badge badge-danger">Cancelled</span>
                                </a>
                            @endif
                            @foreach ($workOrder->bonOuts as $bo)
                                <a href="{{ route('bon_outs.show', $bo) }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-dolly-flatbed"></i> Bon Out #{{ $bo->bon_out_number }}
                                </a>
                            @endforeach
                        @elseif($workOrder->status === 'pending_cancellation')
                            @php $sigit = \App\Models\User::where('name', 'like', '%Sigit%')->first(); @endphp
                            @if ($sigit && auth()->user()->id === $sigit->id)
                                <form action="{{ route('work_orders.approve_cancel', $workOrder) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Approve cancellation of this Work Order?')">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Approve Cancellation
                                    </button>
                                </form>
                            @endif
                        @endif
                        @include('partials.wo_status_badge', ['status' => $workOrder->status])
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Work Order Details</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>WO Number:</th>
                                    <td>{{ $workOrder->wo_number }}</td>
                                </tr>
                                <tr>
                                    <th>Work Date:</th>
                                    <td>{{ $workOrder->work_date->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Customer:</th>
                                    <td><a
                                            href="{{ route('customers.show', $workOrder->customer) }}">{{ $workOrder->customer->name }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Ditujukan Kepada:</th>
                                    <td>
                                        @if($workOrder->billingCustomer)
                                            <a href="{{ route('customers.show', $workOrder->billingCustomer) }}">{{ $workOrder->billingCustomer->name }}</a>
                                        @else
                                            <a href="{{ route('customers.show', $workOrder->customer) }}">{{ $workOrder->customer->name }}</a>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Sales Name:</th>
                                    <td>{{ $workOrder->sa_sales ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Make/Model:</th>
                                    <td>{{ $workOrder->vehicle_info ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Plate No:</th>
                                    <td>{{ $workOrder->vehicle_plate ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Odometer:</th>
                                    <td>{{ $workOrder->vehicle_km ? number_format($workOrder->vehicle_km) . ' KM' : '-' }}
                                    </td>
                                </tr>
                                @if ($workOrder->referenceWo)
                                    <tr>
                                        <th>Reference WO:</th>
                                        <td>
                                            <a href="{{ route('work_orders.show', $workOrder->referenceWo) }}">
                                                {{ $workOrder->referenceWo->wo_number }}
                                            </a>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>

                        <div class="col-md-4">
                            <h6>Panel &amp; Kendaraan</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Kisaran Harga:</th>
                                    <td>
                                        @php
                                            $tierLabels = [
                                                '0_300'   => '0 – 300 juta',
                                                '300_500' => '300 – 500 juta',
                                                '500_800' => '500 – 800 juta',
                                                '800_2000'=> '800 juta – 2 miliar',
                                            ];
                                        @endphp
                                        {{ $tierLabels[$workOrder->vehicle_price_tier] ?? '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Merk / Type:</th>
                                    <td>{{ trim(($workOrder->vehicle_merk ?? '') . ' ' . ($workOrder->vehicle_type_year ?? '')) ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>No. Polisi:</th>
                                    <td>{{ $workOrder->vehicle_plate ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Chasis No:</th>
                                    <td>{{ $workOrder->chasis_no ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-4">
                            <h6>Pricing</h6>
                            @php
                                $panelTotal    = $workOrder->panelLabors->where('is_extra', false)->sum('total_price');
                                $laborTotal    = $workOrder->generalLabors->where('is_extra', false)->sum('total_price');
                                $extraLabor    = $workOrder->generalLabors->where('is_extra', true)->sum('total_price');
                                $extraMaterial = $workOrder->items->whereNotNull('total_price')->sum('total_price');
                            @endphp
                            <table class="table table-sm table-bordered">
                                @if ($panelTotal > 0)
                                    <tr>
                                        <th>Total Panel:</th>
                                        <td class="text-right">Rp {{ number_format($panelTotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                                @if ($laborTotal > 0)
                                    <tr>
                                        <th>Total Jasa:</th>
                                        <td class="text-right">Rp {{ number_format($laborTotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                                @if ($extraMaterial > 0)
                                    <tr>
                                        <th>Sparepart:</th>
                                        <td class="text-right text-info">+ Rp {{ number_format($extraMaterial, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                                @if ($extraLabor > 0)
                                    <tr>
                                        <th>Extra Jasa:</th>
                                        <td class="text-right text-info">+ Rp {{ number_format($extraLabor, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                                @if ($workOrder->usesEstimasiDiscount() && $workOrder->estimasiDiscountAmount() > 0)
                                    <tr>
                                        <th>Grand Total:</th>
                                        <td class="text-right">Rp {{ number_format($workOrder->grand_total, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Estimasi Discount:</th>
                                        <td class="text-right text-danger">
                                            — Rp {{ number_format($workOrder->estimasiDiscountAmount(), 0, ',', '.') }}
                                            @if ($workOrder->activeEstimasi)
                                                <small class="text-muted d-block">
                                                    ({{ $workOrder->activeEstimasi->estimasi_number }}:
                                                    Panel {{ number_format($workOrder->estimasi_discount_percentage_panel, 2) }}%,
                                                    Sparepart {{ number_format($workOrder->estimasi_discount_percentage_sparepart, 2) }}%)
                                                </small>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr class="table-success">
                                        <th><strong>Discounted Total:</strong></th>
                                        <td class="text-right"><strong>Rp {{ number_format($workOrder->discountedGrandTotal(), 0, ',', '.') }}</strong></td>
                                    </tr>
                                @else
                                    <tr class="table-success">
                                        <th><strong>Grand Total:</strong></th>
                                        <td class="text-right"><strong>Rp {{ number_format($workOrder->grand_total, 0, ',', '.') }}</strong></td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>Status:</th>
                                    <td>@include('partials.wo_status_badge', ['status' => $workOrder->status])</td>
                                </tr>
                                @if ($workOrder->cancellation_reason)
                                    <tr>
                                        <th>Cancellation Reason:</th>
                                        <td>{{ $workOrder->cancellation_reason }}</td>
                                    </tr>
                                @endif
                                @if ($workOrder->started_at)
                                    <tr>
                                        <th>Start Work:</th>
                                        <td>{{ $workOrder->started_at->format('d M Y, H:i') }}</td>
                                    </tr>
                                @endif
                                @if ($workOrder->completed_at)
                                    <tr>
                                        <th>Complete Work:</th>
                                        <td>{{ $workOrder->completed_at->format('d M Y, H:i') }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if ($workOrder->description)
                        <hr>
                        <h6>Description</h6>
                        <p>{{ $workOrder->description }}</p>
                    @endif

                    <hr>
                    @php
                        $basePanels = $workOrder->panelLabors->where('is_extra', false);
                        $baseLabors = $workOrder->generalLabors->where('is_extra', false);
                    @endphp

                    {{-- Base Panels + Base Labors --}}
                    <div class="d-flex align-items-center mb-2">
                        <h6 class="mb-0">Panel yang Dikerjakan</h6>
                    </div>
                    @if ($basePanels->isNotEmpty() || $baseLabors->isNotEmpty())
                        <table class="table table-striped table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Panel</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-right">Rate (Rp)</th>
                                    <th class="text-right">Total (Rp)</th>
                                    <th>Flags</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($basePanels->concat($baseLabors) as $wol)
                                    <tr>
                                        <td>{{ $wol->panel?->panel_code ?? $wol->labor?->labor_code ?? '—' }}</td>
                                        <td>{{ $wol->description }}</td>
                                        <td class="text-center">{{ number_format($wol->qty, 0) }}</td>
                                        <td class="text-right">{{ $wol->rate ? number_format($wol->rate, 0, ',', '.') : '<span class="text-muted">—</span>' }}</td>
                                        <td class="text-right"><strong>{{ $wol->total_price ? number_format($wol->total_price, 0, ',', '.') : '—' }}</strong></td>
                                        <td>
                                            @if ($wol->is_three_coat)
                                                <span class="badge badge-info">Three Coat/Candy</span>
                                            @endif
                                            @if ($wol->is_special_repair)
                                                <span class="badge badge-warning">Special Repair</span>
                                            @endif
                                        </td>
                                        <td>{{ $wol->remarks ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted">Belum ada panel.</p>
                    @endif

                    {{-- Sparepart yang Digunakan --}}
                    <div class="d-flex align-items-center mb-2 mt-3">
                        <h6 class="mb-0">Sparepart yang Digunakan</h6>
                    </div>
                    @if ($workOrder->items->isNotEmpty())
                        <table class="table table-striped table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Sparepart</th>
                                    <th class="text-center">Qty</th>
                                    <th>UOM</th>
                                    <th class="text-right">Harga Satuan (Rp)</th>
                                    <th class="text-right">Total (Rp)</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($workOrder->items as $woItem)
                                    <tr>
                                        <td>{{ $woItem->item?->code ?? '—' }}</td>
                                        <td>{{ $woItem->item?->name ?? '—' }}</td>
                                        <td class="text-center">{{ number_format($woItem->actual_quantity ?? $woItem->demand_quantity, 2) }}</td>
                                        <td>{{ $woItem->uom?->code ?? optional($woItem->item?->smallestUom)->name ?? '-' }}</td>
                                        <td class="text-right">{{ $woItem->unit_price ? number_format($woItem->unit_price, 0, ',', '.') : '—' }}</td>
                                        <td class="text-right"><strong>{{ $woItem->total_price ? number_format($woItem->total_price, 0, ',', '.') : '—' }}</strong></td>
                                        <td>{{ $woItem->remark ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted">Belum ada sparepart yang digunakan.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ESTIMASI HISTORY ===== --}}
    @if ($workOrder->estimasis->isNotEmpty())
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-invoice mr-1"></i> Estimasi History</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Estimasi #</th>
                                    <th>Date</th>
                                    <th>Subtotal</th>
                                    <th>Discount</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($workOrder->estimasis as $est)
                                    @php $estBadge = $est->getStatusBadge(); @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('estimasis.show', $est) }}">{{ $est->estimasi_number }}</a>
                                        </td>
                                        <td>{{ $est->created_at->format('d M Y') }}</td>
                                        <td class="text-right">Rp {{ number_format($est->subtotal, 0, ',', '.') }}</td>
                                        <td class="text-right">
                                            @if ($est->discount_amount > 0)
                                                Rp {{ number_format($est->discount_amount, 0, ',', '.') }}
                                                ({{ number_format($est->discount_percentage, 1) }}%)
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-right">Rp {{ number_format($est->total, 0, ',', '.') }}</td>
                                        <td><span class="badge badge-{{ $estBadge['color'] }}">{{ $estBadge['label'] }}</span></td>
                                        <td>
                                            <a href="{{ route('estimasis.show', $est) }}" class="btn btn-info btn-xs">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== INVOICE HISTORY ===== --}}
    @if (
        $workOrder->invoices->count() > 1 ||
            ($workOrder->invoices->count() === 1 && $workOrder->invoices->first()->status === 'cancelled'))
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history mr-1"></i> Invoice History</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Amount (Rp)</th>
                                    <th>Status</th>
                                    <th>Credit Note</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($workOrder->invoices as $inv)
                                    <tr class="{{ $inv->status === 'cancelled' ? 'table-danger' : 'table-success' }}">
                                        <td>
                                            <a href="{{ route('invoices.show', $inv) }}">
                                                {{ $inv->invoice_number }}
                                            </a>
                                        </td>
                                        <td>{{ $inv->invoice_date?->format('d M Y') ?? '-' }}</td>
                                        <td class="text-right">{{ number_format($inv->grand_total, 0, ',', '.') }}</td>
                                        <td>
                                            @if ($inv->status === 'cancelled')
                                                <span class="badge badge-danger">Cancelled</span>
                                            @elseif ($inv->status === 'paid')
                                                <span class="badge badge-success">Paid</span>
                                            @else
                                                <span class="badge badge-primary">{{ ucfirst($inv->status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($inv->creditNote)
                                                <a href="{{ route('credit_notes.show', $inv->creditNote) }}"
                                                    class="badge badge-info">
                                                    {{ $inv->creditNote->credit_note_number }}
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-muted small">{{ $inv->cancellation_reason ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Cancellation Reason Modal --}}
    <div class="modal fade" id="cancelRequestModal" tabindex="-1" role="dialog" aria-labelledby="cancelRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('work_orders.cancel', $workOrder) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelRequestModalLabel">Request Work Order Cancellation</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="cancellation_reason">Reason for Cancellation <span class="text-danger">*</span></label>
                            <textarea name="cancellation_reason" id="cancellation_reason" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Submit Cancellation Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
