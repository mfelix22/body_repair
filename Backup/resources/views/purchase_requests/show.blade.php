@extends('layouts.admin')

@section('title', $purchaseRequest->pr_number)
@section('page_title', ($purchaseRequest->type === 'Jasa' ? 'PPJ: ' : 'PPB: ') . $purchaseRequest->pr_number)

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $purchaseRequest->pr_number }}</h3>
                    <div class="card-tools">
                        @if (in_array($purchaseRequest->status, ['completed', 'printed']))
                            <a href="{{ \URL::temporarySignedRoute('purchase_requests.print', now()->addMinutes(5), $purchaseRequest) }}"
                                class="btn btn-secondary btn-sm" target="_blank">
                                <i class="fas fa-print"></i> Print
                            </a>
                        @endif
                        @if ($purchaseRequest->status === 'on_progress')
                            @if (auth()->user()->hasAnyRole(['staff', 'admin', 'super_admin']))
                                <a href="{{ route('purchase_requests.edit', $purchaseRequest) }}"
                                    class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            @endif
                        @endif
                        @php
                            $canCancelPr = false;
                            $cancelablePrStatuses = ['on_progress', 'dept_head_approved', 'gm_approved'];
                            if (in_array($purchaseRequest->status, $cancelablePrStatuses)) {
                                $user = auth()->user();

                                if ($user->hasAnyRole(['admin', 'super_admin'])) {
                                    $canCancelPr = true;
                                } elseif ($purchaseRequest->status === 'on_progress') {
                                    $canCancelPr =
                                        $purchaseRequest->requested_by === auth()->id() ||
                                        $user->hasAnyRole(['manager', 'director']);
                                } elseif ($purchaseRequest->status === 'dept_head_approved') {
                                    $canCancelPr = $user->hasAnyRole(['manager', 'director']);
                                } elseif ($purchaseRequest->status === 'gm_approved') {
                                    $canCancelPr = $user->hasAnyRole(['director']);
                                }
                            }
                        @endphp
                        @if ($canCancelPr)
                            <button type="button" class="btn btn-danger btn-sm" data-toggle="modal"
                                data-target="#cancelPrModal">
                                <i class="fas fa-ban"></i> Cancel
                            </button>
                        @endif
                        @if ($purchaseRequest->status === 'on_progress')
                            @if (auth()->user()->hasAnyRole(['manager']) && $purchaseRequest->requested_by !== auth()->id())
                                <form action="{{ route('purchase_requests.approve', $purchaseRequest) }}" method="POST"
                                    class="d-inline" onsubmit="return confirm('Are you sure you want to approve this PR?')">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>
                            @endif
                        @elseif($purchaseRequest->status === 'dept_head_approved')
                            @if (
                                $purchaseRequest->require_acknowledgement &&
                                    auth()->user()->hasAnyRole(['director']))
                                <form action="{{ route('purchase_requests.gm_approve', $purchaseRequest) }}" method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Are you sure you want to approve this PR as GM/Direksi?')">
                                    @csrf
                                    <button type="submit" class="btn btn-info btn-sm">
                                        <i class="fas fa-user-tie"></i> GM/Direksi Approve
                                    </button>
                                </form>
                            @endif
                            @if (
                                !$purchaseRequest->require_acknowledgement &&
                                    !$purchaseRequest->purchasing_received_by &&
                                    auth()->user()->hasAnyRole(['purchasing']))
                                <form action="{{ route('purchase_requests.received', $purchaseRequest) }}" method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Are you sure you want to mark as received?')">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-clipboard-check"></i> Mark as Received
                                    </button>
                                </form>
                            @endif
                        @elseif($purchaseRequest->status === 'gm_approved')
                            @if (
                                !$purchaseRequest->purchasing_received_by &&
                                    auth()->user()->hasAnyRole(['purchasing']))
                                <form action="{{ route('purchase_requests.received', $purchaseRequest) }}" method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Are you sure you want to mark as received?')">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-clipboard-check"></i> Mark as Received
                                    </button>
                                </form>
                            @endif
                        @elseif($purchaseRequest->status === 'completed')
                            @if (
                                !$purchaseRequest->purchaseOrders->isNotEmpty() &&
                                    auth()->user()->hasAnyRole(['purchasing']))
                                <a href="{{ route('purchase_orders.create', ['pr_id' => $purchaseRequest->id]) }}"
                                    class="btn btn-success btn-sm">
                                    <i class="fas fa-file-invoice"></i> Create PO
                                </a>
                            @endif
                        @endif
                        <span
                            class="badge badge-{{ in_array($purchaseRequest->status, ['dept_head_approved', 'gm_approved', 'completed', 'printed']) ? 'success' : (in_array($purchaseRequest->status, ['rejected', 'cancelled']) ? 'danger' : 'secondary') }}">
                            {{ ucwords(str_replace('_', ' ', $purchaseRequest->status)) }}
                        </span>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Request Details</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th>{{ $purchaseRequest->type === 'Jasa' ? 'PPJ Number:' : 'PPB Number:' }}</th>
                                    <td>{{ $purchaseRequest->pr_number }}</td>
                                </tr>
                                <tr>
                                    <th>Request Date:</th>
                                    <td>{{ $purchaseRequest->request_date->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Requested By:</th>
                                    <td>{{ $purchaseRequest->requestor->name }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td><span
                                            class="badge badge-{{ in_array($purchaseRequest->status, ['dept_head_approved', 'gm_approved', 'completed', 'printed']) ? 'success' : ($purchaseRequest->status === 'rejected' ? 'danger' : 'secondary') }}">
                                            {{ ucwords(str_replace('_', ' ', $purchaseRequest->status)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Type:</th>
                                    <td>
                                        <span
                                            class="badge badge-{{ $purchaseRequest->type === 'Jasa' ? 'info' : 'primary' }}">
                                            {{ $purchaseRequest->type === 'Jasa' ? 'Jasa (Service) - PPJ' : 'Barang (Items) - PPB' }}
                                        </span>
                                    </td>
                                </tr>
                                @if ($purchaseRequest->status === 'cancelled' && $purchaseRequest->cancellation_reason)
                                    <tr>
                                        <th>Cancellation Reason:</th>
                                        <td><span class="text-danger">{{ $purchaseRequest->cancellation_reason }}</span>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6>Signature Approvals</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Step:</th>
                                    <th>User:</th>
                                    <th>Signature:</th>
                                </tr>
                                <!-- Created By (Requested By) -->
                                <tr>
                                    <td><strong>Created By:</strong></td>
                                    <td>{{ $purchaseRequest->requestor->name }}</td>
                                    <td>
                                        @if ($purchaseRequest->requestor->signature_path)
                                            <img src="{{ route('users.signature', $purchaseRequest->requestor) }}"
                                                alt="Signature" style="max-width: 80px; max-height: 40px;">
                                        @else
                                            <span class="text-muted text-sm">-</span>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Approved By -->
                                <tr>
                                    <td><strong>Approved By:</strong></td>
                                    <td>
                                        @if ($purchaseRequest->deptHeadApprover)
                                            {{ $purchaseRequest->deptHeadApprover->name }}
                                            <br><small
                                                class="text-muted">{{ $purchaseRequest->dept_head_at->format('M d, Y H:i') }}</small>
                                        @else
                                            <span class="text-muted">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($purchaseRequest->deptHeadApprover && $purchaseRequest->deptHeadApprover->signature_path)
                                            <img src="{{ route('users.signature', $purchaseRequest->deptHeadApprover) }}"
                                                alt="Signature" style="max-width: 80px; max-height: 40px;">
                                        @else
                                            <span class="text-muted text-sm">-</span>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Acknowledged By -->
                                <tr>
                                    <td><strong>Acknowledged By:</strong>
                                        @if (!$purchaseRequest->require_acknowledgement)
                                            <span class="badge badge-warning">Optional</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($purchaseRequest->gmApprover)
                                            {{ $purchaseRequest->gmApprover->name }}
                                            <br><small
                                                class="text-muted">{{ $purchaseRequest->gm_at->format('M d, Y H:i') }}</small>
                                        @else
                                            <span class="text-muted">
                                                @if ($purchaseRequest->require_acknowledgement)
                                                    Pending
                                                @else
                                                    Skipped
                                                @endif
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($purchaseRequest->gmApprover && $purchaseRequest->gmApprover->signature_path)
                                            <img src="{{ route('users.signature', $purchaseRequest->gmApprover) }}"
                                                alt="Signature" style="max-width: 80px; max-height: 40px;">
                                        @else
                                            <span class="text-muted text-sm">-</span>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Purchasing Received -->
                                <tr>
                                    <td><strong>Purchasing Received:</strong></td>
                                    <td>
                                        @if ($purchaseRequest->purchasingReceiver)
                                            {{ $purchaseRequest->purchasingReceiver->name }}
                                            <br><small
                                                class="text-muted">{{ $purchaseRequest->purchasing_received_at->format('M d, Y H:i') }}</small>
                                        @else
                                            <span class="text-muted">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($purchaseRequest->purchasingReceiver && $purchaseRequest->purchasingReceiver->signature_path)
                                            <img src="{{ route('users.signature', $purchaseRequest->purchasingReceiver) }}"
                                                alt="Signature" style="max-width: 80px; max-height: 40px;">
                                        @else
                                            <span class="text-muted text-sm">-</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>
                    <h6>Items</h6>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                @if ($purchaseRequest->type === 'Jasa')
                                    <th>Service Description</th>
                                    <th>Quantity</th>
                                    <th>Notes</th>
                                @else
                                    <th>Item</th>
                                    <th>Item Code</th>
                                    <th>Current Stock</th>
                                    <th>Request Qty</th>
                                    <th>UOM</th>
                                    <th>Notes</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchaseRequest->details as $detail)
                                <tr>
                                    @if ($purchaseRequest->type === 'Jasa')
                                        <td><strong>{{ $detail->service_description ?? '-' }}</strong></td>
                                        <td>{{ number_format($detail->quantity, 2) }}</td>
                                        <td>{{ $detail->notes ?? '-' }}</td>
                                    @else
                                        <td>
                                            @if ($detail->is_custom_item)
                                                {{ $detail->custom_item_name ?? '-' }}
                                                <span class="badge badge-warning ml-1">New Item</span>
                                            @else
                                                {{ $detail->item->name ?? '-' }}
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                @if ($detail->is_custom_item)
                                                    <em>Pending master item creation</em>
                                                @else
                                                    {{ $detail->item->code ?? '-' }}
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            @if ($detail->is_custom_item)
                                                <span class="badge badge-secondary">-</span>
                                            @else
                                                <span
                                                    class="badge badge-{{ $detail->item->stocks->sum('quantity') <= $detail->item->reorder_level ? 'danger' : 'success' }}">
                                                    {{ number_format($detail->item->stocks->sum('quantity'), 2) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td><strong>{{ number_format($detail->quantity, 2) }}</strong></td>
                                        <td>{{ $detail->uom->code ?? '-' }}</td>
                                        <td>{{ $detail->notes ?? '-' }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($purchaseRequest->notes)
                        <hr>
                        <h6>Notes</h6>
                        <p>{{ $purchaseRequest->notes }}</p>
                    @endif

                    @if ($purchaseRequest->purchaseOrders->isNotEmpty())
                        <hr>
                        <h6>Related Purchase Orders</h6>
                        <ul>
                            @foreach ($purchaseRequest->purchaseOrders as $po)
                                <li><a href="{{ route('purchase_orders.show', $po) }}">{{ $po->po_number }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
    {{-- Cancel Modal --}}
    <div class="modal fade" id="cancelPrModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('purchase_requests.cancel', $purchaseRequest) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title"><i class="fas fa-ban"></i> Cancel PPB/PPJ</h5>
                        <button type="button" class="close text-white"
                            data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to cancel <strong>{{ $purchaseRequest->pr_number }}</strong>?</p>
                        <div class="form-group">
                            <label for="cancellation_reason">Cancellation Reason <span
                                    class="text-danger">*</span></label>
                            <textarea name="cancellation_reason" id="cancellation_reason" class="form-control" rows="3"
                                placeholder="e.g., Item no longer needed, budget cut, etc." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> Confirm Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
