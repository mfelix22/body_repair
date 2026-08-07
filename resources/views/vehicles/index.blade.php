@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
@endpush

@section('title', 'Vehicles')
@section('page_title', 'Vehicle Master')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Vehicle List</h3>
                    <div class="card-tools">
                        <a href="{{ route('customers.export') }}" class="btn btn-info btn-sm mr-2">
                            <i class="fas fa-download"></i> Export Excel (Customers + Vehicles)
                        </a>
                        @if (\App\Helpers\PermissionHelper::canCreate('vehicles'))
                            <a href="{{ route('vehicles.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Register Vehicle
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">

                    <table id="vehicles-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Plate Number</th>
                                <th>Brand / Model</th>
                                <th>Year</th>
                                <th>Color</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vehicles as $vehicle)
                                <tr>
                                    <td><strong>{{ $vehicle->plate_number }}</strong></td>
                                    <td>{{ $vehicle->brand }} {{ $vehicle->model }}</td>
                                    <td>{{ $vehicle->year ?? '-' }}</td>
                                    <td>{{ $vehicle->color ?? '-' }}</td>
                                    <td>
                                        @if ($vehicle->customer)
                                            <a href="{{ route('customers.show', $vehicle->customer) }}">
                                                {{ $vehicle->customer->name }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if ($vehicle->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No vehicles registered yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
    
    <script>
        $(document).ready(function() {
            $('#vehicles-table').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'asc']], // Sort by plate number
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'copy',
                        className: 'btn btn-sm btn-secondary',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5] // Exclude Actions column
                        }
                    },
                    {
                        extend: 'excel',
                        className: 'btn btn-sm btn-success',
                        title: 'Vehicles_' + new Date().toISOString().split('T')[0],
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5]
                        }
                    },
                    {
                        extend: 'pdf',
                        className: 'btn btn-sm btn-danger',
                        title: 'Vehicles_' + new Date().toISOString().split('T')[0],
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5]
                        },
                        orientation: 'landscape',
                        pageSize: 'A4'
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-sm btn-info',
                        title: 'Vehicles List',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5]
                        }
                    }
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search vehicles..."
                }
            });
        });
    </script>
@endsection
