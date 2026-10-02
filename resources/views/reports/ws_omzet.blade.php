@extends('layouts.admin')

@section('title', 'Laporan Omzet WS')

@php
    $rp = fn($v) => number_format((float) $v, 0, ',', '.');
@endphp

@section('page_title', 'Laporan Omzet WS')

@section('content')
    <div class="container-fluid">

        {{-- ===== SUMMARY CARDS ===== --}}
        <div class="row">
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-primary"><i class="fas fa-coins"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Grand Total Omzet</span>
                        <span class="info-box-number">Rp {{ $rp($totals['total']) }}</span>
                        <small class="text-muted">{{ number_format($totals['count']) }} invoice</small>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info"><i class="fas fa-tools"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Labor (Jasa)</span>
                        <span class="info-box-number">Rp {{ $rp($totals['labor']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning"><i class="fas fa-cogs"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Parts (Sparepart)</span>
                        <span class="info-box-number">Rp {{ $rp($totals['parts']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-danger"><i class="fas fa-percent"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Diskon</span>
                        <span class="info-box-number">Rp {{ $rp($totals['discount']) }}</span>
                        <small class="text-muted">Materai: Rp {{ $rp($totals['stamp_duty']) }}</small>
                    </div>
                </div>
            </div>
            @if ($canViewCogs)
                <div class="col-md">
                    <div class="info-box shadow-sm">
                        <span class="info-box-icon bg-success"><i class="fas fa-chart-line"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Gross Profit Part</span>
                            <span class="info-box-number">Rp {{ $rp($totals['parts_gp']) }}</span>
                            <small class="text-muted">HPP: Rp {{ $rp($totals['parts_cost']) }}
                                ({{ number_format($totals['parts_gp_pct'], 1) }}%)</small>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ===== FILTER CARD ===== --}}
        <div class="card card-outline card-primary">
            <form method="GET" action="{{ route('reports.ws_omzet') }}">
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
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Status Bayar</label>
                                <select name="status" class="form-control form-control-sm">
                                    <option value="">Semua Status</option>
                                    @foreach ($statuses as $code => $label)
                                        <option value="{{ $code }}" @selected($filters['status'] === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Cari No. Faktur / WO / Customer / Plat</label>
                                <input type="text" name="search" class="form-control form-control-sm"
                                    value="{{ $filters['search'] }}" placeholder="Ketik kata kunci...">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-sync-alt mr-1"></i>Refresh</button>
                    <a href="{{ route('reports.ws_omzet') }}" class="btn btn-secondary btn-sm ml-1">
                        <i class="fas fa-times mr-1"></i>Reset
                    </a>
                    <a href="{{ route('reports.ws_omzet.export', array_filter($filters)) }}" class="btn btn-success btn-sm float-right">
                        <i class="fas fa-file-excel mr-1"></i>Export Excel (.xlsx) Multi-Sheet
                    </a>
                </div>
            </form>
        </div>

        {{-- ===== TABS ===== --}}
        <div class="card card-outline card-info">
            <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#pane-summary" role="tab">
                            <i class="fas fa-file-invoice mr-1"></i>WS Invoice Summary ({{ count($summary) }})
                        </a>
                    </li>
                    @if ($canViewCogs)
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#pane-hpp" role="tab">
                                <i class="fas fa-balance-scale mr-1"></i>HPP Summary
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#pane-parts" role="tab">
                                <i class="fas fa-cogs mr-1"></i>HPP (COGS) ({{ count($parts) }})
                            </a>
                        </li>
                    @endif
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#pane-labor" role="tab">
                            <i class="fas fa-tools mr-1"></i>Detail Labor ({{ count($labors) }})
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body tab-content">

                {{-- ---- WS Invoice Summary ---- --}}
                <div class="tab-pane fade show active" id="pane-summary" role="tabpanel">
                    <table class="table table-bordered table-striped table-sm report-table text-nowrap" style="width:100%">
                        <thead>
                            <tr class="bg-info">
                                <th>Doc. No.</th>
                                <th>Doc. Type</th>
                                <th>Doc. Date</th>
                                <th>Account No.</th>
                                <th>Customer Name</th>
                                <th>Reg No</th>
                                <th>VIN</th>
                                <th>Desc</th>
                                <th>W. Order No</th>
                                <th>Date In</th>
                                <th>Labor</th>
                                <th>Parts</th>
                                <th>Discount</th>
                                <th>Stamp Duty</th>
                                <th>Total</th>
                                <th>Operator Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($summary as $r)
                                <tr>
                                    <td><a href="{{ route('invoices.show', $r['invoice_id']) }}" target="_blank">{{ $r['doc_no'] }}</a></td>
                                    <td>{{ $r['doc_type'] }}</td>
                                    <td data-order="{{ \Carbon\Carbon::createFromFormat('d/m/Y', $r['doc_date'])->format('Ymd') }}">{{ $r['doc_date'] }}</td>
                                    <td>{{ $r['account_no'] }}</td>
                                    <td>{{ $r['customer'] }}</td>
                                    <td>{{ $r['reg_no'] }}</td>
                                    <td>{{ $r['vin'] }}</td>
                                    <td>{{ $r['desc'] }}</td>
                                    <td><a href="{{ route('work_orders.show', $r['work_order_id']) }}" target="_blank">{{ $r['wo_no'] }}</a></td>
                                    <td>{{ $r['date_in'] }}</td>
                                    <td class="text-right">{{ $rp($r['labor']) }}</td>
                                    <td class="text-right">{{ $rp($r['parts']) }}</td>
                                    <td class="text-right">{{ $rp($r['discount']) }}</td>
                                    <td class="text-right">{{ $rp($r['stamp_duty']) }}</td>
                                    <td class="text-right font-weight-bold">{{ $rp($r['total']) }}</td>
                                    <td>{{ $r['operator'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold bg-light">
                                <td colspan="10" class="text-right">TOTAL</td>
                                <td class="text-right">{{ $rp($totals['labor']) }}</td>
                                <td class="text-right">{{ $rp($totals['parts']) }}</td>
                                <td class="text-right">{{ $rp($totals['discount']) }}</td>
                                <td class="text-right">{{ $rp($totals['stamp_duty']) }}</td>
                                <td class="text-right">{{ $rp($totals['total']) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if ($canViewCogs)
                    {{-- ---- HPP per Invoice ---- --}}
                    <div class="tab-pane fade" id="pane-hpp" role="tabpanel">
                        <table class="table table-bordered table-striped table-sm report-table text-nowrap" style="width:100%">
                            <thead>
                                <tr class="bg-info">
                                    <th>Doc. No.</th>
                                    <th>Doc. Date</th>
                                    <th>W. Order No</th>
                                    <th>Customer Name</th>
                                    <th>Reg No</th>
                                    <th>Labor</th>
                                    <th class="hpp-col">Parts Selling Price</th>
                                    <th class="hpp-col">Parts Cost Price (HPP)</th>
                                    <th>Parts Gross Profit</th>
                                    <th>GP %</th>
                                    <th>Discount</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summary as $r)
                                    <tr>
                                        <td><a href="{{ route('invoices.cogsReport', $r['invoice_id']) }}" target="_blank">{{ $r['doc_no'] }}</a></td>
                                        <td data-order="{{ \Carbon\Carbon::createFromFormat('d/m/Y', $r['doc_date'])->format('Ymd') }}">{{ $r['doc_date'] }}</td>
                                        <td>{{ $r['wo_no'] }}</td>
                                        <td>{{ $r['customer'] }}</td>
                                        <td>{{ $r['reg_no'] }}</td>
                                        <td class="text-right">{{ $rp($r['labor']) }}</td>
                                        <td class="text-right hpp-cell">{{ $rp($r['parts']) }}</td>
                                        <td class="text-right hpp-cell">{{ $rp($r['parts_cost']) }}</td>
                                        <td class="text-right {{ $r['parts_gp'] < 0 ? 'text-danger' : '' }}">{{ $rp($r['parts_gp']) }}</td>
                                        <td class="text-right">{{ number_format($r['parts_gp_pct'], 1) }}%</td>
                                        <td class="text-right">{{ $rp($r['discount']) }}</td>
                                        <td class="text-right font-weight-bold">{{ $rp($r['total']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold bg-light">
                                    <td colspan="5" class="text-right">TOTAL</td>
                                    <td class="text-right">{{ $rp($totals['labor']) }}</td>
                                    <td class="text-right">{{ $rp($totals['parts']) }}</td>
                                    <td class="text-right">{{ $rp($totals['parts_cost']) }}</td>
                                    <td class="text-right">{{ $rp($totals['parts_gp']) }}</td>
                                    <td class="text-right">{{ number_format($totals['parts_gp_pct'], 1) }}%</td>
                                    <td class="text-right">{{ $rp($totals['discount']) }}</td>
                                    <td class="text-right">{{ $rp($totals['total']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- ---- HPP (COGS) per part ---- --}}
                    <div class="tab-pane fade" id="pane-parts" role="tabpanel">
                        <table class="table table-bordered table-striped table-sm report-table text-nowrap" style="width:100%">
                            <thead>
                                <tr class="bg-info">
                                    <th>Doc. Date</th>
                                    <th>Doc. No.</th>
                                    <th>W. Order No</th>
                                    <th>Part No</th>
                                    <th>Part Name</th>
                                    <th>UOM</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Disc %</th>
                                    <th class="hpp-col">Selling Price</th>
                                    <th class="hpp-col">Cost</th>
                                    <th>Gross Profit</th>
                                    <th>Invoice No</th>
                                    <th>Invoice Date</th>
                                    <th>Account No.</th>
                                    <th>Customer Name</th>
                                    <th>Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($parts as $p)
                                    <tr>
                                        <td data-order="{{ \Carbon\Carbon::createFromFormat('d/m/Y', $p['doc_date'])->format('Ymd') }}">{{ $p['doc_date'] }}</td>
                                        <td>{{ $p['doc_no'] }}</td>
                                        <td>{{ $p['wo_no'] }}</td>
                                        <td><code>{{ $p['part_no'] }}</code></td>
                                        <td>{{ $p['part_name'] }}</td>
                                        <td>{{ $p['uom'] }}</td>
                                        <td class="text-right">{{ number_format($p['qty'], 2) }}</td>
                                        <td class="text-right">{{ $p['billed'] ? $rp($p['unit_price']) : '—' }}</td>
                                        <td class="text-right">{{ $p['billed'] ? number_format($p['disc_pct'], 2) : '—' }}</td>
                                        <td class="text-right hpp-cell">{{ $rp($p['total_selling']) }}</td>
                                        <td class="text-right hpp-cell">{{ $rp($p['cost']) }}</td>
                                        <td class="text-right {{ $p['gp'] < 0 ? 'text-danger' : '' }}">{{ $rp($p['gp']) }}</td>
                                        <td><a href="{{ route('invoices.show', $p['invoice_id']) }}" target="_blank">{{ $p['invoice_no'] }}</a></td>
                                        <td>{{ $p['invoice_date'] }}</td>
                                        <td>{{ $p['account_no'] }}</td>
                                        <td>{{ $p['customer'] }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit($p['address'], 60) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-weight-bold bg-light">
                                    <td colspan="6" class="text-right">TOTAL</td>
                                    <td class="text-right">{{ number_format(array_sum(array_column($parts, 'qty')), 2) }}</td>
                                    <td colspan="2"></td>
                                    <td class="text-right">{{ $rp(array_sum(array_column($parts, 'total_selling'))) }}</td>
                                    <td class="text-right">{{ $rp(array_sum(array_column($parts, 'cost'))) }}</td>
                                    <td class="text-right">{{ $rp(array_sum(array_column($parts, 'gp'))) }}</td>
                                    <td colspan="5"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif

                {{-- ---- Detail Labor ---- --}}
                <div class="tab-pane fade" id="pane-labor" role="tabpanel">
                    <table class="table table-bordered table-striped table-sm report-table text-nowrap" style="width:100%">
                        <thead>
                            <tr class="bg-info">
                                <th>Doc. No.</th>
                                <th>Doc. Date</th>
                                <th>W. Order No</th>
                                <th>Reg No</th>
                                <th>Labor Code</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Qty</th>
                                <th>Rate</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($labors as $l)
                                <tr>
                                    <td>{{ $l['doc_no'] }}</td>
                                    <td data-order="{{ \Carbon\Carbon::createFromFormat('d/m/Y', $l['doc_date'])->format('Ymd') }}">{{ $l['doc_date'] }}</td>
                                    <td>{{ $l['wo_no'] }}</td>
                                    <td>{{ $l['reg_no'] }}</td>
                                    <td><code>{{ $l['labor_code'] }}</code></td>
                                    <td>{{ $l['description'] }}</td>
                                    <td>{{ $l['type'] }}</td>
                                    <td class="text-right">{{ number_format($l['qty'], 2) }}</td>
                                    <td class="text-right">{{ $rp($l['rate']) }}</td>
                                    <td class="text-right">{{ $rp($l['total']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold bg-light">
                                <td colspan="9" class="text-right">TOTAL</td>
                                <td class="text-right">{{ $rp(array_sum(array_column($labors, 'total'))) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .report-table { font-size: .85rem; margin-bottom: 0 !important; }
        .report-table thead th { vertical-align: middle; white-space: nowrap; }
        .report-table td { vertical-align: middle; }
        .report-table tfoot td { white-space: nowrap; }
        .report-table thead th.hpp-col { background: #f1c40f !important; color: #212529 !important; }
        .report-table td.hpp-cell { background: rgba(241, 196, 15, .15) !important; font-weight: 600; }
        .report-table code { color: inherit; font-size: .85em; }
    </style>
@endpush

@push('scripts')
    <script>
        $(function() {
            function initTable($pane) {
                var $table = $pane.find('table.report-table');
                if (!$table.length || $.fn.DataTable.isDataTable($table)) {
                    return;
                }
                $table.DataTable({
                    responsive: false,
                    autoWidth: false,
                    pageLength: 50,
                    lengthMenu: [25, 50, 100, 500],
                    order: [],
                    language: { emptyTable: 'Tidak ada data untuk filter ini.' }
                });
            }

            // Only build a table once its tab is visible, so column widths are measured correctly.
            initTable($('.tab-pane.active'));
            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                initTable($($(e.target).attr('href')));
            });
        });
    </script>
@endpush
