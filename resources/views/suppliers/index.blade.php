@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endpush

@section('title', 'Suppliers')
@section('page_title', 'Suppliers Management')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Supplier List</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canCreate('suppliers'))
                            <a href="{{ route('suppliers.import') }}" class="btn btn-success btn-sm mr-1">
                                <i class="fas fa-file-import"></i> Import
                            </a>
                            <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add Supplier
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">

                    <table id="suppliers-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Purchase Orders</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($suppliers as $supplier)
                                <tr>
                                    <td><strong>{{ $supplier->name }}</strong></td>
                                    <td>{{ $supplier->contact_person ?? '-' }}</td>
                                    <td>{{ $supplier->phone ?? '-' }}</td>
                                    <td>{{ $supplier->email ?? '-' }}</td>
                                    <td>{{ $supplier->purchase_orders_count ?? 0 }}</td>
                                    <td>
                                        <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if (\App\Helpers\PermissionHelper::canUpdate('suppliers'))
                                            <a href="{{ route('suppliers.edit', $supplier) }}"
                                                class="btn btn-warning btn-sm">
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
@endsection

@push('scripts')
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#suppliers-table').DataTable({
                responsive: true,
                pageLength: 25,
                order: [
                    [0, 'asc']
                ],
                columnDefs: [{
                    orderable: false,
                    targets: 5
                }]
            });
        });
    </script>
@endpush
