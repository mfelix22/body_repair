@extends('layouts.admin')

@section('title', 'Master Data Review')
@section('page_title', 'Master Data Review - Warehouse Changes')

@push('styles')
    <style>
        /* Ensure visibility of timeline elements in light mode */
        .timeline-inverse .time-label>span {
            color: white !important;
            font-weight: 600;
        }

        /* Badge styling for better contrast */
        .badge {
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #333;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }

        .badge-info {
            background-color: #17a2b8;
            color: white;
        }

        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }

        /* Ensure table headers are visible */
        .table thead th {
            background-color: #f8f9fa;
            color: #333;
            font-weight: 600;
            border: 1px solid #dee2e6;
        }

        /* Code elements styling */
        code {
            background-color: #f4f4f4;
            color: #333;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }

        /* Alert styling */
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
        }

        /* Timeline icon styling */
        .timeline>div>i {
            color: white !important;
        }

        /* Ensure text is readable in all elements */
        .timeline-body {
            color: #333;
        }

        .timeline-item {
            color: #666;
        }
    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-eye"></i> Master Data Changes - Watch & Review
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool d-inline-flex align-items-center" data-toggle="collapse" data-target="#filterCollapse">
                            <i class="fas fa-filter mr-1"></i>Advanced Filters
                        </button>
                    </div>
                </div>

                <!-- Filter Form -->
                <div id="filterCollapse"
                    class="collapse {{ request()->hasAny(['model_type', 'action', 'user_id', 'date_from', 'date_to']) ? 'show' : '' }}">
                    <div class="card-body border-bottom bg-light">
                        <form method="GET" action="{{ route('audit-logs.review') }}">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label><strong>Data Type</strong></label>
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
                                        <label><strong>Action</strong></label>
                                        <select name="action" class="form-control">
                                            <option value="">All Actions</option>
                                            <option value="created" {{ request('action') === 'created' ? 'selected' : '' }}>
                                                <i class="fas fa-plus"></i> Created
                                            </option>
                                            <option value="updated" {{ request('action') === 'updated' ? 'selected' : '' }}>
                                                <i class="fas fa-edit"></i> Updated
                                            </option>
                                            <option value="deleted" {{ request('action') === 'deleted' ? 'selected' : '' }}>
                                                <i class="fas fa-trash"></i> Deleted
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label><strong>Changed By</strong></label>
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
                                        <label><strong>From Date</strong></label>
                                        <input type="date" name="date_from" class="form-control"
                                            value="{{ request('date_from') }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label><strong>To Date</strong></label>
                                        <input type="date" name="date_to" class="form-control"
                                            value="{{ request('date_to') }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-warning btn-block">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                            @if (request()->hasAny(['model_type', 'action', 'user_id', 'date_from', 'date_to']))
                                                <a href="{{ route('audit-logs.review') }}"
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

                <div class="card-body">
                    @if ($auditLogs->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No changes found. Warehouse team is playing it safe! ✓
                        </div>
                    @else
                        <div class="timeline timeline-inverse">
                            @foreach ($auditLogs as $log)
                                <div class="time-label">
                                    <span
                                        class="bg-{{ $log->action === 'created' ? 'success' : ($log->action === 'updated' ? 'warning' : 'danger') }}">
                                        {{ $log->created_at->format('M d, Y - H:i:s') }}
                                    </span>
                                </div>
                                <div>
                                    <i
                                        class="fas {{ $log->action === 'created' ? 'fa-plus bg-green' : ($log->action === 'updated' ? 'fa-edit bg-orange' : 'fa-trash bg-red') }}"></i>
                                    <div class="timeline-item">
                                        <span
                                            class="badge badge-{{ $log->action === 'created' ? 'success' : ($log->action === 'updated' ? 'warning' : 'danger') }}"
                                            style="font-size: 11px; padding: 6px 12px;">
                                            {{ strtoupper($log->action) }}
                                        </span>
                                        <span class="badge badge-info" style="font-size: 11px; padding: 6px 12px;">
                                            {{ class_basename($log->model_type) }} #{{ $log->model_id }}
                                        </span>
                                        <span class="badge badge-secondary" style="font-size: 11px; padding: 6px 12px;">
                                            by {{ $log->user->name ?? 'System' }}
                                        </span>
                                        <span class="badge badge-light text-dark"
                                            style="font-size: 11px; padding: 6px 12px; border: 1px solid #ccc;">
                                            {{ $log->ip_address }}
                                        </span>

                                        <div class="timeline-body mt-3">
                                            @if ($log->action === 'created')
                                                <div class="alert alert-success" style="margin-bottom: 10px;">
                                                    <strong>New {{ class_basename($log->model_type) }} Created</strong>
                                                    <div class="mt-2">
                                                        <table class="table table-sm table-bordered"
                                                            style="margin-bottom: 0; background: white;">
                                                            <thead>
                                                                <tr style="background: #f5f5f5;">
                                                                    <th style="width: 30%;">Field</th>
                                                                    <th>Value</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @if ($log->new_values)
                                                                    @foreach ($log->new_values as $field => $value)
                                                                        <tr>
                                                                            <td><strong>{{ ucfirst(str_replace('_', ' ', $field)) }}</strong>
                                                                            </td>
                                                                            <td>
                                                                                <code>{{ is_array($value) ? json_encode($value) : $value }}</code>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                @endif
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @elseif ($log->action === 'deleted')
                                                <div class="alert alert-danger" style="margin-bottom: 10px;">
                                                    <strong>{{ class_basename($log->model_type) }} Deleted ❌</strong>
                                                    <div class="mt-2">
                                                        <table class="table table-sm table-bordered"
                                                            style="margin-bottom: 0; background: white;">
                                                            <thead>
                                                                <tr style="background: #f5f5f5;">
                                                                    <th style="width: 30%;">Field</th>
                                                                    <th>Previous Value</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @if ($log->old_values)
                                                                    @foreach ($log->old_values as $field => $value)
                                                                        <tr>
                                                                            <td><strong>{{ ucfirst(str_replace('_', ' ', $field)) }}</strong>
                                                                            </td>
                                                                            <td>
                                                                                <code>{{ is_array($value) ? json_encode($value) : $value }}</code>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                @endif
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @elseif ($log->action === 'updated')
                                                <div class="alert alert-warning" style="margin-bottom: 10px;">
                                                    <strong>Changes Made:</strong>
                                                    <div class="mt-2">
                                                        <table class="table table-sm table-bordered"
                                                            style="margin-bottom: 0; background: white;">
                                                            <thead>
                                                                <tr style="background: #f5f5f5;">
                                                                    <th style="width: 25%;">Field</th>
                                                                    <th style="width: 37.5%;">Old Value</th>
                                                                    <th style="width: 37.5%;">New Value</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @if ($log->new_values)
                                                                    @php
                                                                        $changedFields = array_keys($log->new_values);
                                                                    @endphp
                                                                    @foreach ($changedFields as $field)
                                                                        <tr>
                                                                            <td>
                                                                                <strong>{{ ucfirst(str_replace('_', ' ', $field)) }}</strong>
                                                                            </td>
                                                                            <td style="background: #ffe6e6;">
                                                                                <code>{{ isset($log->old_values[$field]) ? (is_array($log->old_values[$field]) ? json_encode($log->old_values[$field]) : $log->old_values[$field]) : '(empty)' }}</code>
                                                                            </td>
                                                                            <td style="background: #e6ffe6;">
                                                                                <code>{{ is_array($log->new_values[$field]) ? json_encode($log->new_values[$field]) : $log->new_values[$field] }}</code>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                @endif
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($auditLogs->hasPages())
                    <div class="card-footer">
                        {{ $auditLogs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 30px;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #e9ecef;
        }

        .timeline>div:last-child::after {
            content: none;
        }

        .time-label {
            margin: 10px 0;
            padding: 4px 12px;
            background: #e9ecef;
            color: #666;
            display: inline-block;
            border-radius: 4px;
            margin-left: 50px;
            font-weight: bold;
            font-size: 12px;
        }

        .timeline-item {
            margin-left: 50px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
            border-left: 3px solid #dee2e6;
            margin-bottom: 20px;
        }

        .timeline>div>i {
            position: absolute;
            left: 15px;
            top: 30px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            z-index: 1;
        }

        .bg-green {
            background: #28a745;
        }

        .bg-orange {
            background: #ffc107;
            color: #333 !important;
        }

        .bg-red {
            background: #dc3545;
        }
    </style>
@endsection
