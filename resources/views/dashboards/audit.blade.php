@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Audit Dashboard')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline card-secondary mb-0">
                <div class="card-body py-3">
                    <div>
                        <h5 class="mb-1">Audit Overview <span class="badge badge-secondary ml-1"
                                style="font-size:0.7rem;">Read Only</span></h5>
                        <p class="text-muted mb-0">Snapshot as of {{ now()->format('d M Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Boxes --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $summary['purchase_requests_pending'] }}</h3>
                    <p>Pending PRs</p>
                </div>
                <div class="icon"><i class="fas fa-file-alt"></i></div>
                <a href="{{ route('purchase_requests.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $summary['purchase_orders_open'] }}</h3>
                    <p>Open PO & SO</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-list"></i></div>
                <a href="{{ route('purchase_orders.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $summary['open_work_orders'] }}</h3>
                    <p>Active Work Orders</p>
                </div>
                <div class="icon"><i class="fas fa-tools"></i></div>
                <a href="{{ route('work_orders.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
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
    </div>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $summary['purchase_requests_total'] }}</h3>
                    <p>Total PRs</p>
                </div>
                <div class="icon"><i class="fas fa-file-alt"></i></div>
                <a href="{{ route('purchase_requests.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $summary['purchase_orders_total'] }}</h3>
                    <p>Total PO & SO</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-list"></i></div>
                <a href="{{ route('purchase_orders.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-{{ $summary['overdue_work_orders'] > 0 ? 'danger' : 'secondary' }}">
                <div class="inner">
                    <h3>{{ $summary['overdue_work_orders'] }}</h3>
                    <p>Overdue Work Orders</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                <a href="{{ route('work_orders.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-{{ $summary['low_stock_items'] > 0 ? 'warning' : 'secondary' }}">
                <div class="inner">
                    <h3>{{ $summary['low_stock_items'] }}</h3>
                    <p>Low Stock Items</p>
                </div>
                <div class="icon"><i class="fas fa-boxes"></i></div>
                <a href="{{ route('stocks.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    {{-- Master Data Summary --}}
    <div class="row">
        <div class="col-lg-4">
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

        {{-- Status Breakdowns --}}
        <div class="col-lg-8">
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
                                        <span
                                            class="badge badge-{{ $status['class'] }} mr-1">{{ $status['label'] }}</span>
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
                @endforeach
            </div>
        </div>
    </div>
@endsection
