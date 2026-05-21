@extends('layouts.admin')

@section('title', 'PPB & PPJ')
@section('page_title', 'PPB & PPJ')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">PPB & PPJ</h3>
                    <div class="card-tools">
                        @if (auth()->user()->hasAnyRole(['staff', 'warehouse', 'admin', 'super_admin']))
                            <a href="{{ route('purchase_requests.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Create PPB/PPJ
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">

                    <!-- Filter by Type -->
                    <div class="mb-3">
                        <form method="GET" class="form-inline">
                            <label class="mr-2">Filter by Type:</label>
                            <select name="type" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                <option value="Jasa" {{ request('type') === 'Jasa' ? 'selected' : '' }}>PPJ (Service)
                                </option>
                                <option value="Barang" {{ request('type') === 'Barang' ? 'selected' : '' }}>PPB (Items)
                                </option>
                            </select>
                        </form>
                    </div>

                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Number</th>
                                <th>Type</th>
                                <th>Request Date</th>
                                <th>Requested By</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($prs as $pr)
                                @php
                                    $canCancelPr = false;
                                @endphp
                                <tr>
                                    <td>{{ $pr->pr_number }}</td>
                                    <td>
                                        <span class="badge badge-{{ $pr->type === 'Jasa' ? 'info' : 'primary' }}">
                                            {{ $pr->type === 'Jasa' ? 'PPJ' : 'PPB' }}
                                        </span>
                                    </td>
                                    <td>{{ $pr->request_date->format('M d, Y') }}</td>
                                    <td>{{ $pr->requestor->name }}</td>
                                    <td>{{ $pr->details_count }} items</td>
                                    <td>
                                        <span
                                            class="badge badge-{{ in_array($pr->status, ['dept_head_approved', 'gm_approved', 'completed']) ? 'success' : (in_array($pr->status, ['rejected', 'cancelled']) ? 'danger' : ($pr->status === 'closed' ? 'dark' : 'secondary')) }}">
                                            {{ $pr->status === 'closed' ? 'Closed' : ucwords(str_replace('_', ' ', $pr->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('purchase_requests.show', $pr) }}" class="btn btn-info btn-sm"
                                            title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        @if ($pr->status === 'on_progress')
                                            @if (auth()->user()->hasAnyRole(['staff', 'warehouse', 'admin', 'super_admin']) && $pr->requested_by === auth()->id())
                                                <a href="{{ route('purchase_requests.edit', $pr) }}"
                                                    class="btn btn-warning btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif

                                            @if (auth()->user()->hasAnyRole(['manager']) && $pr->requested_by !== auth()->id())
                                                <form action="{{ route('purchase_requests.approve', $pr) }}" method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to approve this PR?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success btn-sm" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @php
                                                $canCancelPr = false;
                                                if (
                                                    auth()
                                                        ->user()
                                                        ->hasAnyRole(['admin', 'super_admin'])
                                                ) {
                                                    $canCancelPr = true;
                                                } elseif (
                                                    $pr->status === 'on_progress' &&
                                                    ($pr->requested_by === auth()->id() ||
                                                        auth()
                                                            ->user()
                                                            ->hasAnyRole(['manager', 'director']))
                                                ) {
                                                    $canCancelPr = true;
                                                }
                                            @endphp

                                            @if ($canCancelPr)
                                                <button type="button" class="btn btn-danger btn-sm" title="Cancel"
                                                    data-toggle="modal" data-target="#cancelPrModal{{ $pr->id }}">
                                                    <i class="fas fa-ban"></i>
                                                </button>

                                                <div class="modal fade" id="cancelPrModal{{ $pr->id }}"
                                                    tabindex="-1" role="dialog"
                                                    aria-labelledby="cancelPrModalLabel{{ $pr->id }}"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <form action="{{ route('purchase_requests.cancel', $pr) }}"
                                                                method="POST">
                                                                @csrf
                                                                <div class="modal-header bg-danger text-white">
                                                                    <h5 class="modal-title"
                                                                        id="cancelPrModalLabel{{ $pr->id }}">
                                                                        <i class="fas fa-ban"></i> Cancel PPB/PPJ
                                                                    </h5>
                                                                    <button type="button" class="close text-white"
                                                                        data-dismiss="modal"><span>&times;</span></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Are you sure you want to cancel
                                                                        <strong>{{ $pr->pr_number }}</strong>?
                                                                    </p>
                                                                    <div class="form-group mb-0">
                                                                        <label
                                                                            for="cancellation_reason_{{ $pr->id }}">Cancellation
                                                                            Reason <span
                                                                                class="text-danger">*</span></label>
                                                                        <textarea name="cancellation_reason" id="cancellation_reason_{{ $pr->id }}" class="form-control" rows="3"
                                                                            placeholder="e.g., Item no longer needed, budget cut, duplicate request" required></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary"
                                                                        data-dismiss="modal">Close</button>
                                                                    <button type="submit" class="btn btn-danger">
                                                                        <i class="fas fa-ban"></i> Confirm Cancel
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @elseif(in_array($pr->status, ['completed', 'printed']))
                                            @if (auth()->user()->hasAnyRole(['purchasing']))
                                                <a href="{{ route('purchase_orders.create', ['pr_id' => $pr->id]) }}"
                                                    class="btn btn-success btn-sm" title="Create PO">
                                                    <i class="fas fa-file-invoice"></i>
                                                </a>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No PPB/PPJ found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
