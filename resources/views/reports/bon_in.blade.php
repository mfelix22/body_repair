@extends('layouts.admin')

@section('title', 'Bon In Listing')

@php
    $rp = fn($v) => number_format((float) $v, 2, '.', ',');
    $queryFilters = array_filter($filters);
@endphp

@section('page_title', 'Bon In Listing')

@section('content')
    <div class="container-fluid">

        {{-- ===== SUMMARY CARDS ===== --}}
        <div class="row">
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-primary"><i class="fas fa-truck-loading"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Bon In</span>
                        <span class="info-box-number">{{ number_format($totals['count']) }}</span>
                        <small class="text-muted">{{ count($groups) }} vendor</small>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info"><i class="fas fa-coins"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Value</span>
                        <span class="info-box-number">Rp {{ $rp($totals['value']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning"><i class="fas fa-percent"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Tax (PPN)</span>
                        <span class="info-box-number">Rp {{ $rp($totals['tax']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-success"><i class="fas fa-boxes"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Cost Value (Stock)</span>
                        <span class="info-box-number">Rp {{ $rp($totals['cost_value']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== FILTER CARD ===== --}}
        <div class="card card-outline card-primary">
            <form method="GET" action="{{ route('reports.bon_in') }}">
                <div class="card-body pb-0">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Dari Tanggal</label>
                                <input type="date" name="date_from" class="form-control form-control-sm"
                                    value="{{ $filters['date_from'] }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Sampai Tanggal</label>
                                <input type="date" name="date_to" class="form-control form-control-sm"
                                    value="{{ $filters['date_to'] }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Vendor</label>
                                <select name="supplier_id" class="form-control form-control-sm select2">
                                    <option value="">Semua Vendor</option>
                                    @foreach ($suppliers as $s)
                                        <option value="{{ $s->id }}" @selected($filters['supplier_id'] === $s->id)>
                                            {{ $s->supplier_code ? $s->supplier_code . ' - ' : '' }}{{ $s->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Tipe Bon In</label>
                                <select name="bon_in_type" class="form-control form-control-sm">
                                    <option value="">Semua Tipe</option>
                                    @foreach ($types as $code => $label)
                                        <option value="{{ $code }}" @selected($filters['bon_in_type'] === $code)>{{ $code }} - {{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync-alt mr-1"></i>Refresh</button>
                    <a href="{{ route('reports.bon_in') }}" class="btn btn-secondary btn-sm ml-1">
                        <i class="fas fa-times mr-1"></i>Reset
                    </a>
                    <div class="float-right">
                        <a href="{{ route('reports.bon_in.print', $queryFilters) }}" target="_blank" class="btn btn-info btn-sm">
                            <i class="fas fa-print mr-1"></i>Print
                        </a>
                        <a href="{{ route('reports.bon_in.export', $queryFilters) }}" class="btn btn-success btn-sm ml-1">
                            <i class="fas fa-file-excel mr-1"></i>Export Excel
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- ===== LISTING ===== --}}
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-1"></i>Bon In Listing {{ $period }}</h3>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm report-table text-nowrap mb-0">
                    <thead class="bg-info">
                        <tr>
                            <th>Type</th>
                            <th>Bon In</th>
                            <th>Date</th>
                            <th>Order</th>
                            <th class="text-right">Value</th>
                            <th class="text-right">Tax</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Cost Value</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groups as $group)
                            @unless ($loop->first)
                                <tr class="group-spacer"><td colspan="9"></td></tr>
                            @endunless
                            <tr class="vendor-row">
                                <td colspan="9">Vendor : {{ $group['code'] }} - {{ $group['name'] }}</td>
                            </tr>
                            @foreach ($group['rows'] as $r)
                                <tr>
                                    <td>{{ $r['type'] }}</td>
                                    <td><a href="{{ route('receivables.show', $r['id']) }}" target="_blank">{{ $r['bon_in'] }}</a></td>
                                    <td>{{ $r['date'] }}</td>
                                    <td>
                                        @if ($r['po_id'])
                                            <a href="{{ route('purchase_orders.show', $r['po_id']) }}" target="_blank">{{ $r['order'] }}</a>
                                        @endif
                                    </td>
                                    <td class="text-right {{ $r['value'] < 0 ? 'text-danger' : '' }}">{{ $rp($r['value']) }}</td>
                                    <td class="text-right">{{ $rp($r['tax']) }}</td>
                                    <td class="text-right">{{ $rp($r['total']) }}</td>
                                    <td class="text-right">{{ $rp($r['cost_value']) }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($r['note'], 40) }}</td>
                                </tr>
                            @endforeach
                            <tr class="group-total">
                                <td colspan="4" class="pl-4">Total {{ $group['code'] }} - {{ $group['name'] }} ({{ $group['totals']['count'] }})</td>
                                <td class="text-right">{{ $rp($group['totals']['value']) }}</td>
                                <td class="text-right">{{ $rp($group['totals']['tax']) }}</td>
                                <td class="text-right">{{ $rp($group['totals']['total']) }}</td>
                                <td class="text-right">{{ $rp($group['totals']['cost_value']) }}</td>
                                <td></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Tidak ada Bon In (completed) untuk filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($groups)
                        <tfoot>
                            <tr class="grand-total">
                                <td colspan="4">GRAND TOTAL ({{ $totals['count'] }})</td>
                                <td class="text-right">{{ $rp($totals['value']) }}</td>
                                <td class="text-right">{{ $rp($totals['tax']) }}</td>
                                <td class="text-right">{{ $rp($totals['total']) }}</td>
                                <td class="text-right">{{ $rp($totals['cost_value']) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .report-table { font-size: .85rem; }
        .report-table td, .report-table th { vertical-align: middle; }
        .report-table .vendor-row td { background: #f4f6f9; font-weight: 600; border-top: 2px solid #dee2e6; }
        .report-table .group-spacer td { height: 22px; border: none; }
        .report-table .group-total td { font-weight: 600; border-top: 1px solid #343a40; border-bottom: 3px double #343a40; }
        .report-table .grand-total td { font-weight: 700; background: #fcf3cf; border-top: 2px solid #343a40; }
    </style>
@endpush
