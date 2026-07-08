@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Warehouse Dashboard')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline card-success mb-0">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Warehouse Overview</h5>
                            <p class="text-muted mb-0">Snapshot as of {{ now()->format('d M Y H:i') }}</p>
                        </div>
                        <div class="mt-2 mt-md-0">
                            {{-- <a href="{{ route('work_orders.create') }}" class="btn btn-primary btn-sm mr-1">
                                <i class="fas fa-plus"></i> New Work Order
                            </a> --}}
                            <a href="{{ route('bon_outs.createStandalone') }}" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> New Bon Out
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
                    <h3>{{ $summary['overdue_work_orders'] }}</h3>
                    <p>Overdue Work Orders</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                <a href="{{ route('work_orders.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-orange">
                <div class="inner">
                    <h3>{{ $summary['low_stock_items'] }}</h3>
                    <p>Low Stock Alerts</p>
                </div>
                <div class="icon"><i class="fas fa-boxes"></i></div>
                <a href="{{ route('stocks.index') }}" class="small-box-footer">View Stock <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $summary['pending_receivables'] }}</h3>
                    <p>Pending Bon In</p>
                </div>
                <div class="icon"><i class="fas fa-dolly"></i></div>
                <a href="{{ route('receivables.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Low Stock Alerts --}}
        <div class="col-lg-5">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-1"></i> Stock Alerts</h3>
                    <div class="card-tools">
                        <a href="{{ route('stocks.index') }}" class="btn btn-sm btn-warning">View All</a>
                    </div>
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
            </div>
        </div>

        {{-- Open Work Orders --}}
        <div class="col-lg-7">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-tools mr-1"></i> Active Work Orders</h3>
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
                                <th>Deadline</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($openWorkOrders as $wo)
                                <tr>
                                    <td><a href="{{ route('work_orders.show', $wo) }}">{{ $wo->wo_number }}</a></td>
                                    <td>{{ $wo->customer->name ?? '-' }}</td>
                                    <td>
                                        @if ($wo->deadline)
                                            <span
                                                class="{{ $wo->deadline->isPast() ? 'text-danger font-weight-bold' : '' }}">
                                                {{ $wo->deadline->format('d M Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>@include('partials.wo_status_badge', ['status' => $wo->status])</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No active work orders</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Recent Stock Transactions --}}
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
                                        @php $trxClass = $trx->transaction_type === 'in' ? 'success' : ($trx->transaction_type === 'out' ? 'danger' : 'warning'); @endphp
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

        {{-- Recent Bon Out --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-dolly-flatbed mr-1"></i> Recent Bon Out</h3>
                    <div class="card-tools">
                        <a href="{{ route('bon_outs.index') }}" class="btn btn-sm btn-default">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Bon Out #</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentBonOuts as $bo)
                                <tr>
                                    <td><a href="{{ route('bon_outs.show', $bo) }}">{{ $bo->bon_out_number }}</a></td>
                                    <td>{{ $bo->created_at->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">No bon out yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
