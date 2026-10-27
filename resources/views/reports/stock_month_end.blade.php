@extends('layouts.admin')

@section('title', 'Month-End Stock Cost')

@php
    $rp = fn($v) => number_format((float) $v, 2, '.', ',');
    $qty = fn($v) => number_format((float) $v, 2, '.', ',');
    $queryFilters = array_filter($filters);
    $momDelta = $cards['close_total'] - $cards['prev_total'];
    $momPct = $cards['prev_total'] != 0 ? ($momDelta / abs($cards['prev_total'])) * 100 : null;
@endphp

@section('page_title', 'Month-End Stock Cost')

@section('content')
    <div class="container-fluid">

        {{-- ===== SUMMARY CARDS ===== --}}
        <div class="row">
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-primary"><i class="fas fa-warehouse"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Stock Value — {{ $monthLabel }}</span>
                        <span class="info-box-number">Rp {{ $rp($cards['close_total']) }}</span>
                        @if ($momPct !== null)
                            <small class="{{ $momDelta >= 0 ? 'text-success' : 'text-danger' }}">
                                <i class="fas fa-arrow-{{ $momDelta >= 0 ? 'up' : 'down' }}"></i>
                                {{ $rp(abs($momDelta)) }} ({{ number_format($momPct, 1) }}%) vs prev month
                            </small>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-success"><i class="fas fa-truck-loading"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Stock In (Purchases)</span>
                        <span class="info-box-number">Rp {{ $rp($cards['in_val']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-danger"><i class="fas fa-dolly"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Stock Out (Issued)</span>
                        <span class="info-box-number">Rp {{ $rp(-$cards['out_val']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info"><i class="fas fa-boxes"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Items In Stock</span>
                        <span class="info-box-number">{{ number_format($cards['items_in_stock']) }}</span>
                        <small class="text-muted">qty &gt; 0 at month end</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== FILTER CARD ===== --}}
        <div class="card card-outline card-primary">
            <form method="GET" action="{{ route('reports.stock_month_end') }}">
                <div class="card-body pb-0">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Year</label>
                                <select name="year" class="form-control form-control-sm">
                                    @for ($y = now()->year; $y >= $filters['first_year']; $y--)
                                        <option value="{{ $y }}" @selected($filters['year'] === $y)>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Month (Detail)</label>
                                <select name="month" class="form-control form-control-sm">
                                    @for ($m = 1; $m <= $filters['last_month']; $m++)
                                        <option value="{{ $m }}" @selected($filters['month'] === $m)>
                                            {{ \Carbon\Carbon::create($filters['year'], $m, 1)->format('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Item Type</label>
                                <select name="item_type" class="form-control form-control-sm">
                                    <option value="">All Types</option>
                                    @foreach ($types as $code => $label)
                                        <option value="{{ $code }}" @selected($filters['item_type'] === $code)>{{ $code }} - {{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Search Item</label>
                                <input type="text" name="search" class="form-control form-control-sm"
                                    placeholder="Code or name..." value="{{ $filters['search'] }}">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync-alt mr-1"></i>Refresh</button>
                    <a href="{{ route('reports.stock_month_end') }}" class="btn btn-secondary btn-sm ml-1">
                        <i class="fas fa-times mr-1"></i>Reset
                    </a>
                    <div class="float-right">
                        <a href="{{ route('reports.stock_month_end.print', $queryFilters) }}" target="_blank" class="btn btn-info btn-sm">
                            <i class="fas fa-print mr-1"></i>Print
                        </a>
                        <a href="{{ route('reports.stock_month_end.export', $queryFilters) }}" class="btn btn-success btn-sm ml-1">
                            <i class="fas fa-file-excel mr-1"></i>Export Excel
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- ===== YEAR MATRIX ===== --}}
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-table mr-1"></i>Closing Stock Value per Month — {{ $filters['year'] }}</h3>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm report-table text-nowrap mb-0">
                    <thead class="bg-secondary">
                        <tr>
                            <th>Item Type</th>
                            @for ($m = 1; $m <= 12; $m++)
                                <th class="text-right {{ $m === $filters['month'] ? 'bg-primary' : '' }}">
                                    {{ \Carbon\Carbon::create($filters['year'], $m, 1)->format('M') }}
                                </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($matrix as $type => $months)
                            <tr>
                                <td>{{ $type }} - {{ $types[$type] ?? $type }}</td>
                                @for ($m = 1; $m <= 12; $m++)
                                    <td class="text-right {{ $m === $filters['month'] ? 'table-active font-weight-bold' : '' }}">
                                        {{ $m <= $filters['last_month'] ? $rp($months[$m] ?? 0) : '—' }}
                                    </td>
                                @endfor
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center text-muted py-4">No stock data for {{ $filters['year'] }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($matrix)
                        <tfoot>
                            <tr class="grand-total">
                                <td>TOTAL</td>
                                @for ($m = 1; $m <= 12; $m++)
                                    <td class="text-right {{ $m === $filters['month'] ? 'bg-warning' : '' }}">
                                        {{ $m <= $filters['last_month'] ? $rp($monthTotals[$m]) : '—' }}
                                    </td>
                                @endfor
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- ===== MONTH DETAIL ===== --}}
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-1"></i>Stock Detail — {{ $monthLabel }}</h3>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm report-table text-nowrap mb-0">
                    <thead class="bg-info">
                        <tr>
                            <th>Code</th>
                            <th>Item Name</th>
                            <th>UOM</th>
                            <th class="text-right">Open Qty</th>
                            <th class="text-right">Open Value</th>
                            <th class="text-right">In Qty</th>
                            <th class="text-right">In Value</th>
                            <th class="text-right">Out Qty</th>
                            <th class="text-right">Out Value</th>
                            <th class="text-right">Adj Qty</th>
                            <th class="text-right" title="Includes avg-cost revaluation impact">Adj/Reval Val</th>
                            <th class="text-right">Close Qty</th>
                            <th class="text-right">Avg Cost</th>
                            <th class="text-right">Close Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groups as $type => $group)
                            @unless ($loop->first)
                                <tr class="group-spacer"><td colspan="14"></td></tr>
                            @endunless
                            <tr class="type-row">
                                <td colspan="14">Type : {{ $type }} - {{ $types[$type] ?? $type }}</td>
                            </tr>
                            @foreach ($group['rows'] as $r)
                                <tr>
                                    <td><a href="{{ route('items.show', $r['id']) }}" target="_blank">{{ $r['code'] }}</a></td>
                                    <td>{{ $r['name'] }}</td>
                                    <td>{{ $r['uom'] }}</td>
                                    <td class="text-right">{{ $qty($r['open_qty']) }}</td>
                                    <td class="text-right">{{ $rp($r['open_val']) }}</td>
                                    <td class="text-right">{{ $qty($r['in_qty']) }}</td>
                                    <td class="text-right">{{ $rp($r['in_val']) }}</td>
                                    <td class="text-right">{{ $qty(-$r['out_qty']) }}</td>
                                    <td class="text-right">{{ $rp(-$r['out_val']) }}</td>
                                    <td class="text-right {{ $r['adj_qty'] < 0 ? 'text-danger' : '' }}">{{ $qty($r['adj_qty']) }}</td>
                                    <td class="text-right {{ $r['adj_val'] + $r['reval'] < 0 ? 'text-danger' : '' }}">{{ $rp($r['adj_val'] + $r['reval']) }}</td>
                                    <td class="text-right font-weight-bold">{{ $qty($r['close_qty']) }}</td>
                                    <td class="text-right">{{ $rp($r['close_avg']) }}</td>
                                    <td class="text-right font-weight-bold">{{ $rp($r['close_val']) }}</td>
                                </tr>
                            @endforeach
                            <tr class="group-total">
                                <td colspan="3" class="pl-4">Total {{ $type }} - {{ $types[$type] ?? $type }} ({{ $group['totals']['count'] }})</td>
                                <td></td>
                                <td class="text-right">{{ $rp($group['totals']['open_val']) }}</td>
                                <td></td>
                                <td class="text-right">{{ $rp($group['totals']['in_val']) }}</td>
                                <td></td>
                                <td class="text-right">{{ $rp(-$group['totals']['out_val']) }}</td>
                                <td></td>
                                <td class="text-right">{{ $rp($group['totals']['adj_val'] + $group['totals']['reval']) }}</td>
                                <td></td>
                                <td></td>
                                <td class="text-right">{{ $rp($group['totals']['close_val']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center text-muted py-4">No stock or movements for {{ $monthLabel }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($groups)
                        <tfoot>
                            <tr class="grand-total">
                                <td colspan="3">GRAND TOTAL ({{ $totals['count'] }} items)</td>
                                <td></td>
                                <td class="text-right">{{ $rp($totals['open_val']) }}</td>
                                <td></td>
                                <td class="text-right">{{ $rp($totals['in_val']) }}</td>
                                <td></td>
                                <td class="text-right">{{ $rp(-$totals['out_val']) }}</td>
                                <td></td>
                                <td class="text-right">{{ $rp($totals['adj_val'] + $totals['reval']) }}</td>
                                <td></td>
                                <td></td>
                                <td class="text-right">{{ $rp($totals['close_val']) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            <div class="card-footer text-muted small">
                Valuation: weighted average cost, reconstructed from the stock transaction ledger
                (incl. manual avg-cost adjustments). Open + In &minus; Out &plusmn; Adj/Reval = Close.
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .report-table { font-size: .85rem; }
        .report-table td, .report-table th { vertical-align: middle; }
        .report-table .type-row td { background: #f4f6f9; font-weight: 600; border-top: 2px solid #dee2e6; }
        .report-table .group-spacer td { height: 22px; border: none; }
        .report-table .group-total td { font-weight: 600; border-top: 1px solid #343a40; border-bottom: 3px double #343a40; }
        .report-table .grand-total td { font-weight: 700; background: #fcf3cf; border-top: 2px solid #343a40; }
    </style>
@endpush
