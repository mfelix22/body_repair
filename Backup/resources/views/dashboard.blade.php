@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline card-primary mb-0">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Operational Overview</h5>
                            <p class="text-muted mb-0">Snapshot as of {{ now()->format('d M Y H:i') }}</p>
                        </div>
                        <div class="mt-2 mt-md-0">
                            @if (auth()->user()->hasAnyRole(['warehouse', 'admin', 'super_admin', 'director']))
                                <a href="{{ route('work_orders.create') }}" class="btn btn-primary btn-sm mr-1">
                                    <i class="fas fa-plus"></i> New Work Order
                                </a>
                            @endif
                            @if (auth()->user()->hasAnyRole(['purchasing', 'admin', 'super_admin', 'director']))
                                <a href="{{ route('purchase_orders.create') }}" class="btn btn-info btn-sm mr-1">
                                    <i class="fas fa-plus"></i> New Order
                                </a>
                            @endif
                            @if (auth()->user()->hasAnyRole(['warehouse', 'admin', 'super_admin', 'director']))
                                <a href="{{ route('bon_outs.createStandalone') }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus"></i> New Bon Out
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $summary['purchase_requests_pending'] }}</h3>
                    <p>Pending PPB & PPJ</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <a href="{{ route('purchase_requests.index') }}" class="small-box-footer">
                    View Details <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $summary['purchase_orders_open'] }}</h3>
                    <p>Open PO & SO</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <a href="{{ route('purchase_orders.index') }}" class="small-box-footer">
                    View Details <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $summary['open_work_orders'] }}</h3>
                    <p>Active Work Orders</p>
                </div>
                <div class="icon">
                    <i class="fas fa-tools"></i>
                </div>
                <a href="{{ route('work_orders.index') }}" class="small-box-footer">
                    View Details <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $summary['outstanding_invoices'] }}</h3>
                    <p>Outstanding Invoices</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <a href="{{ route('invoices.index') }}" class="small-box-footer">
                    View Details <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-database mr-1"></i> Master Data Summary</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td>Customers</td>
                                <td class="text-right"><strong>{{ $entityCounts['customers'] }}</strong></td>
                            </tr>
                            <tr>
                                <td>Suppliers</td>
                                <td class="text-right"><strong>{{ $entityCounts['suppliers'] }}</strong></td>
                            </tr>
                            <tr>
                                <td>Items</td>
                                <td class="text-right"><strong>{{ $entityCounts['items'] }}</strong></td>
                            </tr>
                            <tr>
                                <td>Total Work Orders</td>
                                <td class="text-right"><strong>{{ $entityCounts['work_orders'] }}</strong></td>
                            </tr>
                            <tr>
                                <td>Total Bon Out</td>
                                <td class="text-right"><strong>{{ $entityCounts['bon_outs'] }}</strong></td>
                            </tr>
                            <tr>
                                <td>Total Invoices</td>
                                <td class="text-right"><strong>{{ $entityCounts['invoices'] }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ($statusSections as $section)
            <div class="col-lg-6">
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title">{{ $section['title'] }}</h3>
                    </div>
                    <div class="card-body">
                        @foreach ($section['items'] as $status)
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>
                                    <span class="badge badge-{{ $status['class'] }} mr-1">{{ $status['label'] }}</span>
                                </span>
                                <span><strong>{{ $status['count'] }}</strong></span>
                            </div>
                            <div class="progress mb-3" style="height: 6px;">
                                <div class="progress-bar bg-{{ $status['class'] }}" role="progressbar"
                                    style="width: {{ $status['percentage'] }}%"
                                    aria-valuenow="{{ $status['percentage'] }}" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Stock Alerts</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-right">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lowStockItems as $item)
                                <tr>
                                    <td>
                                        {{ $item->code }} - {{ $item->name }}<br>
                                        <small class="text-muted">Min
                                            {{ rtrim(rtrim(number_format($item->reorder_level, 2, '.', ''), '0'), '.') }}</small>
                                    </td>
                                    <td class="text-right">
                                        <span class="badge badge-danger">
                                            {{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') }}
                                            {{ $item->smallestUom->code ?? '' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">No low stock alerts</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if (\App\Helpers\PermissionHelper::canView('stocks'))
                    <div class="card-footer">
                        <a href="{{ route('stocks.index') }}" class="btn btn-warning btn-sm">Open Stock Page</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-stream mr-1"></i> Recent Stock Transactions</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Item</th>
                                <th>Type</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentStockTransactions as $trx)
                                <tr>
                                    <td>{{ $trx->created_at->format('d M H:i') }}</td>
                                    <td>{{ $trx->item->code ?? '-' }}</td>
                                    <td>
                                        @php
                                            $trxClass =
                                                $trx->transaction_type === 'in'
                                                    ? 'success'
                                                    : ($trx->transaction_type === 'out'
                                                        ? 'danger'
                                                        : 'warning');
                                        @endphp
                                        <span
                                            class="badge badge-{{ $trxClass }}">{{ ucfirst($trx->transaction_type) }}</span>
                                    </td>
                                    <td class="text-right">
                                        {{ rtrim(rtrim(number_format($trx->quantity, 2, '.', ''), '0'), '.') }}</td>
                                    <td class="text-right">
                                        {{ rtrim(rtrim(number_format($trx->balance_after, 2, '.', ''), '0'), '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No transactions yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Latest Work Orders</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>WO #</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentWorkOrders as $wo)
                                <tr>
                                    <td>
                                        <a href="{{ route('work_orders.show', $wo) }}">{{ $wo->wo_number }}</a><br>
                                        <small class="text-muted">{{ $wo->customer->name ?? '-' }}</small>
                                    </td>
                                    <td>
                                        @include('partials.wo_status_badge', ['status' => $wo->status])
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">No work orders yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Latest Purchase Orders</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>PO #</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentPurchaseOrders as $po)
                                <tr>
                                    <td>
                                        <a href="{{ route('purchase_orders.show', $po) }}">{{ $po->po_number }}</a><br>
                                        <small class="text-muted">{{ $po->supplier_name }}</small>
                                    </td>
                                    <td>
                                        <span
                                            class="badge badge-{{ $po->status === 'received' ? 'success' : ($po->status === 'approved' ? 'info' : ($po->status === 'partial' ? 'warning' : ($po->status === 'cancelled' ? 'danger' : 'secondary'))) }}">
                                            {{ $po->status === 'on_progress' ? 'On Progress' : ucwords(str_replace('_', ' ', $po->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">No purchase orders yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Latest Invoices</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentInvoices as $invoice)
                                <tr>
                                    <td>
                                        <a
                                            href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a><br>
                                        <small class="text-muted">Rp
                                            {{ number_format($invoice->grand_total, 0, ',', '.') }}</small>
                                    </td>
                                    <td>
                                        <span
                                            class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'partial' ? 'warning' : ($invoice->status === 'cancelled' ? 'danger' : ($invoice->status === 'sent' ? 'info' : 'secondary'))) }}">
                                            {{ $invoice->status === 'on_progress' ? 'On Progress' : ucwords(str_replace('_', ' ', $invoice->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">No invoices yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
