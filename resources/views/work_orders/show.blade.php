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
                                <form action="{{ route('work_orders.cancel', $workOrder) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Cancel this Work Order? This cannot be undone.')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </form>
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
                            @if (\App\Helpers\PermissionHelper::canCreate('proforma_invoices') && !$pf && !$inv)
                                <a href="{{ route('proforma_invoices.create', ['work_order_id' => $workOrder->id]) }}"
                                    class="btn btn-warning btn-sm">
                                    <i class="fas fa-file-alt"></i> Create Proforma
                                </a>
                            @endif

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
                                        <th>Total Panel:</th>
                                        <td class="text-right">Rp {{ number_format($laborTotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                                @if ($extraMaterial > 0)
                                    <tr>
                                        <th>Extra Materials:</th>
                                        <td class="text-right text-info">+ Rp {{ number_format($extraMaterial, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                                @if ($extraLabor > 0)
                                    <tr>
                                        <th>Extra Panel:</th>
                                        <td class="text-right text-info">+ Rp {{ number_format($extraLabor, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                                <tr class="table-success">
                                    <th><strong>Grand Total:</strong></th>
                                    <td class="text-right"><strong>Rp {{ number_format($workOrder->grand_total, 0, ',', '.') }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>@include('partials.wo_status_badge', ['status' => $workOrder->status])</td>
                                </tr>
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
                        $canAddLabor =
                            $workOrder->status !== 'invoiced' &&
                            !$workOrder->proformaInvoice &&
                            \App\Helpers\PermissionHelper::canUpdate('work_orders');
                    @endphp
                    @php
                        $basePanels   = $workOrder->panelLabors->where('is_extra', false);
                        $baseLabors   = $workOrder->generalLabors->where('is_extra', false);
                        $extraLabors  = $workOrder->generalLabors->where('is_extra', true);
                    @endphp

                    {{-- Base Panels --}}
                    <div class="d-flex align-items-center mb-2">
                        <h6 class="mb-0">Panel yang Dikerjakan</h6>
                    </div>
                    @if ($basePanels->isNotEmpty())
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
                                @foreach ($basePanels as $wol)
                                    <tr>
                                        <td>{{ $wol->panel?->panel_code ?? '—' }}</td>
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

                    {{-- Base Labors --}}
                    <div class="d-flex align-items-center mb-2 mt-3">
                        <h6 class="mb-0">Panel yang Dikerjakan</h6>
                    </div>
                    @if ($baseLabors->isNotEmpty())
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
                                @foreach ($baseLabors as $wol)
                                    <tr>
                                        <td>{{ $wol->labor?->labor_code ?? '—' }}</td>
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

                    {{-- Extra Panels --}}
                    <div class="d-flex align-items-center mb-2 mt-3">
                        <h6 class="mb-0 mr-3">Extra Panel</h6>
                        @if ($canAddLabor)
                            <button type="button" class="btn btn-warning btn-sm" data-toggle="modal"
                                data-target="#addLaborModal">
                                <i class="fas fa-plus"></i> Add Extra Panel
                            </button>
                        @endif
                    </div>
                    @if ($extraLabors->isNotEmpty())
                        <table class="table table-striped table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-right">Rate (Rp)</th>
                                    <th class="text-right">Total (Rp)</th>
                                    <th>Remarks</th>
                                    @if ($canAddLabor)
                                        <th style="width:50px"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($extraLabors as $wol)
                                    <tr class="table-warning">
                                        <td>{{ $wol->labor?->labor_code ?? '—' }}</td>
                                        <td>{{ $wol->description }}</td>
                                        <td class="text-center">{{ number_format($wol->qty, 2) }}</td>
                                        <td class="text-right">{{ $wol->rate ? number_format($wol->rate, 0, ',', '.') : '<span class="text-muted">Fixed</span>' }}</td>
                                        <td class="text-right"><strong>{{ $wol->total_price ? number_format($wol->total_price, 0, ',', '.') : '—' }}</strong></td>
                                        <td>{{ $wol->remarks ?? '-' }}</td>
                                        @if ($canAddLabor)
                                            <td>
                                                <form action="{{ route('work_orders.remove_labor', [$workOrder, $wol]) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Remove this extra panel?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-danger btn-xs"><i class="fas fa-times"></i></button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                            @php $totalExtraLabor = $extraLabors->sum('total_price'); @endphp
                            @if ($totalExtraLabor > 0)
                                <tfoot>
                                    <tr class="table-success">
                                        <td colspan="{{ $canAddLabor ? 4 : 3 }}" class="text-right font-weight-bold">Extra Panel Total:</td>
                                        <td class="text-right font-weight-bold">{{ number_format($totalExtraLabor, 0, ',', '.') }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    @else
                        <p class="text-muted">Tidak ada extra panel.</p>
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

    {{-- ===== ADD PANEL MODAL ===== --}}
    @if ($canAddLabor)
        <div class="modal fade" id="addLaborModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form action="{{ route('work_orders.add_labor', $workOrder) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-hard-hat"></i> Add Extra Panel to
                                {{ $workOrder->wo_number }}</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            @if (session('error'))
                                <div class="alert alert-danger">{{ session('error') }}</div>
                            @endif

                            <div class="form-group">
                                <label>Panel <span class="text-danger">*</span></label>
                                <select name="labor_id" id="laborSelect" class="form-control select2" required>
                                    <option value="">— Pilih Panel —</option>
                                    @foreach ($masterLabors as $ml)
                                        <option value="{{ $ml->id }}"
                                            data-price="{{ (float) $ml->price }}"
                                            data-p0300="{{ (float) $ml->price_0_300 }}"
                                            data-p300500="{{ (float) $ml->price_300_500 }}"
                                            data-p500800="{{ (float) $ml->price_500_800 }}"
                                            data-p8002000="{{ (float) $ml->price_800_2000 }}">
                                            {{ $ml->labor_code }} — {{ $ml->description }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Qty <span class="text-danger">*</span></label>
                                        <input type="number" name="qty" id="laborQty" class="form-control"
                                            value="1" min="0.01" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Unit Price (Rp) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" name="rate" id="laborRate" class="form-control"
                                                value="" min="0" step="1" readonly required>
                                        </div>
                                        <small class="text-muted">Auto-filled based on Kisaran Harga Kendaraan.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Total</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                    <input type="text" id="laborTotal" class="form-control" readonly value="0">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Remarks</label>
                                <input type="text" name="remarks" class="form-control" placeholder="Optional notes">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Add Extra Labor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                $(function() {
                    const $sel = $('#laborSelect');
                    const $qty = $('#laborQty');
                    const $rate = $('#laborRate');
                    const $tot = $('#laborTotal');

                    const tierMap = {
                        '0_300': 'p0300',
                        '300_500': 'p300500',
                        '500_800': 'p500800',
                        '800_2000': 'p8002000'
                    };
                    const priceTier = @json($workOrder->vehicle_price_tier);
                    const tierKey = tierMap[priceTier] || null;

                    $sel.select2({
                        theme: 'bootstrap4',
                        dropdownParent: $('#addLaborModal'),
                        width: '100%'
                    });

                    function recalc() {
                        const q = parseFloat($qty.val()) || 0;
                        const r = parseFloat($rate.val()) || 0;
                        $tot.val(Math.round(q * r).toLocaleString('id-ID'));
                    }

                    function fillPrice() {
                        const opt = $sel.find(':selected')[0];
                        if (opt && opt.value) {
                            let price = 0;
                            if (tierKey && opt.dataset[tierKey] && parseFloat(opt.dataset[tierKey]) > 0) {
                                price = parseFloat(opt.dataset[tierKey]);
                            } else {
                                price = parseFloat(opt.dataset.price) || 0;
                            }
                            $rate.val(price);
                            recalc();
                        } else {
                            $rate.val('');
                            $tot.val('0');
                        }
                    }

                    $sel.on('change', fillPrice);
                    $qty.add($rate).on('input', recalc);

                    {{-- Re-open modal if there was a validation error --}}
                    @if (session('error'))
                        $('#addLaborModal').modal('show');
                    @endif
                });
            </script>
        @endpush
    @endif
@endsection
