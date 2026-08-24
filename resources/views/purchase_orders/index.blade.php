@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
@endpush

@section('title', 'Purchase Orders & Service Orders')
@section('page_title', 'Purchase Orders & Service Orders')

@section('content')
    @php($canViewPrices = \App\Helpers\PermissionHelper::canViewPrices())
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    {{-- <h3 class="card-title">Purchase Orders & Service Orders</h3> --}}
                    <div class="card-tools">
                        @if (auth()->user()->hasAnyRole(['purchasing', 'admin', 'super_admin']))
                            <a href="{{ route('purchase_orders.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Create Order
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">

                    {{-- Tabs Navigation --}}
                    <ul class="nav nav-tabs" id="poTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="ppb-tab" data-toggle="tab" href="#ppb" role="tab">
                                <i class="fas fa-shopping-cart"></i> Purchase Orders (PPB)
                                <span class="badge badge-primary ml-1">{{ $purchaseOrders->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="ppj-tab" data-toggle="tab" href="#ppj" role="tab">
                                <i class="fas fa-wrench"></i> Service Orders (PPJ)
                                <span class="badge badge-warning ml-1">{{ $serviceOrders->count() }}</span>
                            </a>
                        </li>
                    </ul>

                    {{-- Tabs Content --}}
                    <div class="tab-content" id="poTabsContent">
                        {{-- PPB Tab --}}
                        <div class="tab-pane fade show active" id="ppb" role="tabpanel">
                            <div class="mt-3 mb-2 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between" style="gap: .5rem;">
                                <div class="d-flex align-items-center flex-wrap" style="gap: .5rem;">
                                    <input type="text" id="ppb-item-filter" class="form-control form-control-sm"
                                        placeholder="Filter by item name..." style="max-width:180px">
                                    <div class="input-group input-group-sm" style="max-width:210px">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Month</span>
                                        </div>
                                        <select id="ppb-month-filter" class="form-control">
                                            <option value="">All Months</option>
                                            @for ($i = 11; $i >= 0; $i--)
                                                @php($month = now()->subMonths($i))
                                                <option value="{{ $month->format('Y-m') }}">{{ $month->format('M Y') }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <select id="ppb-status-filter" class="form-control form-control-sm" style="max-width:145px">
                                        <option value="">All Status</option>
                                        <option value="on_progress">On Progress</option>
                                        <option value="approved">Approved</option>
                                        <option value="partial">Partial</option>
                                        <option value="received">Received</option>
                                        <option value="completed">Completed</option>
                                        <option value="closed_shortage">Closed with Shortage</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <button type="button" id="ppb-clear-filters" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-undo"></i> Clear
                                    </button>
                                </div>
                                <a href="{{ route('purchase_orders.export_excel', ['type' => 'purchase_order']) }}"
                                   class="btn btn-sm btn-success" id="ppb-export">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </a>
                            </div>
                            <table id="ppb-table" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>PO Number</th>
                                            <th>Supplier</th>
                                            <th>Order Date</th>
                                            <th>Items</th>
                                            @if ($canViewPrices)
                                                <th>Total Amount</th>
                                            @endif
                                            <th>Status</th>
                                            <th>Printed</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($purchaseOrders as $po)
                                            <tr
                                                data-items="{{ strtolower($po->details->pluck('item.name')->filter()->implode(' ')) }}"
                                                data-month="{{ $po->order_date->format('Y-m') }}"
                                                data-status="{{ $po->status }}">
                                                <td><strong>{{ $po->po_number }}</strong></td>
                                                <td>{{ $po->supplier_name }}</td>
                                                <td>{{ $po->order_date->format('M d, Y') }}</td>
                                                <td>{{ $po->details_count }} items</td>
                                                @if ($canViewPrices)
                                                    <td><strong>Rp {{ number_format($po->total_amount, 0, ',', '.') }}</strong></td>
                                                @endif
                                                <td>
                                                    <span
                                                        class="badge badge-{{ in_array($po->status, ['received', 'completed']) ? 'success' : ($po->status === 'approved' ? 'info' : ($po->status === 'partial' ? 'warning' : ($po->status === 'closed_shortage' ? 'dark' : ($po->status === 'cancelled' ? 'danger' : 'secondary')))) }}">
                                                        {{ $po->status === 'on_progress' ? 'On Progress' : ($po->status === 'closed_shortage' ? 'Closed with Shortage' : ucwords(str_replace('_', ' ', $po->status))) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($po->printed_at)
                                                        <span class="badge badge-primary"
                                                            title="Printed on {{ $po->printed_at->format('d M Y H:i') }}">
                                                            <i class="fas fa-print"></i>
                                                            {{ $po->printed_at->format('d M Y') }}
                                                        </span>
                                                    @else
                                                        <span class="badge badge-light text-muted">
                                                            <i class="fas fa-minus"></i> Not Printed
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('purchase_orders.show', $po) }}"
                                                        class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ $canViewPrices ? 8 : 7 }}" class="text-center text-muted">No Purchase Orders found
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                            </table>
                        </div>

                        {{-- PPJ Tab --}}
                        <div class="tab-pane fade" id="ppj" role="tabpanel">
                            <div class="mt-3 mb-2 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between" style="gap: .5rem;">
                                <div class="d-flex align-items-center flex-wrap" style="gap: .5rem;">
                                    <input type="text" id="ppj-item-filter" class="form-control form-control-sm"
                                        placeholder="Filter by service description..." style="max-width:180px">
                                    <div class="input-group input-group-sm" style="max-width:210px">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Month</span>
                                        </div>
                                        <select id="ppj-month-filter" class="form-control">
                                            <option value="">All Months</option>
                                            @for ($i = 11; $i >= 0; $i--)
                                                @php($month = now()->subMonths($i))
                                                <option value="{{ $month->format('Y-m') }}">{{ $month->format('M Y') }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <select id="ppj-status-filter" class="form-control form-control-sm" style="max-width:145px">
                                        <option value="">All Status</option>
                                        <option value="on_progress">On Progress</option>
                                        <option value="approved">Approved</option>
                                        <option value="partial">Partial</option>
                                        <option value="received">Received</option>
                                        <option value="completed">Completed</option>
                                        <option value="closed_shortage">Closed with Shortage</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <button type="button" id="ppj-clear-filters" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-undo"></i> Clear
                                    </button>
                                </div>
                                <a href="{{ route('purchase_orders.export_excel', ['type' => 'service_order']) }}"
                                   class="btn btn-sm btn-success" id="ppj-export">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </a>
                            </div>
                            <table id="ppj-table" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>SO Number</th>
                                            <th>Supplier</th>
                                            <th>Order Date</th>
                                            <th>Services</th>
                                            @if ($canViewPrices)
                                                <th>Total Amount</th>
                                            @endif
                                            <th>Status</th>
                                            <th>Printed</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($serviceOrders as $so)
                                            <tr
                                                data-items="{{ strtolower($so->details->pluck('service_description')->filter()->implode(' ')) }}"
                                                data-month="{{ $so->order_date->format('Y-m') }}"
                                                data-status="{{ $so->status }}">
                                                <td><strong>{{ $so->po_number }}</strong></td>
                                                <td>{{ $so->supplier_name }}</td>
                                                <td>{{ $so->order_date->format('M d, Y') }}</td>
                                                <td>{{ $so->details_count }} services</td>
                                                @if ($canViewPrices)
                                                    <td><strong>Rp {{ number_format($so->total_amount, 0, ',', '.') }}</strong></td>
                                                @endif
                                                <td>
                                                    <span
                                                        class="badge badge-{{ in_array($so->status, ['received', 'completed']) ? 'success' : ($so->status === 'approved' ? 'info' : ($so->status === 'partial' ? 'warning' : ($so->status === 'closed_shortage' ? 'dark' : ($so->status === 'cancelled' ? 'danger' : 'secondary')))) }}">
                                                        {{ $so->status === 'on_progress' ? 'On Progress' : ($so->status === 'closed_shortage' ? 'Closed with Shortage' : ucwords(str_replace('_', ' ', $so->status))) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($so->printed_at)
                                                        <span class="badge badge-primary"
                                                            title="Printed on {{ $so->printed_at->format('d M Y H:i') }}">
                                                            <i class="fas fa-print"></i>
                                                            {{ $so->printed_at->format('d M Y') }}
                                                        </span>
                                                    @else
                                                        <span class="badge badge-light text-muted">
                                                            <i class="fas fa-minus"></i> Not Printed
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('purchase_orders.show', $so) }}"
                                                        class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ $canViewPrices ? 8 : 7 }}" class="text-center text-muted">No Service Orders found
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script>
        var canViewPrices = @json($canViewPrices);

        $(document).ready(function() {
            function makeDtConfig() {
                return {
                    pageLength: 25,
                    order: [
                        [2, 'desc']
                    ],
                    dom: 'Bfrtip',
                    buttons: [{
                            extend: 'print',
                            className: 'btn btn-sm btn-secondary',
                            exportOptions: {
                                columns: [0, 1, 2, 3, 4, 5]
                            }
                        }
                    ],
                    columnDefs: [
                        {
                            type: 'date',
                            targets: 2
                        },
                        {
                            orderable: false,
                            targets: canViewPrices ? 7 : 6
                        }
                    ]
                };
            }

            function updateExportLink(type) {
                var month = $('#' + type + '-month-filter').val();
                var status = $('#' + type + '-status-filter').val();
                var baseUrl = $('#' + type + '-export').attr('href').split('?')[0];
                var params = {type: type === 'ppb' ? 'purchase_order' : 'service_order'};
                if (month) params.month = month;
                if (status) params.status = status;
                $('#' + type + '-export').attr('href', baseUrl + '?' + $.param(params));
            }

            $('#ppb-month-filter, #ppb-status-filter').on('change', function() {
                updateExportLink('ppb');
                ppbTable.draw();
            });

            $('#ppj-month-filter, #ppj-status-filter').on('change', function() {
                updateExportLink('ppj');
                if (ppjTable) ppjTable.draw();
            });

            updateExportLink('ppb');
            updateExportLink('ppj');

            // Item, month, and status filter: match against data-* attributes on each <tr>
            // Use settings.aoData[dataIndex].nTr to get the correct row node regardless of pagination
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                var tableId = settings.nTable.id;
                var rowNode = settings.aoData[dataIndex].nTr;
                if (!rowNode) return true;

                // Item filter
                var itemInput = tableId === 'ppb-table' ?
                    $('#ppb-item-filter').val() :
                    ($('#ppj-item-filter').val() || '');
                if (itemInput) {
                    var items = ($(rowNode).data('items') || '').toLowerCase();
                    if (items.indexOf(itemInput.toLowerCase()) === -1) return false;
                }

                // Month filter
                var monthInput = tableId === 'ppb-table' ?
                    $('#ppb-month-filter').val() :
                    $('#ppj-month-filter').val();
                if (monthInput && $(rowNode).data('month') !== monthInput) return false;

                // Status filter
                var statusInput = tableId === 'ppb-table' ?
                    $('#ppb-status-filter').val() :
                    $('#ppj-status-filter').val();
                if (statusInput && $(rowNode).data('status') !== statusInput) return false;

                return true;
            });

            // Init PPB table immediately (it's visible on load)
            var ppbTable = $('#ppb-table').DataTable(makeDtConfig());

            $('#ppb-item-filter').on('keyup', function() {
                ppbTable.draw();
            });

            // Defer PPJ table init until the tab is first shown (hidden table causes _DT_CellIndex error)
            var ppjTable = null;
            $('#ppj-tab').one('shown.bs.tab', function() {
                ppjTable = $('#ppj-table').DataTable(makeDtConfig());
                $('#ppj-item-filter').on('keyup', function() {
                    ppjTable.draw();
                });
            });

            // Clear filter buttons
            $('#ppb-clear-filters').on('click', function() {
                $('#ppb-item-filter, #ppb-month-filter, #ppb-status-filter').val('');
                updateExportLink('ppb');
                ppbTable.draw();
            });

            $('#ppj-clear-filters').on('click', function() {
                $('#ppj-item-filter, #ppj-month-filter, #ppj-status-filter').val('');
                updateExportLink('ppj');
                if (ppjTable) ppjTable.draw();
            });
        });
    </script>
@endpush
