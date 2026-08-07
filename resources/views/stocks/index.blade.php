@extends('layouts.admin')

@section('title', 'Stock Management')
@section('page_title', 'Stock Management')

@section('content')
    <div class="row">
        <div class="col-12">
            @if ($lowStockCount > 0)
                <div class="alert alert-warning">
                    <h5><i class="icon fas fa-exclamation-triangle"></i> Low Stock Alert!</h5>
                    {{ $lowStockCount }} item(s) are below reorder level.
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Current Stock</h3>
                    <div class="card-tools">
                        <a href="{{ route('stocks.export') }}" class="btn btn-primary btn-sm"
                            title="Export for Stock Opname">
                            <i class="fas fa-file-excel"></i> Export Stock
                        </a>
                        @if (\App\Helpers\PermissionHelper::canViewPrices())
                            <a href="{{ route('stocks.export_prices') }}" class="btn btn-success btn-sm"
                                title="Export Stock with Prices">
                                <i class="fas fa-file-excel"></i> Export with Prices
                            </a>
                        @endif
                        <a href="{{ route('stocks.transactions') }}" class="btn btn-info btn-sm">
                            <i class="fas fa-history"></i> Transactions
                        </a>
                        @if (\App\Helpers\PermissionHelper::canAdjustStock())
                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal"
                                data-target="#adjustStockModal">
                                <i class="fas fa-edit"></i> Adjust Stock
                            </button>
                        @endif
                    </div>
                </div>

                <div class="card-body">

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="item-type-filter">Item Type</label>
                            <select id="item-type-filter" class="form-control form-control-sm">
                                <option value="">All Types</option>
                                @foreach ($itemTypes as $key => $type)
                                    <option value="{{ $key }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status-filter">Status</label>
                            <select id="status-filter" class="form-control form-control-sm">
                                <option value="">All Status</option>
                                <option value="Good">Good</option>
                                <option value="Low">Low</option>
                                <option value="Out of Stock">Out of Stock</option>
                            </select>
                        </div>
                    </div>

                    @php
                        $canViewCost = auth()->user() && auth()->user()->hasAnyRole(['accounting', 'warehouse', 'director', 'viewer', 'super_admin']);
                    @endphp

                    <table class="table table-bordered table-striped table-hover" id="stocks-table">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Item Type</th>
                                <th>Current Stock</th>
                                <th>Reorder Level</th>
                                <th>Status</th>
                                @if ($canViewCost)
                                    <th>Unit Cost</th>
                                @endif
                                <th>Alternative UOMs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stocks as $stock)
                                @php
                                    $statusKey =
                                        $stock->quantity > $stock->item->reorder_level
                                            ? 'good'
                                            : ($stock->quantity > 0
                                                ? 'low'
                                                : 'out_of_stock');
                                @endphp
                                <tr class="{{ $stock->quantity <= $stock->item->reorder_level ? 'table-warning' : '' }}">
                                    <td><strong>{{ $stock->item->code }}</strong></td>
                                    <td>{{ $stock->item->name }}</td>
                                    <td>{{ $stock->item->item_type_name }}</td>
                                    <td data-order="{{ $stock->quantity }}">
                                        {{ number_format($stock->quantity, 2) }} {{ $stock->item->smallestUom->code }}
                                        @if ($stock->quantity <= $stock->item->reorder_level && $stock->quantity > 0)
                                            <span class="badge badge-warning ml-1">Low</span>
                                        @endif
                                    </td>
                                    <td data-order="{{ $stock->item->reorder_level }}">
                                        {{ number_format($stock->item->reorder_level, 2) }}
                                        {{ $stock->item->smallestUom->code }}
                                    </td>
                                    <td>
                                        @if ($stock->quantity > $stock->item->reorder_level)
                                            <span class="badge badge-success">Good</span>
                                        @elseif($stock->quantity > 0)
                                            <span class="badge badge-warning">Low</span>
                                        @else
                                            <span class="badge badge-danger">Out of Stock</span>
                                        @endif
                                    </td>
                                    @if ($canViewCost)
                                        <td>
                                            @if ($stock->avg_cost > 0)
                                                <span class="text-monospace">Rp {{ number_format($stock->avg_cost, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td>
                                        @foreach ($stock->item->itemUoms as $itemUom)
                                            @if ($itemUom->uom_id !== $stock->item->smallest_uom_id)
                                                <small class="text-muted">
                                                    {{ number_format($stock->quantity / $itemUom->conversion_to_smallest, 2) }}
                                                    {{ $itemUom->uom->code }}
                                                </small><br>
                                            @endif
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <div class="modal fade" id="adjustStockModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('stocks.adjust') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h4 class="modal-title">Adjust Stock</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong><i class="fas fa-info-circle"></i> When to use this:</strong>
                            <ul class="mb-0 pl-3">
                                <li>Physical stock count differences</li>
                                <li>Damaged or expired items</li>
                                <li>System corrections</li>
                            </ul>
                            <small>For normal receiving use <strong>Bon In</strong>, for issuing use <strong>Bon
                                    Out</strong>.</small>
                        </div>

                        <div class="form-group">
                            <label for="item_id">Item</label>
                            <select name="item_id" id="item_id" class="form-control select2" required>
                                <option value="">Select Item</option>
                                @foreach ($adjustableItems as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}
                                        ({{ $item->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="adjustment_type">Adjustment Type</label>
                            <select name="adjustment_type" id="adjustment_type" class="form-control select2" required>
                                <option value="set">Set to specific quantity</option>
                                <option value="add">Add quantity</option>
                                <option value="subtract">Subtract quantity</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="quantity">Quantity</label>
                            <input type="number" name="quantity" id="quantity" class="form-control" step="0.01"
                                min="0" required>
                        </div>

                        <div class="form-group">
                            <label for="notes">Reason / Notes <span class="text-danger">*</span></label>
                            <textarea name="notes" id="notes" class="form-control" rows="3"
                                placeholder="e.g., Physical count difference, Damaged items, System correction..." required></textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Please explain why you're adjusting.
                                For normal operations, use Bon In (receiving) or Bon Out (issuing).
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Adjust Stock</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')

    <script>
        $(document).ready(function() {
            var table = $('#stocks-table').DataTable({
                responsive: true,
                pageLength: 25,
                order: [
                    [1, 'asc']
                ], // Sort by item name by default
                columnDefs: [{
                        orderable: false,
                        targets: [7]
                    } // Disable sorting on Alternative UOMs column
                ],
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ items per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ items",
                    infoEmpty: "No items to display",
                    infoFiltered: "(filtered from _MAX_ total items)"
                }
            });

            // Item Type filter
            $('#item-type-filter').on('change', function() {
                var selectedValue = $(this).val();
                if (selectedValue === '') {
                    table.column(2).search('').draw();
                } else {
                    // Search by the item type name (displayed value)
                    var itemTypeName = $(this).find('option:selected').text();
                    table.column(2).search(itemTypeName).draw();
                }
            });

            // Status filter
            $('#status-filter').on('change', function() {
                var value = $(this).val();
                table.column(5).search(value).draw();
            });
        });
    </script>
@endpush
