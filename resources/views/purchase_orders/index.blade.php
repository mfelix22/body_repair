@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
@endpush

@section('title', 'Purchase Orders & Service Orders')
@section('page_title', 'Purchase Orders & Service Orders')

@section('content')
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
                            <div class="mt-3 mb-2 d-flex align-items-center gap-2">
                                <input type="text" id="ppb-item-filter" class="form-control form-control-sm"
                                    placeholder="Filter by item name..." style="max-width:320px">
                                <a href="{{ route('purchase_orders.export_excel', ['type' => 'purchase_order']) }}"
                                   class="btn btn-sm btn-success ml-2">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table id="ppb-table" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>PO Number</th>
                                            <th>Supplier</th>
                                            <th>Order Date</th>
                                            <th>Items</th>
                                            @if (\App\Helpers\PermissionHelper::canViewPrices())
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
                                                data-items="{{ strtolower($po->details->pluck('item.name')->filter()->implode(' ')) }}">
                                                <td><strong>{{ $po->po_number }}</strong></td>
                                                <td>{{ $po->supplier_name }}</td>
                                                <td>{{ $po->order_date->format('M d, Y') }}</td>
                                                <td>{{ $po->details_count }} items</td>
                                                @if (\App\Helpers\PermissionHelper::canViewPrices())
                                                    <td><strong>Rp {{ number_format($po->total_amount, 0, ',', '.') }}</strong></td>
                                                @endif
                                                <td>
                                                    <span
                                                        class="badge badge-{{ in_array($po->status, ['received', 'completed']) ? 'success' : ($po->status === 'approved' ? 'info' : ($po->status === 'partial' ? 'warning' : ($po->status === 'closed_shortage' ? 'dark' : ($po->status === 'cancelled' ? 'danger' : 'secondary')))) }}">
                                                        {{ $po->status === 'on_progress' ? 'On Progress' : ($po->status === 'closed_shortage' ? 'Closed with Shortage' : ucfirst($po->status)) }}
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
                                                <td colspan="8" class="text-center text-muted">No Purchase Orders found
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- PPJ Tab --}}
                        <div class="tab-pane fade" id="ppj" role="tabpanel">
                            <div class="mt-3 mb-2 d-flex align-items-center gap-2">
                                <input type="text" id="ppj-item-filter" class="form-control form-control-sm"
                                    placeholder="Filter by service description..." style="max-width:320px">
                                <a href="{{ route('purchase_orders.export_excel', ['type' => 'service_order']) }}"
                                   class="btn btn-sm btn-success ml-2">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table id="ppj-table" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>SO Number</th>
                                            <th>Supplier</th>
                                            <th>Order Date</th>
                                            <th>Services</th>
                                            @if (\App\Helpers\PermissionHelper::canViewPrices())
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
                                                data-items="{{ strtolower($so->details->pluck('service_description')->filter()->implode(' ')) }}">
                                                <td><strong>{{ $so->po_number }}</strong></td>
                                                <td>{{ $so->supplier_name }}</td>
                                                <td>{{ $so->order_date->format('M d, Y') }}</td>
                                                <td>{{ $so->details_count }} services</td>
                                                @if (\App\Helpers\PermissionHelper::canViewPrices())
                                                    <td><strong>Rp {{ number_format($so->total_amount, 0, ',', '.') }}</strong></td>
                                                @endif
                                                <td>
                                                    <span
                                                        class="badge badge-{{ in_array($so->status, ['received', 'completed']) ? 'success' : ($so->status === 'approved' ? 'info' : ($so->status === 'partial' ? 'warning' : ($so->status === 'closed_shortage' ? 'dark' : ($so->status === 'cancelled' ? 'danger' : 'secondary')))) }}">
                                                        {{ $so->status === 'on_progress' ? 'On Progress' : ($so->status === 'closed_shortage' ? 'Closed with Shortage' : ucfirst($so->status)) }}
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
                                                <td colspan="8" class="text-center text-muted">No Service Orders found
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
        $(document).ready(function() {
            function makeDtConfig() {
                return {
                    autoWidth: false,
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
                            targets: 7
                        }
                    ]
                };
            }

            // Item filter: match against data-items attribute on each <tr>
            // Use settings.aoData[dataIndex].nTr to get the correct row node regardless of pagination
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                var tableId = settings.nTable.id;
                var input = tableId === 'ppb-table' ?
                    $('#ppb-item-filter').val() :
                    ($('#ppj-item-filter').val() || '');
                if (!input) return true;
                var rowNode = settings.aoData[dataIndex].nTr;
                if (!rowNode) return true;
                var items = ($(rowNode).data('items') || '').toLowerCase();
                return items.indexOf(input.toLowerCase()) !== -1;
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
        });
    </script>
@endpush
