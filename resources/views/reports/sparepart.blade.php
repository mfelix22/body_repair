@extends('layouts.admin')

@section('title', 'Sparepart Usage Report')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-chart-bar mr-2"></i>Sparepart Usage Report</h1>
    </div>
@endsection

@section('content')
    <div class="container-fluid">

        {{-- ===== FILTER CARD ===== --}}
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filters</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <form method="GET" action="{{ route('reports.sparepart') }}" id="filterForm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date From</label>
                                <input type="date" name="date_from" class="form-control form-control-sm"
                                    value="{{ request('date_from') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date To</label>
                                <input type="date" name="date_to" class="form-control form-control-sm"
                                    value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Vehicle</label>
                                <select name="vehicle" class="form-control form-control-sm select2">
                                    <option value="">-- All Vehicles --</option>
                                    @foreach ($vehicles as $v)
                                        <option value="{{ $v }}"
                                            {{ request('vehicle') === $v ? 'selected' : '' }}>
                                            {{ $v }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Spare Part</label>
                                <select name="item_id" class="form-control form-control-sm select2">
                                    <option value="">-- All Parts --</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id }}"
                                            {{ request('item_id') == $item->id ? 'selected' : '' }}>
                                            [{{ $item->code }}] {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search mr-1"></i>Apply Filter
                    </button>
                    <a href="{{ route('reports.sparepart') }}" class="btn btn-secondary btn-sm ml-1">
                        <i class="fas fa-times mr-1"></i>Clear Filter
                    </a>
                </div>
            </form>
        </div>

        {{-- ===== SUMMARY CARDS ===== --}}
        <div class="row">
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info"><i class="fas fa-list-ol"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Transactions</span>
                        <span class="info-box-number">{{ number_format($totalTransactions) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-success"><i class="fas fa-boxes"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Unique Parts Used</span>
                        <span class="info-box-number">{{ number_format($uniqueParts) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning"><i class="fas fa-money-bill-wave"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Cost</span>
                        <span class="info-box-number">Rp {{ number_format($totalCost, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== TABS ===== --}}
        <div class="card card-outline card-info">
            <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-part" data-toggle="tab" href="#pane-part" role="tab">
                            <i class="fas fa-puzzle-piece mr-1"></i>Summary by Part
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-vehicle" data-toggle="tab" href="#pane-vehicle" role="tab">
                            <i class="fas fa-car mr-1"></i>Summary by Vehicle
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-detail" data-toggle="tab" href="#pane-detail" role="tab">
                            <i class="fas fa-table mr-1"></i>Detailed Transactions
                        </a>
                    </li>
                    <li class="nav-item ml-auto d-flex align-items-center pr-2">
                        <button class="btn btn-success btn-sm" id="exportBtn">
                            <i class="fas fa-file-excel mr-1"></i>Export to Excel
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body tab-content">

                {{-- ---- Tab 1: By Part ---- --}}
                <div class="tab-pane fade show active" id="pane-part" role="tabpanel">
                    <table id="tbl-part" class="table table-bordered table-striped table-sm dataTable"
                        style="width:100%">
                        <thead>
                            <tr class="bg-info">
                                <th>No</th>
                                <th>Part Code</th>
                                <th>Part Name</th>
                                <th>Total Qty Used</th>
                                <th>Usage Count</th>
                                <th>Avg Price (Rp)</th>
                                <th>Total Cost (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($byPart as $row)
                                <tr>
                                    <td></td>
                                    <td><a href="{{ route('items.show', $row->item_id) }}"
                                            target="_blank"><code>{{ $row->item_code }}</code></a></td>
                                    <td><a href="{{ route('items.show', $row->item_id) }}"
                                            target="_blank">{{ $row->item_name }}</a></td>
                                    <td class="text-right">{{ number_format((float) $row->total_qty, 2) }}</td>
                                    <td class="text-right">{{ number_format((int) $row->usage_count), 2 }}</td>
                                    <td class="text-right">{{ number_format((float) $row->avg_price, 2, ',', '.') }}</td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format((float) $row->total_cost, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($byPart->count())
                            <tfoot>
                                <tr class="font-weight-bold bg-light">
                                    <td colspan="3" class="text-right">Total</td>
                                    <td class="text-right">{{ number_format($byPart->sum('total_qty'), 2) }}</td>
                                    <td class="text-right">{{ number_format($byPart->sum('usage_count'), 2) }}</td>
                                    <td></td>
                                    <td class="text-right">{{ number_format($byPart->sum('total_cost'), 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                {{-- ---- Tab 2: By Vehicle ---- --}}
                <div class="tab-pane fade" id="pane-vehicle" role="tabpanel">
                    <table id="tbl-vehicle" class="table table-bordered table-striped table-sm dataTable"
                        style="width:100%">
                        <thead>
                            <tr class="bg-info">
                                <th>No</th>
                                <th>Vehicle Plate</th>
                                <th>Merk / Brand</th>
                                <th>Total Qty Used</th>
                                <th>Usage Count</th>
                                <th>Total Cost (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($byVehicle as $row)
                                <tr>
                                    <td></td>
                                    <td>
                                        @if ($row->vehicle_id)
                                            <a href="{{ route('vehicles.show', $row->vehicle_id) }}" target="_blank">
                                                <strong>{{ $row->vehicle_plate }}</strong>
                                            </a>
                                        @else
                                            <strong>{{ $row->vehicle_plate }}</strong>
                                        @endif
                                    </td>
                                    <td>{{ $row->vehicle_merk }}</td>
                                    <td class="text-right">{{ number_format((float) $row->total_qty, 2) }}</td>
                                    <td class="text-right">{{ number_format((float) $row->usage_count, 2) }}</td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format((float) $row->total_cost, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($byVehicle->count())
                            <tfoot>
                                <tr class="font-weight-bold bg-light">
                                    <td colspan="3" class="text-right">Total</td>
                                    <td class="text-right">{{ number_format($byVehicle->sum('total_qty'), 2) }}</td>
                                    <td class="text-right">{{ number_format($byVehicle->sum('usage_count'), 2) }}</td>
                                    <td class="text-right">{{ number_format($byVehicle->sum('total_cost'), 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                {{-- ---- Tab 3: Detailed ---- --}}
                <div class="tab-pane fade" id="pane-detail" role="tabpanel">
                    <table id="tbl-detail" class="table table-bordered table-striped table-sm dataTable"
                        style="width:100%">
                        <thead>
                            <tr class="bg-info">
                                <th>No</th>
                                <th>Date</th>
                                <th>Bon Out #</th>
                                <th>WO #</th>
                                <th>Vehicle</th>
                                <th>Merk</th>
                                <th>Part Code</th>
                                <th>Part Name</th>
                                <th>Qty</th>
                                <th>Unit Cost</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($detailed as $row)
                                <tr>
                                    <td></td>
                                    <td>{{ \Carbon\Carbon::parse($row->issued_date)->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ route('bon_outs.show', $row->bon_out_id) }}" target="_blank">
                                            {{ $row->bon_out_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('work_orders.show', $row->work_order_id) }}" target="_blank">
                                            {{ $row->wo_number }}
                                        </a>
                                    </td>
                                    <td>
                                        @if ($row->vehicle_id)
                                            <a href="{{ route('vehicles.show', $row->vehicle_id) }}" target="_blank">
                                                <strong>{{ $row->vehicle_plate }}</strong>
                                            </a>
                                        @else
                                            <strong>{{ $row->vehicle_plate }}</strong>
                                        @endif
                                    </td>
                                    <td>{{ $row->vehicle_merk }}</td>
                                    <td>
                                        <a href="{{ route('items.show', $row->item_id) }}" target="_blank">
                                            <code>{{ $row->item_code }}</code>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('items.show', $row->item_id) }}" target="_blank">
                                            {{ $row->item_name }}
                                        </a>
                                    </td>
                                    <td class="text-right">{{ number_format((float) $row->actual_quantity, 2) }}</td>
                                    <td class="text-right">{{ number_format((float) $row->unit_cost, 2, ',', '.') }}</td>
                                    <td class="text-right font-weight-bold">
                                        {{ number_format((float) $row->line_cost, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted">No data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($detailed->count())
                            <tfoot>
                                <tr class="font-weight-bold bg-light">
                                    <td colspan="8" class="text-right">Total</td>
                                    <td class="text-right">{{ number_format($detailed->sum('actual_quantity'), 2) }}</td>
                                    <td></td>
                                    <td class="text-right">{{ number_format($detailed->sum('line_cost'), 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

            </div>{{-- card-body --}}
        </div>{{-- card --}}

    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            // Re-usable drawCallback for sequential row numbers
            function rowNumberCallback() {
                var api = this.api();
                var start = api.page.info().start;
                api.column(0, {
                    page: 'current'
                }).nodes().each(function(cell, i) {
                    cell.innerHTML = start + i + 1;
                });
            }

            // Init DataTables
            $('#tbl-part').DataTable({
                pageLength: 25,
                order: [
                    [6, 'desc']
                ],
                columnDefs: [{
                    orderable: false,
                    targets: 0
                }],
                drawCallback: rowNumberCallback
            });
            $('#tbl-vehicle').DataTable({
                pageLength: 25,
                order: [
                    [5, 'desc']
                ],
                columnDefs: [{
                    orderable: false,
                    targets: 0
                }],
                drawCallback: rowNumberCallback
            });
            $('#tbl-detail').DataTable({
                pageLength: 25,
                order: [
                    [1, 'desc']
                ],
                columnDefs: [{
                    orderable: false,
                    targets: 0
                }],
                drawCallback: rowNumberCallback
            });

            // Re-draw DataTables when tab shown (fixes column widths)
            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                $.fn.dataTable.tables({
                    visible: true,
                    api: true
                }).columns.adjust();
            });

            // Select2
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            // Active tab tracking for export
            var activeTab = 'by_part';
            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                var target = $(e.target).attr('href');
                if (target === '#pane-part') activeTab = 'by_part';
                if (target === '#pane-vehicle') activeTab = 'by_vehicle';
                if (target === '#pane-detail') activeTab = 'by_detail';
            });

            // Export button — passes current filters + active tab
            $('#exportBtn').on('click', function() {
                var params = $('#filterForm').serialize();
                params += '&tab=' + activeTab;
                window.location.href = '{{ route('reports.sparepart.export') }}?' + params;
            });
        });
    </script>
@endpush
