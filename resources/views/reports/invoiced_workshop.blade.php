@extends('layouts.admin')

@section('title', 'Invoiced Workshop Detail')

@php
    $rp = fn($v) => number_format((float) $v, 0, '.', ',');
    $queryFilters = array_filter($filters);
@endphp

@section('page_title', 'Invoiced Workshop Detail')

@section('content')
    <div class="container-fluid">

        {{-- ===== SUMMARY CARDS ===== --}}
        <div class="row">
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-primary"><i class="fas fa-file-invoice"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Dokumen</span>
                        <span class="info-box-number">{{ number_format($totals['count']) }}</span>
                        <small class="text-muted">{{ count($groups) }} account</small>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info"><i class="fas fa-tools"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Labour</span>
                        <span class="info-box-number">Rp {{ $rp($totals['labour']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning"><i class="fas fa-cogs"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Sparepart</span>
                        <span class="info-box-number">Rp {{ $rp($totals['sparepart']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-success"><i class="fas fa-coins"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total</span>
                        <span class="info-box-number">Rp {{ $rp($totals['total']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== FILTER CARD ===== --}}
        <div class="card card-outline card-primary">
            <form method="GET" action="{{ route('reports.invoiced_workshop') }}">
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
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Account</label>
                                <select name="account_code" class="form-control form-control-sm">
                                    <option value="">Semua Account</option>
                                    @foreach ($accountCodes as $code => $label)
                                        <option value="{{ $code }}" @selected($filters['account_code'] === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync-alt mr-1"></i>Refresh</button>
                    <a href="{{ route('reports.invoiced_workshop') }}" class="btn btn-secondary btn-sm ml-1">
                        <i class="fas fa-times mr-1"></i>Reset
                    </a>
                    <div class="float-right">
                        <a href="{{ route('reports.invoiced_workshop.print', $queryFilters) }}" target="_blank" class="btn btn-info btn-sm">
                            <i class="fas fa-print mr-1"></i>Print
                        </a>
                        <a href="{{ route('reports.invoiced_workshop.export', $queryFilters) }}" class="btn btn-success btn-sm ml-1">
                            <i class="fas fa-file-excel mr-1"></i>Export Excel
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- ===== LISTING ===== --}}
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-1"></i>Invoiced Workshop Detail {{ $period }}</h3>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm report-table text-nowrap mb-0">
                    <thead class="bg-info">
                        <tr>
                            <th>No.</th>
                            <th>Date</th>
                            <th>Inv-No</th>
                            <th>WIP-No</th>
                            <th>Customer</th>
                            <th class="text-right">Labour</th>
                            <th class="text-right">Sparepart</th>
                            <th class="text-right">Discount</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Tax</th>
                            <th class="text-right">Stamp</th>
                            <th class="text-right">Total</th>
                            <th>Job-No</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groups as $group)
                            <tr class="account-row">
                                <td colspan="13">ACCOUNT : {{ $group['label'] }}</td>
                            </tr>
                            @foreach ($group['rows'] as $r)
                                <tr class="{{ $r['type'] === 'credit_note' ? 'text-danger' : '' }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $r['date'] }}</td>
                                    <td>
                                        <a href="{{ $r['type'] === 'credit_note' ? route('credit_notes.show', $r['id']) : route('invoices.show', $r['id']) }}"
                                            target="_blank" class="{{ $r['type'] === 'credit_note' ? 'text-danger' : '' }}">{{ $r['inv_no'] }}</a>
                                        @if ($r['type'] === 'credit_note')
                                            <span class="badge badge-danger">CN</span>
                                        @elseif ($r['cancelled'])
                                            <span class="badge badge-secondary">Cancelled</span>
                                        @endif
                                    </td>
                                    <td>{{ $r['wip_no'] }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($r['customer'], 35) }}</td>
                                    <td class="text-right">{{ $r['labour'] ? $rp($r['labour']) : '' }}</td>
                                    <td class="text-right">{{ $r['sparepart'] ? $rp($r['sparepart']) : '' }}</td>
                                    <td class="text-right">{{ $r['discount'] ? $rp($r['discount']) : '' }}</td>
                                    <td class="text-right">{{ $rp($r['amount']) }}</td>
                                    <td class="text-right">{{ $r['tax'] ? $rp($r['tax']) : '' }}</td>
                                    <td class="text-right">{{ $r['stamp'] ? $rp($r['stamp']) : '' }}</td>
                                    <td class="text-right">{{ $rp($r['total']) }}</td>
                                    <td>
                                        @if ($r['work_order_id'])
                                            <a href="{{ route('work_orders.show', $r['work_order_id']) }}" target="_blank">{{ $r['job_no'] }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="group-total">
                                <td colspan="5" class="pl-4">SUB TOTAL {{ $group['label'] }} ({{ $group['totals']['count'] }})</td>
                                <td class="text-right">{{ $rp($group['totals']['labour']) }}</td>
                                <td class="text-right">{{ $rp($group['totals']['sparepart']) }}</td>
                                <td class="text-right">{{ $rp($group['totals']['discount']) }}</td>
                                <td class="text-right">{{ $rp($group['totals']['amount']) }}</td>
                                <td class="text-right">{{ $rp($group['totals']['tax']) }}</td>
                                <td class="text-right">{{ $rp($group['totals']['stamp']) }}</td>
                                <td class="text-right">{{ $rp($group['totals']['total']) }}</td>
                                <td></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center text-muted py-4">Tidak ada invoice untuk filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($groups)
                        <tfoot>
                            <tr class="grand-total">
                                <td colspan="5">GRAND TOTAL ({{ $totals['count'] }})</td>
                                <td class="text-right">{{ $rp($totals['labour']) }}</td>
                                <td class="text-right">{{ $rp($totals['sparepart']) }}</td>
                                <td class="text-right">{{ $rp($totals['discount']) }}</td>
                                <td class="text-right">{{ $rp($totals['amount']) }}</td>
                                <td class="text-right">{{ $rp($totals['tax']) }}</td>
                                <td class="text-right">{{ $rp($totals['stamp']) }}</td>
                                <td class="text-right">{{ $rp($totals['total']) }}</td>
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
        .report-table .account-row td { background: #f4f6f9; font-weight: 600; border-top: 2px solid #dee2e6; }
        .report-table .group-total td { font-weight: 600; border-top: 1px solid #343a40; border-bottom: 3px double #343a40; }
        .report-table .grand-total td { font-weight: 700; background: #fcf3cf; border-top: 2px solid #343a40; }
    </style>
@endpush
