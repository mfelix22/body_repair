@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Purchasing Dashboard')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline card-info mb-0">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Procurement Overview</h5>
                            <p class="text-muted mb-0">Snapshot as of {{ now()->format('d M Y H:i') }}</p>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <a href="{{ route('purchase_orders.create') }}" class="btn btn-info btn-sm">
                                <i class="fas fa-plus"></i> New PO / SO
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
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $summary['prs_ready_for_po'] }}</h3>
                    <p>PRs Ready for PO</p>
                </div>
                <div class="icon"><i class="fas fa-file-alt"></i></div>
                <a href="{{ route('purchase_requests.index') }}" class="small-box-footer">View PRs <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $summary['purchase_orders_open'] }}</h3>
                    <p>Open PO & SO</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-list"></i></div>
                <a href="{{ route('purchase_orders.index') }}" class="small-box-footer">View POs <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $summary['suppliers'] }}</h3>
                    <p>Total Suppliers</p>
                </div>
                <div class="icon"><i class="fas fa-truck"></i></div>
                <a href="{{ route('suppliers.index') }}" class="small-box-footer">View Suppliers <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $summary['items'] }}</h3>
                    <p>Total Items</p>
                </div>
                <div class="icon"><i class="fas fa-box"></i></div>
                <a href="{{ route('items.index') }}" class="small-box-footer">View Items <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- PRs Ready for PO --}}
        <div class="col-lg-6">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-check-circle mr-1"></i> PRs Ready for PO</h3>
                    <div class="card-tools">
                        <a href="{{ route('purchase_requests.index') }}" class="btn btn-sm btn-default">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>PR #</th>
                                <th>Requested By</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($prsReadyForPo as $pr)
                                <tr>
                                    <td><a href="{{ route('purchase_requests.show', $pr) }}">{{ $pr->pr_number }}</a></td>
                                    <td>{{ $pr->requester->name ?? '-' }}</td>
                                    <td>{{ $pr->request_date?->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ route('purchase_orders.create', ['pr_id' => $pr->id]) }}"
                                            class="btn btn-xs btn-info">
                                            <i class="fas fa-plus"></i> Create PO
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No PRs awaiting PO</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Recent Purchase Orders --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clipboard-list mr-1"></i> Recent Purchase & Service Orders</h3>
                    <div class="card-tools">
                        <a href="{{ route('purchase_orders.index') }}" class="btn btn-sm btn-default">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>PO #</th>
                                <th>Supplier</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentPurchaseOrders as $po)
                                <tr>
                                    <td><a href="{{ route('purchase_orders.show', $po) }}">{{ $po->po_number }}</a></td>
                                    <td>{{ $po->supplier_name }}</td>
                                    <td>
                                        <span
                                            class="badge badge-{{ $po->status === 'received' ? 'success' : ($po->status === 'approved' ? 'info' : ($po->status === 'partial' ? 'warning' : ($po->status === 'cancelled' ? 'danger' : 'secondary'))) }}">
                                            {{ $po->status === 'on_progress' ? 'On Progress' : ucwords(str_replace('_', ' ', $po->status)) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">No purchase orders yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- PO Status Breakdown --}}
        <div class="col-lg-6">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">PO / SO Status Breakdown</h3>
                </div>
                <div class="card-body">
                    @foreach ($poStatusItems as $status)
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

        {{-- PR Status Breakdown --}}
        <div class="col-lg-6">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">Purchase Request Status Breakdown</h3>
                </div>
                <div class="card-body">
                    @foreach ($prStatusItems as $status)
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
@endsection
