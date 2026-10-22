@extends('layouts.admin')

@section('title', 'User Management')
@section('page_title', 'User Management')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Users</h3>
                    <div class="card-tools">
                        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Create New User
                        </a>
                    </div>
                </div>

                <div class="card-body">

                    <div class="table-responsive">
                        <table id="users-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">#</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Roles</th>
                                    <th>Created At</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td>{{ $user->id }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->username }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>
                                            @php
                                                $roles = $user->roleList();
                                                $roleStyles = [
                                                    'super_admin' => 'badge-dark',
                                                    'admin' => 'badge-danger',
                                                    'director' => 'badge-dark',
                                                    'manager' => 'badge-warning',
                                                    'purchasing' => 'badge-info',
                                                    'warehouse' => 'badge-warning',
                                                    'staff' => 'badge-primary',
                                                    'audit' => 'badge-success',
                                                ];
                                            @endphp
                                            @if (empty($roles))
                                                <span class="badge badge-secondary">No Role</span>
                                            @else
                                                @foreach ($roles as $role)
                                                    <span class="badge {{ $roleStyles[$role] ?? 'badge-secondary' }}">
                                                        {{ ucwords(str_replace('_', ' ', $role)) }}
                                                    </span>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>{{ $user->created_at ? $user->created_at->format('Y-m-d H:i') : '-' }}</td>
                                        <td>
                                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-info"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if ($user->id !== Auth::id())
                                                <form action="{{ route('users.destroy', $user) }}" method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
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
    </div>
@endsection

@push('scripts')

    <script>
        $(document).ready(function() {
            $('#users-table').DataTable({
                responsive: true,
                pageLength: 25,
                order: [
                    [5, 'desc']
                ],
                columnDefs: [{
                    orderable: false,
                    targets: 6
                }]
            });
        });
    </script>
@endpush
