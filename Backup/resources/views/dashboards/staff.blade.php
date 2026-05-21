@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'My Dashboard')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline card-primary mb-0">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">Welcome, {{ auth()->user()->name }}</h5>
                            <p class="text-muted mb-0">{{ now()->format('l, d M Y') }}</p>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <a href="{{ route('purchase_requests.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> New Purchase Request
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Boxes --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $summary['my_prs_total'] }}</h3>
                    <p>My Total PRs</p>
                </div>
                <div class="icon"><i class="fas fa-file-alt"></i></div>
                <a href="{{ route('purchase_requests.index') }}" class="small-box-footer">View All <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $summary['my_prs_pending'] }}</h3>
                    <p>Pending Approval</p>
                </div>
                <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                <a href="{{ route('purchase_requests.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $summary['my_prs_approved'] }}</h3>
                    <p>GM Approved</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <a href="{{ route('purchase_requests.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $summary['my_prs_completed'] }}</h3>
                    <p>Completed</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-check"></i></div>
                <a href="{{ route('purchase_requests.index') }}" class="small-box-footer">View <i
                        class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i> My Purchase Requests</h3>
                    <div class="card-tools">
                        <a href="{{ route('purchase_requests.create') }}" class="btn btn-sm btn-primary mr-1">
                            <i class="fas fa-plus"></i> New
                        </a>
                        <a href="{{ route('purchase_requests.index') }}" class="btn btn-sm btn-default">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>PR #</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($myPurchaseRequests as $pr)
                                <tr>
                                    <td>{{ $pr->pr_number }}</td>
                                    <td>{{ $pr->request_date?->format('d M Y') }}</td>
                                    <td>{{ strtoupper($pr->type ?? '-') }}</td>
                                    <td>
                                        @php
                                            $prClass = match ($pr->status) {
                                                'on_progress' => 'secondary',
                                                'dept_head_approved' => 'info',
                                                'gm_approved' => 'primary',
                                                'completed' => 'success',
                                                'rejected' => 'danger',
                                                'cancelled' => 'danger',
                                                default => 'secondary',
                                            };
                                            $prLabel = match ($pr->status) {
                                                'on_progress' => 'Pending Dept Head',
                                                'dept_head_approved' => 'Pending GM',
                                                'gm_approved' => 'GM Approved',
                                                'completed' => 'Completed',
                                                'rejected' => 'Rejected',
                                                'cancelled' => 'Cancelled',
                                                default => ucwords(str_replace('_', ' ', $pr->status)),
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $prClass }}">{{ $prLabel }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('purchase_requests.show', $pr) }}"
                                            class="btn btn-xs btn-default">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No purchase requests yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
