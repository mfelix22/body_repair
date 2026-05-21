@extends('layouts.admin')

@section('title', 'Audit Logs')
@section('page_title', 'Audit Logs - Master Data History')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i> Audit Trail - All Changes
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-toggle="collapse" data-target="#filterCollapse">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>

                <!-- Filter Form -->
                <div id="filterCollapse"
                    class="collapse {{ request()->hasAny(['model_type', 'action', 'user_id', 'model_id']) ? 'show' : '' }}">
                    <div class="card-body border-bottom">
                        <form method="GET" action="{{ route('audit-logs.index') }}">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Data Type</label>
                                        <select name="model_type" class="form-control">
                                            <option value="">All Types</option>
                                            @foreach ($modelTypes as $type)
                                                <option value="{{ $type['value'] }}"
                                                    {{ request('model_type') === $type['value'] ? 'selected' : '' }}>
                                                    {{ $type['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Action</label>
                                        <select name="action" class="form-control">
                                            <option value="">All Actions</option>
                                            <option value="created" {{ request('action') === 'created' ? 'selected' : '' }}>
                                                Created</option>
                                            <option value="updated" {{ request('action') === 'updated' ? 'selected' : '' }}>
                                                Updated</option>
                                            <option value="deleted" {{ request('action') === 'deleted' ? 'selected' : '' }}>
                                                Deleted</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>User</label>
                                        <select name="user_id" class="form-control">
                                            <option value="">All Users</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}"
                                                    {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Record ID</label>
                                        <input type="number" name="model_id" class="form-control" placeholder="ID..."
                                            value="{{ request('model_id') }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                            @if (request()->hasAny(['model_type', 'action', 'user_id', 'model_id']))
                                                <a href="{{ route('audit-logs.index') }}"
                                                    class="btn btn-secondary btn-block mt-1">
                                                    <i class="fas fa-times"></i> Clear
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th style="width: 140px">Date & Time</th>
                                <th style="width: 100px">Action</th>
                                <th style="width: 120px">Data Type</th>
                                <th style="width: 80px">Record ID</th>
                                <th style="width: 120px">User</th>
                                <th>Changes</th>
                                <th style="width: 120px">IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($auditLogs as $log)
                                <tr>
                                    <td><small>{{ $log->created_at->format('M d, Y H:i:s') }}</small></td>
                                    <td>
                                        @if ($log->action === 'created')
                                            <span class="badge badge-success">Created</span>
                                        @elseif ($log->action === 'updated')
                                            <span class="badge badge-warning">Updated</span>
                                        @elseif ($log->action === 'deleted')
                                            <span class="badge badge-danger">Deleted</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $log->action }}</span>
                                        @endif
                                    </td>
                                    <td><code>{{ class_basename($log->model_type) }}</code></td>
                                    <td><strong>#{{ $log->model_id }}</strong></td>
                                    <td>{{ $log->user->name ?? 'System' }}</td>
                                    <td>
                                        @if ($log->action === 'created')
                                            <small class="text-muted">New record created</small>
                                        @elseif ($log->action === 'deleted')
                                            <small class="text-muted">Record deleted</small>
                                        @elseif ($log->action === 'updated' && $log->old_values && $log->new_values)
                                            <small>
                                                @php
                                                    $changes = array_keys($log->new_values);
                                                    $changeCount = count($changes);
                                                @endphp
                                                @if ($changeCount <= 3)
                                                    Changed: <strong>{{ implode(', ', $changes) }}</strong>
                                                @else
                                                    Changed {{ $changeCount }} fields:
                                                    {{ implode(', ', array_slice($changes, 0, 2)) }}, ...
                                                @endif
                                            </small>
                                            <br>
                                            <button type="button" class="btn btn-xs btn-outline-info mt-1"
                                                onclick="showDetails({{ json_encode($log->old_values) }}, {{ json_encode($log->new_values) }})">
                                                <i class="fas fa-eye"></i> View Details
                                            </button>
                                        @endif
                                    </td>
                                    <td><small>{{ $log->ip_address }}</small></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No audit logs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer clearfix d-flex justify-content-end">
                    {{ $auditLogs->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Details</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th style="width: 30%">Field</th>
                                <th style="width: 35%">Old Value</th>
                                <th style="width: 35%">New Value</th>
                            </tr>
                        </thead>
                        <tbody id="detailsTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function showDetails(oldValues, newValues) {
            const tbody = document.getElementById('detailsTableBody');
            tbody.innerHTML = '';

            const allFields = new Set([...Object.keys(oldValues || {}), ...Object.keys(newValues || {})]);

            allFields.forEach(field => {
                const oldVal = oldValues?.[field] ?? '-';
                const newVal = newValues?.[field] ?? '-';

                if (oldVal !== newVal) {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td><strong>${field}</strong></td>
                        <td><span class="text-danger">${escapeHtml(oldVal)}</span></td>
                        <td><span class="text-success">${escapeHtml(newVal)}</span></td>
                    `;
                    tbody.appendChild(row);
                }
            });

            $('#detailsModal').modal('show');
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) return '-';
            const div = document.createElement('div');
            div.textContent = String(text);
            return div.innerHTML;
        }
    </script>
@endpush
