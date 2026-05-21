@extends('layouts.admin')

@section('title', $purchaseOrder->po_number)
@section('page_title', 'Purchase Order: ' . $purchaseOrder->po_number)

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $purchaseOrder->po_number }}</h3>
                    @php
                        $statusLabel = match ($purchaseOrder->status) {
                            'on_progress' => 'On Progress',
                            'closed_shortage' => 'Closed with Shortage',
                            'completed' => 'Completed',
                            default => ucfirst(str_replace('_', ' ', $purchaseOrder->status)),
                        };
                        $statusColor = match ($purchaseOrder->status) {
                            'received' => 'success',
                            'completed' => 'success',
                            'approved' => 'info',
                            'partial' => 'warning',
                            'closed_shortage' => 'dark',
                            'cancelled' => 'danger',
                            default => 'secondary',
                        };
                        $hasOpenReceiptLines = $purchaseOrder->details->contains(
                            fn($detail) => $detail->getOpenQuantity() > 0,
                        );
                        $hasBillableLines = $purchaseOrder->details->contains(
                            fn($detail) => $detail->getRemainingBillableQuantity() > 0,
                        );
                    @endphp
                    <div class="card-tools">
                        @if (in_array($purchaseOrder->status, ['approved', 'partial', 'received', 'completed', 'closed_shortage', 'printed']))
                            @if (\App\Helpers\PermissionHelper::canPrint('purchase_orders'))
                                <a href="{{ \URL::temporarySignedRoute('purchase_orders.print', now()->addMinutes(5), $purchaseOrder) }}"
                                    class="btn btn-secondary btn-sm" target="_blank">
                                    <i class="fas fa-print"></i>
                                    {{ $purchaseOrder->printed_at ? 'Reprint' : 'Print' }}
                                </a>
                            @endif
                            @if ($purchaseOrder->printed_at)
                                <span class="badge badge-primary"
                                    title="Printed on {{ $purchaseOrder->printed_at->format('d M Y H:i') }}">
                                    <i class="fas fa-check-circle"></i> Printed
                                    {{ $purchaseOrder->printed_at->format('d M Y') }}
                                </span>
                            @endif
                        @endif
                        @if (
                            $purchaseOrder->status === 'on_progress' &&
                                auth()->user()->hasAnyRole(['purchasing', 'admin', 'super_admin']))
                            <a href="{{ route('purchase_orders.edit', $purchaseOrder) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit PO
                            </a>
                        @endif
                        @if ($purchaseOrder->status === 'on_progress')
                            @php
                                $amountThreshold = 5000000;
                                $canApprove = false;

                                if ($purchaseOrder->total_amount > $amountThreshold) {
                                    // Amount > 5,000,000: Only Director can approve
                                    $canApprove = auth()
                                        ->user()
                                        ->hasAnyRole(['director', 'super_admin']);
                                } else {
                                    // Amount <= 5,000,000: Only Manager (not Director) can approve
                                    $canApprove = auth()
                                        ->user()
                                        ->hasAnyRole(['manager', 'super_admin']);
                                }
                                $canApprove = $canApprove && $purchaseOrder->created_by !== auth()->id();
                            @endphp
                            @if ($canApprove)
                                <form action="{{ route('purchase_orders.approve', $purchaseOrder) }}" method="POST"
                                    class="d-inline" onsubmit="return confirm('Are you sure you want to approve this PO?')">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                            @else
                                <span class="badge badge-warning">
                                    @if ($purchaseOrder->total_amount > 5000000)
                                        Director approval required (>Rp 5,000,000)
                                    @else
                                        Manager approval required (≤Rp 5,000,000) — Director cannot approve
                                    @endif
                                </span>
                            @endif
                        @endif
                        @php
                            $canCancelPo = false;
                            $hasDownstreamProcess =
                                !empty($purchaseOrder->invoice_number) ||
                                $purchaseOrder->receivables()->exists() ||
                                $purchaseOrder->purchaseInvoices()->exists();

                            if (in_array($purchaseOrder->status, ['on_progress', 'approved'])) {
                                $user = auth()->user();

                                if ($user->hasAnyRole(['admin', 'super_admin'])) {
                                    $canCancelPo = true;
                                } elseif ($purchaseOrder->status === 'on_progress') {
                                    $canCancelPo =
                                        $user->hasAnyRole(['manager', 'director']) ||
                                        ($user->hasAnyRole(['purchasing']) &&
                                            $purchaseOrder->created_by === auth()->id());
                                } elseif ($purchaseOrder->status === 'approved' && !$hasDownstreamProcess) {
                                    $canCancelPo =
                                        $user->hasAnyRole(['director', 'manager']) ||
                                        $purchaseOrder->approved_by === auth()->id();
                                }
                            }
                        @endphp
                        @if ($canCancelPo)
                            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal"
                                data-target="#cancelPoModal">
                                <i class="fas fa-ban"></i> Cancel
                            </button>
                        @endif
                        @if (in_array($purchaseOrder->status, ['approved', 'partial']) && $hasOpenReceiptLines)
                            @if (auth()->user()->hasAnyRole(['warehouse', 'admin', 'super_admin']))
                                @if ($purchaseOrder->po_type === 'purchase_order')
                                    <a href="{{ route('receivables.create', ['po_id' => $purchaseOrder->id]) }}"
                                        class="btn btn-success btn-sm">
                                        <i class="fas fa-dolly"></i> Create Bon In
                                    </a>
                                @else
                                    <span class="badge badge-info" title="Service orders don't require Bon In">
                                        <i class="fas fa-info-circle"></i> PPJ (Service) - No Bon In needed
                                    </span>
                                @endif
                            @endif
                            @if (auth()->user()->hasAnyRole(['purchasing', 'admin', 'super_admin']))
                                <button type="button" class="btn btn-dark btn-sm" data-toggle="modal"
                                    data-target="#closeRemainingModal">
                                    <i class="fas fa-layer-group"></i> Close Remaining
                                </button>
                            @endif
                        @endif
                        @if (in_array($purchaseOrder->status, ['partial', 'received']) &&
                                $hasBillableLines &&
                                auth()->user()->hasAnyRole(['purchasing', 'admin', 'super_admin']))
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                                data-target="#recordInvoiceModal">
                                <i class="fas fa-file-invoice"></i> Record Invoice
                            </button>
                        @elseif (in_array($purchaseOrder->status, ['closed_shortage']) &&
                                $hasBillableLines &&
                                auth()->user()->hasAnyRole(['purchasing', 'admin', 'super_admin']))
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                                data-target="#recordInvoiceModal">
                                <i class="fas fa-file-invoice"></i> Record Invoice
                            </button>
                        @endif
                        @if (in_array($purchaseOrder->status, ['partial', 'received', 'closed_shortage']) &&
                                $purchaseOrder->purchaseInvoices()->exists() &&
                                auth()->user()->hasAnyRole(['purchasing', 'admin', 'super_admin']))
                            <form action="{{ route('purchase_orders.complete', $purchaseOrder) }}" method="POST"
                                class="d-inline"
                                onsubmit="return confirm('Mark this PO as Completed? This action cannot be undone.')">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-check-double"></i> Complete PO
                                </button>
                            </form>
                        @endif <span class="badge badge-{{ $statusColor }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Order Details</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>PO Number:</th>
                                    <td>{{ $purchaseOrder->po_number }}</td>
                                </tr>
                                @if ($purchaseOrder->purchaseRequest)
                                    <tr>
                                        <th>PPB Number:</th>
                                        <td>
                                            <a
                                                href="{{ route('purchase_requests.show', $purchaseOrder->purchaseRequest) }}">
                                                {{ $purchaseOrder->purchaseRequest->pr_number }}
                                            </a>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>Order Date:</th>
                                    <td>{{ $purchaseOrder->order_date->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Expected Delivery:</th>
                                    <td>{{ $purchaseOrder->expected_delivery_date?->format('M d, Y') ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td><span class="badge badge-{{ $statusColor }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                </tr>
                                @if ($purchaseOrder->status === 'cancelled' && $purchaseOrder->cancellation_reason)
                                    <tr>
                                        <th>Cancellation Reason:</th>
                                        <td><span class="text-danger">{{ $purchaseOrder->cancellation_reason }}</span></td>
                                    </tr>
                                @endif
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6>Supplier Details</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Name:</th>
                                    <td>
                                        @if ($purchaseOrder->supplier)
                                            <a href="{{ route('suppliers.show', $purchaseOrder->supplier) }}">
                                                {{ $purchaseOrder->supplier->name }}
                                            </a>
                                        @else
                                            {{ $purchaseOrder->supplier_name }}
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td>{{ $purchaseOrder->supplier->phone ?? ($purchaseOrder->supplier_phone ?? '-') }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Address:</th>
                                    <td>{{ $purchaseOrder->supplier->address ?? ($purchaseOrder->supplier_address ?? '-') }}
                                    </td>
                                </tr>
                                @if (\App\Helpers\PermissionHelper::canViewPrices())
                                    <tr>
                                        <th>Total Amount:</th>
                                        <td><strong>Rp
                                                {{ number_format($purchaseOrder->total_amount, 0, ',', '.') }}</strong>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>Expected Delivery:</th>
                                    <td>{{ $purchaseOrder->expected_delivery_date ? $purchaseOrder->expected_delivery_date->format('M d, Y') : '-' }}
                                    </td>
                                </tr>
                                @if ($purchaseOrder->po_type === 'service_order')
                                    <tr>
                                        <th>Service Location:</th>
                                        <td>{{ $purchaseOrder->lokasi_pengerjaan ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Service Duration:</th>
                                        <td>{{ $purchaseOrder->waktu_pengerjaan ?? '-' }}</td>
                                    </tr>
                                @else
                                    <tr>
                                        <th>Delivery Location:</th>
                                        <td>{{ $purchaseOrder->lokasi_pengiriman ?? '-' }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>Payment Method:</th>
                                    <td>
                                        @php
                                            $methodLabels = [
                                                'credit' => 'Credit',
                                                'cbd' => 'CBD (Cash Before Delivery)',
                                                'dp' => 'DP (Down Payment)',
                                            ];
                                            $pembayaranLabels = [
                                                'tunai' => 'Tunai (Cash)',
                                                'non_tunai' => 'Non-Tunai (Transfer)',
                                            ];
                                            $method = $purchaseOrder->payment_method;
                                            $pem = $purchaseOrder->pembayaran;
                                        @endphp
                                        {{ $methodLabels[$method] ?? strtoupper($method ?? '-') }}
                                        @if ($pem && $method !== 'credit')
                                            &mdash; {{ $pembayaranLabels[$pem] ?? ucfirst($pem) }}
                                        @endif
                                    </td>
                                </tr>
                                @if ($purchaseOrder->payment_terms)
                                    <tr>
                                        <th style="vertical-align:top;">Syarat Pembayaran:</th>
                                        <td>
                                            <ul class="mb-0 pl-3">
                                                @foreach (explode("\n", trim($purchaseOrder->payment_terms)) as $term)
                                                    @if (trim($term))
                                                        <li>{{ trim($term) }}</li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>

                        @if ($purchaseOrder->purchaseInvoices->isNotEmpty() && \App\Helpers\PermissionHelper::canViewPrices())
                            <div class="col-md-12 mt-3">
                                <div class="card card-info card-outline">
                                    <div class="card-header py-2">
                                        <h6 class="card-title mb-0"><i class="fas fa-file-invoice mr-1"></i> Supplier
                                            Invoices</h6>
                                    </div>
                                    <div class="card-body py-2">
                                        @foreach ($purchaseOrder->purchaseInvoices as $invoice)
                                            <div class="border rounded p-3 mb-3">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <table class="table table-sm table-borderless mb-0">
                                                            <tr>
                                                                <th style="width:150px;">Invoice Number:</th>
                                                                <td><strong>{{ $invoice->invoice_number }}</strong></td>
                                                            </tr>
                                                            <tr>
                                                                <th>Supplier:</th>
                                                                <td>{{ $invoice->supplier->name ?? ($invoice->supplier_name ?? '-') }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Invoice Date:</th>
                                                                <td>{{ $invoice->invoice_date?->format('d M Y') ?? '-' }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <th>Due Date:</th>
                                                                <td>{{ $invoice->due_date?->format('d M Y') ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Total:</th>
                                                                <td><strong>Rp
                                                                        {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <table class="table table-sm table-borderless mb-0">
                                                            <tr>
                                                                <th style="width:150px;">Recorded By:</th>
                                                                <td>{{ $invoice->recorder->name ?? '-' }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Recorded At:</th>
                                                                <td>{{ $invoice->recorded_at?->format('d M Y H:i') ?? '-' }}
                                                                </td>
                                                            </tr>
                                                            @if ($invoice->notes)
                                                                <tr>
                                                                    <th style="vertical-align:top;">Notes:</th>
                                                                    <td>{{ $invoice->notes }}</td>
                                                                </tr>
                                                            @endif
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="table-responsive mt-2">
                                                    <table class="table table-sm table-bordered mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>Item</th>
                                                                <th>UOM</th>
                                                                <th>Qty Billed</th>
                                                                <th>Unit Price</th>
                                                                <th>Line Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($invoice->lines as $line)
                                                                <tr>
                                                                    <td>{{ $line->purchaseOrderDetail->item->name ?? '-' }}
                                                                    </td>
                                                                    <td>{{ $line->purchaseOrderDetail->uom->code ?? '-' }}
                                                                    </td>
                                                                    <td>{{ number_format($line->qty_billed, 2) }}</td>
                                                                    <td>Rp
                                                                        {{ number_format($line->unit_price, 2, ',', '.') }}
                                                                    </td>
                                                                    <td>Rp
                                                                        {{ number_format($line->line_total, 0, ',', '.') }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @elseif ($purchaseOrder->invoice_number)
                            <div class="col-md-12 mt-3">
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle"></i> Legacy invoice data exists on this PO:
                                    <strong>{{ $purchaseOrder->invoice_number }}</strong>.
                                </div>
                            </div>
                        @endif
                    </div>

                    <hr>
                    <h6>Signature Approvals</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>Step:</th>
                            <th>User:</th>
                            <th>Signature:</th>
                        </tr>
                        <!-- Created By -->
                        <tr>
                            <td><strong>Created By:</strong></td>
                            <td>{{ $purchaseOrder->creator->name }}</td>
                            <td>
                                @if ($purchaseOrder->creator->signature_path)
                                    <img src="{{ route('users.signature', $purchaseOrder->creator) }}" alt="Signature"
                                        style="max-width: 80px; max-height: 40px;">
                                @else
                                    <span class="text-muted text-sm">-</span>
                                @endif
                            </td>
                        </tr>

                        <!-- Approved By -->
                        <tr>
                            <td><strong>Approved By:</strong></td>
                            <td>
                                @if ($purchaseOrder->approver)
                                    {{ $purchaseOrder->approver->name }}
                                    <br><small
                                        class="text-muted">{{ $purchaseOrder->approved_at->format('M d, Y H:i') }}</small>
                                @else
                                    <span class="text-muted">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if ($purchaseOrder->approver && $purchaseOrder->approver->signature_path)
                                    <img src="{{ route('users.signature', $purchaseOrder->approver) }}" alt="Signature"
                                        style="max-width: 80px; max-height: 40px;">
                                @else
                                    <span class="text-muted text-sm">-</span>
                                @endif
                            </td>
                        </tr>

                    </table>

                    @php $canViewPrices = \App\Helpers\PermissionHelper::canViewPrices(); @endphp
                    <hr>
                    <h6>Items</h6>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                @if ($purchaseOrder->po_type === 'service_order')
                                    <th>Service Description</th>
                                    <th>Quantity</th>
                                    @if ($canViewPrices)
                                        <th>Unit Price</th>
                                        <th>Total Price</th>
                                    @endif
                                @else
                                    <th>Item</th>
                                    <th>UOM</th>
                                    <th>Quantity</th>
                                    @if ($canViewPrices)
                                        <th>Unit Price</th>
                                        <th>Total Price</th>
                                    @endif
                                    <th>Received</th>
                                    <th>Closed Shortage</th>
                                    <th>Open Qty</th>
                                    <th>Invoiced Qty</th>
                                    <th>Line Status</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchaseOrder->details as $detail)
                                <tr>
                                    @if ($purchaseOrder->po_type === 'service_order')
                                        <td><strong>{{ $detail->service_description }}</strong></td>
                                        <td>{{ number_format($detail->quantity, 2) }}</td>
                                        @if ($canViewPrices)
                                            <td>Rp {{ number_format($detail->unit_price, 2, ',', '.') }}</td>
                                            <td><strong>Rp {{ number_format($detail->total_price, 0, ',', '.') }}</strong>
                                            </td>
                                        @endif
                                    @else
                                        <td>{{ $detail->item->name }}</td>
                                        <td>{{ $detail->uom->code }}</td>
                                        <td>{{ number_format($detail->quantity, 2) }}</td>
                                        @if ($canViewPrices)
                                            <td>Rp {{ number_format($detail->unit_price, 2, ',', '.') }}</td>
                                            <td>Rp {{ number_format($detail->total_price, 0, ',', '.') }}</td>
                                        @endif
                                        <td>{{ number_format($detail->received_quantity, 2) }}</td>
                                        <td>{{ number_format($detail->closed_shortage_quantity ?? 0, 2) }}</td>
                                        <td>{{ number_format($detail->getOpenQuantity(), 2) }}</td>
                                        <td>{{ number_format($detail->getBilledQuantity(), 2) }}</td>
                                        <td>
                                            @php
                                                $lineBadge = match ($detail->line_status) {
                                                    'received_full' => 'success',
                                                    'closed_shortage' => 'dark',
                                                    'partial' => 'warning',
                                                    default => 'secondary',
                                                };
                                                $lineLabel = match ($detail->line_status) {
                                                    'received_full' => 'Received Full',
                                                    'closed_shortage' => 'Closed Shortage',
                                                    'partial' => 'Partial',
                                                    default => 'Open',
                                                };
                                            @endphp
                                            <span class="badge badge-{{ $lineBadge }}">{{ $lineLabel }}</span>
                                            @if ($detail->shortage_close_reason)
                                                <div><small
                                                        class="text-muted">{{ $detail->shortage_close_reason }}</small>
                                                </div>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @php
                        $subTotal = $purchaseOrder->total_amount ?? 0;
                        $miscCost = $purchaseOrder->miscCosts->sum('amount');
                        $ppn =
                            $purchaseOrder->include_ppn && $purchaseOrder->po_type === 'purchase_order'
                                ? ($subTotal + $miscCost) * 0.11
                                : 0;
                        $pph = 0;
                        if ($purchaseOrder->po_type === 'service_order') {
                            if ($purchaseOrder->pph_type === 'pph_21') {
                                $pph = $subTotal * 0.025;
                            } elseif ($purchaseOrder->pph_type === 'pph_23') {
                                $pph = $subTotal * 0.02;
                            }
                        }
                        $grandTotal = $subTotal + $miscCost + $ppn - $pph;
                    @endphp

                    @php
                        $canViewFinancialSummary = auth()
                            ->user()
                            ->hasAnyRole([
                                'super_admin',
                                'admin',
                                'director',
                                'manager',
                                'purchasing',
                                'accounting',
                                'audit',
                            ]);
                    @endphp

                    @if ($canViewFinancialSummary)
                        <div class="row mt-3">
                            <div class="col-md-6 offset-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th>Subtotal:</th>
                                        <td class="text-right"><strong>Rp
                                                {{ number_format($subTotal, 0, ',', '.') }}</strong>
                                        </td>
                                    </tr>
                                    @if ($miscCost > 0)
                                        <tr>
                                            <th>Lain-lain (Total):</th>
                                            <td class="text-right"><strong>Rp
                                                    {{ number_format($miscCost, 0, ',', '.') }}</strong></td>
                                        </tr>
                                    @endif
                                    @foreach ($purchaseOrder->miscCosts as $misc)
                                        <tr>
                                            <th class="text-muted font-weight-normal pl-3">↳ {{ $misc->description }}:
                                            </th>
                                            <td class="text-right">Rp {{ number_format($misc->amount, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    @if ($purchaseOrder->po_type === 'purchase_order' && $purchaseOrder->include_ppn)
                                        <tr>
                                            <th>PPN (11%):</th>
                                            <td class="text-right"><strong>Rp
                                                    {{ number_format($ppn, 0, ',', '.') }}</strong>
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($purchaseOrder->po_type === 'service_order' && $pph > 0)
                                        <tr>
                                            <th>{{ $purchaseOrder->pph_type === 'pph_21' ? 'PPh 21 (2.5%)' : 'PPh 23 (2%)' }}:
                                            </th>
                                            <td class="text-right"><strong>Rp
                                                    {{ number_format($pph, 0, ',', '.') }}</strong>
                                            </td>
                                        </tr>
                                    @endif
                                    <tr style="border-top: 2px solid #333;">
                                        <th>Grand Total:</th>
                                        <td class="text-right" style="font-size: 16px; color: #cc0000;"><strong>Rp
                                                {{ number_format($grandTotal, 0, ',', '.') }}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @if (in_array($purchaseOrder->status, ['approved', 'partial']) &&
            $hasOpenReceiptLines &&
            auth()->user()->hasAnyRole(['purchasing', 'admin', 'super_admin']))
        <div class="modal fade" id="closeRemainingModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <form action="{{ route('purchase_orders.close_remaining', $purchaseOrder) }}" method="POST">
                        @csrf
                        <div class="modal-header bg-dark text-white">
                            <h5 class="modal-title"><i class="fas fa-layer-group"></i> Close Remaining Quantities</h5>
                            <button type="button" class="close text-white"
                                data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-warning">
                                Use this when supplier will not deliver the remaining quantity anymore.
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>UOM</th>
                                            <th>Open Qty</th>
                                            <th>Close Qty</th>
                                            <th>Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($purchaseOrder->details->filter(fn($detail) => $detail->getOpenQuantity() > 0) as $detail)
                                            <tr>
                                                <td>{{ $detail->item->name }}</td>
                                                <td>{{ $detail->uom->code }}</td>
                                                <td>{{ number_format($detail->getOpenQuantity(), 2) }}</td>
                                                <td>
                                                    <input type="hidden"
                                                        name="lines[{{ $detail->id }}][purchase_order_detail_id]"
                                                        value="{{ $detail->id }}">
                                                    <input type="number"
                                                        name="lines[{{ $detail->id }}][close_quantity]"
                                                        class="form-control" min="0"
                                                        max="{{ $detail->getOpenQuantity() }}" step="0.01"
                                                        value="0">
                                                </td>
                                                <td>
                                                    <textarea name="lines[{{ $detail->id }}][reason]" class="form-control" rows="2"
                                                        placeholder="Reason why remaining qty is closed"></textarea>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-dark"><i class="fas fa-check"></i> Confirm
                                Close</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
    {{-- Cancel Modal --}}
    <div class="modal fade" id="cancelPoModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('purchase_orders.cancel', $purchaseOrder) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-ban"></i> Cancel Purchase Order</h5>
                        <button type="button" class="close text-white"
                            data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to cancel <strong>{{ $purchaseOrder->po_number }}</strong>?</p>
                        <div class="form-group">
                            <label for="cancellation_reason">Cancellation Reason <span
                                    class="text-danger">*</span></label>
                            <textarea name="cancellation_reason" id="cancellation_reason" class="form-control" rows="3"
                                placeholder="e.g., Supplier unavailable, budget cut, wrong item ordered, etc." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> Confirm Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Record Invoice Modal --}}
    <div class="modal fade" id="recordInvoiceModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form action="{{ route('purchase_orders.record_invoice', $purchaseOrder) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-file-invoice"></i> Record Supplier Invoice</h5>
                        <button type="button" class="close text-white"
                            data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Supplier is auto-populated from this PO:
                            <strong>{{ $purchaseOrder->supplier->name ?? $purchaseOrder->supplier_name }}</strong>
                        </div>
                        <div class="form-group">
                            <label for="invoice_number">Invoice Number <span class="text-danger">*</span></label>
                            <input type="text" name="invoice_number" id="invoice_number" class="form-control"
                                placeholder="e.g., INV-2026-001" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="invoice_date">Invoice Date <span class="text-danger">*</span></label>
                                    <input type="date" name="invoice_date" id="invoice_date" class="form-control"
                                        value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="invoice_due_date">Due Date</label>
                                    <input type="date" name="invoice_due_date" id="invoice_due_date"
                                        class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="invoice_notes">Notes <small class="text-muted">(optional)</small></label>
                            <textarea name="invoice_notes" id="invoice_notes" class="form-control" rows="3"
                                placeholder="e.g., Payment terms, delivery notes, etc."></textarea>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>UOM</th>
                                        <th>Remaining Billable Qty</th>
                                        <th>Qty Billed</th>
                                        <th>Unit Price</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($purchaseOrder->details->filter(fn($detail) => $detail->getRemainingBillableQuantity() > 0) as $detail)
                                        <tr>
                                            <td>{{ $detail->item->name }}</td>
                                            <td>{{ $detail->uom->code }}</td>
                                            <td>{{ number_format($detail->getRemainingBillableQuantity(), 2) }}</td>
                                            <td>
                                                <input type="hidden"
                                                    name="lines[{{ $detail->id }}][purchase_order_detail_id]"
                                                    value="{{ $detail->id }}">
                                                <input type="number" name="lines[{{ $detail->id }}][qty_billed]"
                                                    class="form-control" min="0"
                                                    max="{{ $detail->getRemainingBillableQuantity() }}" step="0.01"
                                                    value="0">
                                            </td>
                                            <td>
                                                <input type="number" name="lines[{{ $detail->id }}][unit_price]"
                                                    class="form-control" min="0" step="0.01"
                                                    value="{{ number_format($detail->unit_price, 2, '.', '') }}">
                                            </td>
                                            <td>
                                                <input type="text" name="lines[{{ $detail->id }}][notes]"
                                                    class="form-control" placeholder="Optional line note">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Record
                            Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
