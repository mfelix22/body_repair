@extends('layouts.admin')

@section('title', 'Insurance Master')
@section('page_title', 'Insurance Master')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Insurance List</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canCreate('insurances'))
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                                data-target="#addInsuranceModal">
                                <i class="fas fa-plus"></i> Add Insurance
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover table-sm" id="insuranceTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Name</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:120px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($insurances as $insurance)
                                <tr>
                                    <td>{{ $insurance->name }}</td>
                                    <td class="text-center">
                                        @if ($insurance->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if (\App\Helpers\PermissionHelper::canUpdate('insurances'))
                                            <a href="{{ route('insurances.edit', $insurance) }}"
                                                class="btn btn-warning btn-xs">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @if (\App\Helpers\PermissionHelper::canDelete('insurances'))
                                            <form action="{{ route('insurances.destroy', $insurance) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Delete {{ $insurance->name }}?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-danger btn-xs">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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

    @if (\App\Helpers\PermissionHelper::canCreate('insurances'))
        <div class="modal fade" id="addInsuranceModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Insurance</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <form action="{{ route('insurances.store') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="name">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>
                            <div class="form-group mb-0">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                        value="1" checked>
                                    <label class="custom-control-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        $(function() {
            $('#insuranceTable').DataTable({
                order: [
                    [0, 'asc']
                ],
                pageLength: 25,
            });
        });
    </script>
@endpush
