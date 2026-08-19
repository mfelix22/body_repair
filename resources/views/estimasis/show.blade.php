@extends('layouts.admin')

@section('title', 'Estimasi: ' . $estimasi->estimasi_number)
@section('page_title', 'Estimasi: ' . $estimasi->estimasi_number)

@section('content')
    @php
        $badge = $estimasi->getStatusBadge();
    @endphp

    <div class="row">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $estimasi->estimasi_number }}</h3>
                    <div class="card-tools">
                        <span class="badge badge-{{ $badge['color'] }}">{{ $badge['label'] }}</span>
                        @if (\App\Helpers\PermissionHelper::canPrint('estimasis'))
                            <a href="{{ \URL::temporarySignedRoute('estimasis.print', now()->addMinutes(5), $estimasi) }}"
                                target="_blank" class="btn btn-default btn-sm ml-2">
                                <i class="fas fa-print"></i> Print
                            </a>
                        @endif
                        <a href="{{ route('work_orders.show', $estimasi->workOrder) }}" class="btn btn-secondary btn-sm ml-2">
                            <i class="fas fa-arrow-left"></i> Back to Work Order
                        </a>
                    </div>
                </div>

                <div class="card-body">

                    {{-- Basic Info --}}
                    <table class="table table-bordered table-sm mb-4">
                        <tr>
                            <th style="width:200px">Estimasi No.</th>
                            <td>{{ $estimasi->estimasi_number }}</td>
                            <th style="width:160px">Created At</th>
                            <td>{{ $estimasi->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Work Order</th>
                            <td>
                                <a href="{{ route('work_orders.show', $estimasi->workOrder) }}">
                                    {{ $estimasi->workOrder->wo_number }}
                                </a>
                            </td>
                            <th>Created By</th>
                            <td>{{ optional($estimasi->creator)->name }}</td>
                        </tr>
                        <tr>
                            <th>Customer</th>
                            <td colspan="3">{{ optional($estimasi->workOrder->customer)->name }}</td>
                        </tr>
                        @if ($estimasi->notes)
                            <tr>
                                <th>Notes</th>
                                <td colspan="3">{{ $estimasi->notes }}</td>
                            </tr>
                        @endif
                    </table>

                    {{-- Approval tier notice --}}
                    @if ($estimasi->status === 'pending_approval')
                        @if ($estimasi->approvals_required === 1)
                            <p class="text-muted small mb-2">
                                <span class="badge badge-warning">&le; 20%</span>
                                Sigit approval only.
                            </p>
                        @elseif ($estimasi->approvals_required === 2)
                            <p class="text-muted small mb-2">
                                <span class="badge badge-danger">&gt; 20%</span>
                                Sigit and Director must approve in sequence.
                            </p>
                        @endif

                        {{-- Approvers table --}}
                        <table class="table table-sm table-bordered mb-3" style="max-width:600px;">
                            <thead class="bg-light">
                                <tr>
                                    <th>Approver</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $slots = [
                                        [1, $estimasi->approver1, $estimasi->approver1_approved_at, $estimasi->approver1_rejected_at],
                                        [2, $estimasi->approver2, $estimasi->approver2_approved_at, $estimasi->approver2_rejected_at],
                                    ];
                                @endphp
                                @foreach ($slots as [$slot, $approver, $approvedAt, $rejectedAt])
                                    @if ($approver)
                                        <tr>
                                            <td>{{ $approver->name }}</td>
                                            <td class="text-muted small">
                                                {{ ucwords(str_replace(['_', '|'], [' ', ', '], $approver->role)) }}
                                            </td>
                                            <td>
                                                @if ($approvedAt)
                                                    <span class="badge badge-success">Approved</span>
                                                @elseif ($rejectedAt)
                                                    <span class="badge badge-danger">Rejected</span>
                                                @elseif ($estimasi->approvals_required === 2 && $slot === 2 && is_null($estimasi->approver1_approved_at))
                                                    <span class="badge badge-secondary">Waiting for Stage 1</span>
                                                @else
                                                    <span class="badge badge-warning">Pending</span>
                                                @endif
                                            </td>
                                            <td class="text-muted small">
                                                {{ $approvedAt?->format('d M Y H:i') ?? ($rejectedAt?->format('d M Y H:i') ?? '—') }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>

                        @if ($pendingMyApproval)
                            <form action="{{ route('estimasis.approve', $estimasi) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('Approve this Estimasi?')">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-check"></i> Approve Estimasi
                                </button>
                            </form>
                            <form action="{{ route('estimasis.reject', $estimasi) }}" method="POST" class="d-inline ml-2"
                                onsubmit="return confirm('Reject this Estimasi?')">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-times"></i> Reject Estimasi
                                </button>
                            </form>
                        @endif
                    @endif

                    @if ($estimasi->items->isNotEmpty())
                        <h6 class="font-weight-bold mt-4">Sparepart Dibutuhkan (untuk Asuransi)</h6>
                        <table class="table table-bordered table-sm" style="max-width:750px;">
                            <thead class="bg-light">
                                <tr>
                                    <th>Sparepart</th>
                                    <th class="text-right" style="width:130px;">Harga Satuan</th>
                                    <th class="text-right" style="width:80px;">Qty</th>
                                    <th class="text-right" style="width:130px;">Jumlah</th>
                                    <th class="text-center" style="width:110px;">Sumber</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($estimasi->items as $spItem)
                                    <tr>
                                        <td>{{ $spItem->description }}</td>
                                        <td class="text-right">Rp {{ number_format($spItem->unit_price, 0, ',', '.') }}</td>
                                        <td class="text-right">{{ number_format($spItem->quantity, 0) }}</td>
                                        <td class="text-right">Rp {{ number_format($spItem->total_price, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            @if ($spItem->is_supply)
                                                <span class="badge badge-info">Supply Asuransi</span>
                                            @else
                                                <span class="badge badge-secondary">Stock Sendiri</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif

                    {{-- Pricing Summary --}}
                    <h6 class="font-weight-bold mt-4">Pricing Summary</h6>
                    @if (auth()->user()->hasAnyRole(['service_advisor', 'admin', 'super_admin']))
                        <h6 class="font-weight-bold mt-4">Insurance Approval Document</h6>

                        @if ($estimasi->approval_document_path)
                            <p>
                                <a href="{{ asset('storage/' . $estimasi->approval_document_path) }}" target="_blank" class="btn btn-info btn-sm">
                                    <i class="fas fa-file-download"></i> Download: {{ $estimasi->approval_document_name }}
                                </a>
                            </p>
                        @else
                            <p class="text-muted small">No insurance approval document uploaded yet.</p>
                        @endif

                        <form action="{{ route('estimasis.upload_approval', $estimasi) }}" method="POST" enctype="multipart/form-data" class="form-inline">
                            @csrf
                            <div class="form-group">
                                <input type="file" name="approval_document" class="form-control-file form-control-sm" accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm ml-2">
                                <i class="fas fa-upload"></i> {{ $estimasi->approval_document_path ? 'Replace' : 'Upload' }}
                            </button>
                        </form>
                    @endif

                    <h6 class="font-weight-bold mt-4">Pricing Summary</h6>
                    <table class="table table-bordered table-sm" style="max-width:450px;">
                        <tr>
                            <th>Panel Subtotal</th>
                            <td class="text-right">Rp {{ number_format($estimasi->panel_subtotal, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Panel Discount</th>
                            <td class="text-right text-danger">
                                @if ($estimasi->panel_discount_amount > 0 && $estimasi->status !== 'rejected')
                                    — Rp {{ number_format($estimasi->panel_discount_amount, 0, ',', '.') }}
                                    <small class="text-muted">({{ number_format($estimasi->panel_discount_percentage, 2) }}%)</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Sparepart Subtotal</th>
                            <td class="text-right">Rp {{ number_format($estimasi->sparepart_subtotal, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Sparepart Discount</th>
                            <td class="text-right text-danger">
                                @if ($estimasi->sparepart_discount_amount > 0 && $estimasi->status !== 'rejected')
                                    — Rp {{ number_format($estimasi->sparepart_discount_amount, 0, ',', '.') }}
                                    <small class="text-muted">({{ number_format($estimasi->sparepart_discount_percentage, 2) }}%)</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>WO Subtotal</th>
                            <td class="text-right">Rp {{ number_format($estimasi->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Total Discount</th>
                            <td class="text-right text-danger">
                                @if ($estimasi->discount_amount > 0 && $estimasi->status !== 'rejected')
                                    — Rp {{ number_format($estimasi->discount_amount, 0, ',', '.') }}
                                    <small class="text-muted">({{ number_format($estimasi->discount_percentage, 2) }}% blended)</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Total</th>
                            <td class="text-right"><strong>Rp {{ number_format($estimasi->total, 0, ',', '.') }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
