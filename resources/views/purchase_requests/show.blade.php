@extends('layouts.admin')

@section('title', $purchaseRequest->pr_number)
@section('page_title', ($purchaseRequest->type === 'Jasa' ? 'PPJ: ' : 'PPB: ') . $purchaseRequest->pr_number)

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        {{ $purchaseRequest->pr_number }}
                        @if ($purchaseRequest->status !== 'cancelled' && !$purchaseRequest->isFullyOrdered() && $purchaseRequest->type === 'Barang')
                            <span class="badge badge-warning ml-2" title="Some items are not fully ordered yet">
                                <i class="fas fa-exclamation-circle"></i> Pending Order
                            </span>
                        @endif
                    </h3>
                    <div class="card-tools">
                        @if (in_array($purchaseRequest->status, ['completed', 'printed', 'closed']))
                            @if (\App\Helpers\PermissionHelper::canPrint('purchase_requests') || auth()->user()->hasAnyRole(['purchasing']))
                                <a href="{{ \URL::temporarySignedRoute('purchase_requests.print', now()->addMinutes(5), $purchaseRequest) }}"
                                    class="btn btn-secondary btn-sm" target="_blank">
                                    <i class="fas fa-print"></i> Print
                                </a>
                            @endif
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
                        @if (in_array($purchaseRequest->status, ['completed', 'printed']) &&
                                (auth()->id() === $purchaseRequest->requested_by ||
                                    auth()->user()->hasAnyRole(['admin', 'super_admin'])))
                            <button type="button" class="btn btn-dark btn-sm" data-toggle="modal"
                                data-target="#closePrModal">
                                <i class="fas fa-archive"></i> Close PPB
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
                        @elseif(in_array($purchaseRequest->status, ['completed', 'printed']))
                            @if (auth()->user()->hasAnyRole(['purchasing']))
                                <a href="{{ route('purchase_orders.create', ['pr_id' => $purchaseRequest->id]) }}"
                                    class="btn btn-success btn-sm">
                                    <i class="fas fa-file-invoice"></i> Create PO
                                </a>
                            @endif
                        @endif
                        <span
                            class="badge badge-{{ in_array($purchaseRequest->status, ['dept_head_approved', 'gm_approved', 'completed', 'printed']) ? 'success' : (in_array($purchaseRequest->status, ['rejected', 'cancelled']) ? 'danger' : ($purchaseRequest->status === 'closed' ? 'dark' : 'secondary')) }}">
                            {{ $purchaseRequest->status === 'closed' ? 'Closed' : ucwords(str_replace('_', ' ', $purchaseRequest->status)) }}
                        </span>
                    </div>
                </div>

                <div class="card-body">

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
                                            class="badge badge-{{ in_array($purchaseRequest->status, ['dept_head_approved', 'gm_approved', 'completed', 'printed']) ? 'success' : ($purchaseRequest->status === 'rejected' ? 'danger' : ($purchaseRequest->status === 'closed' ? 'dark' : 'secondary')) }}">
                                            {{ $purchaseRequest->status === 'closed' ? 'Closed' : ucwords(str_replace('_', ' ', $purchaseRequest->status)) }}
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
                                    <th>Ordered Qty</th>
                                    <th>Remaining Qty</th>
                                    <th>UOM</th>
                                    <th>Notes</th>
                                    <th>Status</th>
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
                                        <td>{{ number_format($detail->ordered_quantity, 2) }}</td>
                                        <td>
                                            @if ($detail->getRemainingQuantity() > 0)
                                                <span class="text-danger font-weight-bold">{{ number_format($detail->getRemainingQuantity(), 2) }}</span>
                                            @else
                                                <span class="text-success">0</span>
                                            @endif
                                        </td>
                                        <td>{{ $detail->uom->code ?? '-' }}</td>
                                        <td>{{ $detail->notes ?? '-' }}</td>
                                        <td>
                                            @if ($detail->isFullyOrdered())
                                                <span class="badge badge-success" title="Fully ordered">
                                                    <i class="fas fa-check-circle"></i> Complete
                                                </span>
                                            @elseif ($detail->isPartiallyOrdered())
                                                <span class="badge badge-warning" title="Partially ordered">
                                                    <i class="fas fa-exclamation-circle"></i> Partial
                                                </span>
                                            @else
                                                <span class="badge badge-secondary" title="Not ordered yet">
                                                    <i class="fas fa-minus-circle"></i> Not Ordered
                                                </span>
                                            @endif
                                        </td>
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

                    @if ($purchaseRequest->type === 'Jasa' && $purchaseRequest->attachments && $purchaseRequest->attachments->count() > 0)
                        <hr>
                        <h6>Attachments</h6>
                        <div class="row">
                            @foreach ($purchaseRequest->attachments as $attachment)
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">{{ $attachment->file_name }}</h6>
                                            <small class="text-muted">{{ number_format($attachment->file_size / 1024, 2) }} KB</small>
                                            @php
                                                $ext = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
                                            @endphp
                                            @if (in_array($ext, ['jpg', 'jpeg', 'png']))
                                                <div class="mt-2">
                                                    <img src="{{ asset('storage/' . $attachment->file_path) }}"
                                                        alt="PPJ Attachment"
                                                        style="max-width: 100%; max-height: 300px; border: 1px solid #dee2e6; border-radius: 4px;">
                                                </div>
                                            @endif
                                            <div class="mt-2">
                                                <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-paperclip"></i> {{ $ext === 'pdf' ? 'View PDF' : 'View/Download' }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($purchaseRequest->purchaseOrders->isNotEmpty())
                        <hr>
                        <h6>Related Purchase Orders</h6>
                        <ul>
                            @foreach ($purchaseRequest->purchaseOrders as $po)
                                <li class="mb-1">
                                    <a href="{{ route('purchase_orders.show', $po) }}">{{ $po->po_number }}</a>
                                    @if ($po->po_type === 'service_order' && $po->status === 'approved')
                                        @if ($purchaseRequest->requested_by === auth()->id() || auth()->user()->hasAnyRole(['admin', 'super_admin']))
                                            <button type="button" class="btn btn-xs btn-info ml-2" data-toggle="modal"
                                                data-target="#uploadBeritaAcaraModal_{{ $po->id }}">
                                                <i class="fas fa-file-upload"></i>
                                                {{ $po->berita_acara_path ? 'Re-upload' : 'Upload Berita Acara' }}
                                            </button>
                                        @endif
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
    {{-- Close PPB Modal --}}
    <div class="modal fade" id="closePrModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('purchase_requests.close', $purchaseRequest) }}" method="POST">
                    @csrf
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title"><i class="fas fa-archive"></i> Close PPB/PPJ</h5>
                        <button type="button" class="close text-white"
                            data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p>Close <strong>{{ $purchaseRequest->pr_number }}</strong>? This marks the PPB as fully processed
                            — no further POs will be created from it.</p>
                        <div class="form-group">
                            <label for="close_reason">Reason / Notes <small class="text-muted">(optional)</small></label>
                            <textarea name="close_reason" id="close_reason" class="form-control" rows="3"
                                placeholder="e.g., Remaining items no longer needed, fulfilled via another process, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark"><i class="fas fa-archive"></i> Confirm Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($purchaseRequest->purchaseOrders as $po)
        @if ($po->po_type === 'service_order' && $po->status === 'approved' &&
                ($purchaseRequest->requested_by === auth()->id() || auth()->user()->hasAnyRole(['admin', 'super_admin'])))
            <div class="modal fade" id="uploadBeritaAcaraModal_{{ $po->id }}" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form action="{{ route('purchase_orders.upload_berita_acara', $po) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="modal-header bg-info text-white">
                                <h5 class="modal-title"><i class="fas fa-file-upload"></i> Upload Berita Acara</h5>
                                <button type="button" class="close text-white"
                                    data-dismiss="modal"><span>&times;</span></button>
                            </div>
                            <div class="modal-body">
                                @if ($po->berita_acara_path)
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        A Berita Acara has already been uploaded for SO
                                        <strong>{{ $po->po_number }}</strong> on
                                        <strong>{{ $po->berita_acara_uploaded_at?->format('d M Y H:i') }}</strong>
                                        by <strong>{{ optional($po->beritaAcaraUploader)->name }}</strong>.
                                        Uploading a new file will replace it.
                                        <br>
                                        <a href="{{ asset('storage/' . $po->berita_acara_path) }}" target="_blank"
                                            class="btn btn-sm btn-outline-info mt-1">
                                            <i class="fas fa-eye"></i> View Current File
                                        </a>
                                    </div>
                                @else
                                    <div class="alert alert-secondary">
                                        <i class="fas fa-info-circle"></i>
                                        Upload the Berita Acara document for SO <strong>{{ $po->po_number }}</strong>.
                                        Once uploaded, this Service Order can be closed by purchasing.
                                    </div>
                                @endif
                                <div class="form-group">
                                    <label for="berita_acara_{{ $po->id }}">Berita Acara File <span class="text-danger">*</span></label>
                                    <input type="file" name="berita_acara" id="berita_acara_{{ $po->id }}" class="form-control-file"
                                        accept=".pdf,.jpg,.jpeg,.png" required>
                                    <small class="form-text text-muted">Accepted: PDF, JPG, PNG. Max size: 5 MB.</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-info">
                                    <i class="fas fa-upload"></i> Upload
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

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
