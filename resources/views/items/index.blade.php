@extends('layouts.admin')

@section('title', 'Items')
@section('page_title', 'Items Management')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Item List</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canCreate('items'))
                            <a href="{{ route('items.import') }}" class="btn btn-success btn-sm mr-1">
                                <i class="fas fa-file-excel"></i> Import Excel
                            </a>
                            <a href="{{ route('items.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add Item
                            </a>
                        @endif
                    </div>
                </div>



                <div class="card-body">

                    <div class="table-responsive">
                        <table id="itemsTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Smallest UOM</th>
                                    <th>Current Stock</th>
                                    <th>Reorder Level</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr
                                        class="{{ ($item->stocks_sum_quantity ?? 0) <= $item->reorder_level ? 'table-warning' : '' }}">
                                        <td>
                                            <span class="badge badge-primary">{{ $item->item_type }}</span>
                                        </td>
                                        <td><strong>{{ $item->code }}</strong></td>
                                        <td>{{ $item->name }}</td>
                                        <td>{{ $item->category ?? '-' }}</td>
                                        <td>{{ $item->smallestUom->code }}</td>
                                        <td>
                                            {{ number_format($item->stocks_sum_quantity ?? 0, 2) }}
                                            {{ $item->smallestUom->code }}
                                            @if (($item->stocks_sum_quantity ?? 0) <= $item->reorder_level)
                                                <span class="badge badge-warning ml-1">Low Stock</span>
                                            @endif
                                        </td>
                                        <td>{{ number_format($item->reorder_level, 2) }}</td>
                                        <td>
                                            @if ($item->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('items.show', $item) }}" class="btn btn-info btn-sm"
                                                title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if (\App\Helpers\PermissionHelper::canUpdate('items'))
                                                <a href="{{ route('items.edit', $item) }}" class="btn btn-warning btn-sm"
                                                    title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(function() {
            $('#itemsTable').DataTable({
                responsive: true,
                lengthChange: true,
                searching: true,
                ordering: true,
                info: true,
                paging: true,
                pageLength: 25,
                language: {
                    search: "🔍 Filter Items:",
                    lengthMenu: "Show _MENU_ items",
                    info: "Showing _START_ to _END_ of _TOTAL_ items",
                    infoEmpty: "No items found",
                    infoFiltered: "(filtered from _MAX_ total items)",
                    emptyTable: "No items available",
                }
            });
        });
    </script>
@endpush
