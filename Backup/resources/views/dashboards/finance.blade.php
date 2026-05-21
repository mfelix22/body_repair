@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Finance Dashboard')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline card-success mb-0">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Finance Overview</h5>
                            <p class="text-muted mb-0">Snapshot as of {{ now()->format('d M Y H:i') }}</p>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <a href="{{ route('invoices.create') }}" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> New Invoice
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Boxes --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $summary['outstanding_invoices'] }}</h3>
                    <p>Outstanding Invoices</p>
                </div>
                <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <a href="{{ route('invoices.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3 style="font-size: 1.4rem;">Rp {{ number_format($summary['outstanding_amount'], 0, ',', '.') }}</h3>
                    <p>Outstanding Amount</p>
                </div>
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                <a href="{{ route('invoices.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $summary['paid_invoices_month'] }}</h3>
                    <p>Paid This Month</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <a href="{{ route('invoices.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3 style="font-size: 1.4rem;">Rp {{ number_format($summary['revenue_this_month'], 0, ',', '.') }}</h3>
                    <p>Revenue This Month</p>
                </div>
                <div class="icon"><i class="fas fa-chart-line"></i></div>
                <a href="{{ route('invoices.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Invoice Status Breakdown --}}
        <div class="col-lg-4">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Invoice Status Breakdown</h3>
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

            {{-- Recently Invoiced / Completed Work Orders --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tools mr-1"></i> Completed Work Orders</h3>
                    <div class="card-tools">
                        <a href="{{ route('work_orders.index') }}" class="btn btn-sm btn-default">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>WO #</th>
                                <th>Customer</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentWorkOrders as $wo)
                                <tr>
                                    <td><a href="{{ route('work_orders.show', $wo) }}">{{ $wo->wo_number }}</a></td>
                                    <td>{{ $wo->customer->name ?? '-' }}</td>
                                    <td>@include('partials.wo_status_badge', ['status' => $wo->status])</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">None</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Recent Invoices --}}
        <div class="col-lg-8">
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
                                <th class="text-right">Amount</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentInvoices as $invoice)
                                <tr>
                                    <td>
                                        <a
                                            href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a><br>
                                        <small class="text-muted">{{ $invoice->created_at->format('d M Y') }}</small>
                                    </td>
                                    <td>{{ $invoice->customer->name ?? '-' }}</td>
                                    <td class="text-right">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                                    <td>
                                        <span
                                            class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'partial' ? 'warning' : ($invoice->status === 'cancelled' ? 'danger' : ($invoice->status === 'sent' ? 'info' : 'secondary'))) }}">
                                            {{ $invoice->status === 'on_progress' ? 'On Progress' : ucwords(str_replace('_', ' ', $invoice->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-xs btn-default">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No invoices yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
