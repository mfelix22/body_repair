@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Manager Dashboard')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline card-primary mb-0">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Management Overview</h5>
                            <p class="text-muted mb-0">Snapshot as of {{ now()->format('d M Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Boxes --}}
    <div class="row">
        <div class="col-lg-2 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $summary['pending_pr_dept_head'] }}</h3>
                    <p>Awaiting Dept Head</p>
                </div>
                <div class="icon"><i class="fas fa-file-alt"></i></div>
                <a href="{{ route('purchase_requests.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $summary['pending_pr_gm'] }}</h3>
                    <p>Awaiting GM Approval</p>
                </div>
                <div class="icon"><i class="fas fa-stamp"></i></div>
                <a href="{{ route('purchase_requests.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $summary['open_work_orders'] }}</h3>
                    <p>Active Work Orders</p>
                </div>
                <div class="icon"><i class="fas fa-tools"></i></div>
                <a href="{{ route('work_orders.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $summary['overdue_work_orders'] }}</h3>
                    <p>Overdue Work Orders</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                <a href="{{ route('work_orders.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $summary['outstanding_invoices'] }}</h3>
                    <p>Outstanding Invoices</p>
                </div>
                <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <a href="{{ route('invoices.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-2 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $summary['purchase_orders_open'] }}</h3>
                    <p>Open PO & SO</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-list"></i></div>
                <a href="{{ route('purchase_orders.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- PRs Pending Approval --}}
        <div class="col-lg-6">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock mr-1"></i> Purchase Requests Pending Approval</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>PR #</th>
                                <th>Requested By</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($prsPendingApproval as $pr)
                                <tr>
                                    <td>
                                        <a href="{{ route('purchase_requests.show', $pr) }}">{{ $pr->pr_number }}</a>
                                    </td>
                                    <td>{{ $pr->requester->name ?? '-' }}</td>
                                    <td>{{ $pr->request_date?->format('d M Y') }}</td>
                                    <td>
                                        @if ($pr->status === 'on_progress')
                                            <span class="badge badge-secondary">Needs Dept Head</span>
                                        @elseif ($pr->status === 'dept_head_approved')
                                            <span class="badge badge-info">Needs GM</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No pending approvals</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Overdue Work Orders --}}
        <div class="col-lg-6">
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Overdue Work Orders</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>WO #</th>
                                <th>Customer</th>
                                <th>Deadline</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($overdueWorkOrders as $wo)
                                <tr>
                                    <td><a href="{{ route('work_orders.show', $wo) }}">{{ $wo->wo_number }}</a></td>
                                    <td>{{ $wo->customer->name ?? '-' }}</td>
                                    <td><span class="text-danger">{{ $wo->deadline?->format('d M Y') }}</span></td>
                                    <td>@include('partials.wo_status_badge', ['status' => $wo->status])</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No overdue work orders</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- WO Status Breakdown --}}
        <div class="col-lg-6">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Work Order Status</h3>
                </div>
                <div class="card-body">
                    @foreach ($woStatusItems as $status)
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge badge-{{ $status['class'] }} mr-1">{{ $status['label'] }}</span>
                            <strong>{{ $status['count'] }}</strong>
                        </div>
                        <div class="progress mb-3" style="height: 6px;">
                            <div class="progress-bar bg-{{ $status['class'] }}"
                                style="width: {{ $status['percentage'] }}%"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Invoice Status Breakdown --}}
        <div class="col-lg-6">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Invoice Status</h3>
                </div>
                <div class="card-body">
                    @foreach ($invoiceStatusItems as $status)
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge badge-{{ $status['class'] }} mr-1">{{ $status['label'] }}</span>
                            <strong>{{ $status['count'] }}</strong>
                        </div>
                        <div class="progress mb-3" style="height: 6px;">
                            <div class="progress-bar bg-{{ $status['class'] }}"
                                style="width: {{ $status['percentage'] }}%"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Recent Invoices --}}
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice mr-1"></i> Recent Invoices</h3>
                    <div class="card-tools">
                        <a href="{{ route('invoices.index') }}" class="btn btn-sm btn-default">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentInvoices as $invoice)
                                <tr>
                                    <td><a
                                            href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                                    </td>
                                    <td>{{ $invoice->customer->name ?? '-' }}</td>
                                    <td>Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                                    <td>
                                        <span
                                            class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'partial' ? 'warning' : ($invoice->status === 'cancelled' ? 'danger' : ($invoice->status === 'sent' ? 'info' : 'secondary'))) }}">
                                            {{ $invoice->status === 'on_progress' ? 'On Progress' : ucwords(str_replace('_', ' ', $invoice->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No invoices yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
