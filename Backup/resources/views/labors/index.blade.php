@extends('layouts.admin')
@section('title', 'Labor Master')
@section('page_title', 'Labor Master')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Labor Master List</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canCreate('labors'))
                            <a href="{{ route('labors.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add Labor
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table class="table table-bordered table-hover" id="laborTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Code</th>
                                <th>Description</th>
                                <th class="text-right">Price (Rp)</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:120px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($labors as $labor)
                                <tr>
                                    <td><strong>{{ $labor->labor_code }}</strong></td>
                                    <td>{{ $labor->description }}</td>
                                    <td class="text-right">{{ number_format($labor->price, 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if ($labor->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if (\App\Helpers\PermissionHelper::canUpdate('labors'))
                                            <a href="{{ route('labors.edit', $labor) }}" class="btn btn-warning btn-xs">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @if (\App\Helpers\PermissionHelper::canDelete('labors'))
                                            <form action="{{ route('labors.destroy', $labor) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Delete {{ $labor->labor_code }}?')">
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
@endsection

@push('scripts')
    <script>
        $(function() {
            $('#laborTable').DataTable({
                order: [
                    [0, 'asc']
                ],
                pageLength: 25
            });
        });
    </script>
@endpush
