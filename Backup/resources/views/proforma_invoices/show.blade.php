@extends('layouts.admin')

@section('title', 'Proforma: ' . $proformaInvoice->proforma_number)
@section('page_title', 'Proforma Invoice: ' . $proformaInvoice->proforma_number)

@section('content')
    @php
        $authUser = auth()->user();
        $statusColor = match ($proformaInvoice->status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'no_discount' => 'secondary',
            'pending_approval' => 'warning',
            default => 'light',
        };
        $statusLabel = match ($proformaInvoice->status) {
            'no_discount' => 'No Discount',
            default => ucwords(str_replace('_', ' ', $proformaInvoice->status)),
        };
    @endphp

    <div class="row">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $proformaInvoice->proforma_number }}</h3>
                    <div class="card-tools">
                        <span class="badge badge-{{ $statusColor }}">{{ $statusLabel }}</span>
                        <a href="{{ \URL::temporarySignedRoute('proforma_invoices.print', now()->addMinutes(5), $proformaInvoice) }}"
                            target="_blank" class="btn btn-default btn-sm ml-2">
                            <i class="fas fa-print"></i> Print
                        </a>
                        <a href="{{ route('proforma_invoices.index') }}" class="btn btn-secondary btn-sm ml-2">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @foreach (['success', 'error', 'info'] as $type)
                        @if (session($type))
                            <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible">
                                {{ session($type) }}
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                            </div>
                        @endif
                    @endforeach

                    {{-- Basic Info --}}
                    <table class="table table-bordered table-sm mb-4">
                        <tr>
                            <th style="width:200px">Proforma No.</th>
                            <td>{{ $proformaInvoice->proforma_number }}</td>
                            <th style="width:160px">Created At</th>
                            <td>{{ $proformaInvoice->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Work Order</th>
                            <td>
                                <a href="{{ route('work_orders.show', $proformaInvoice->workOrder) }}">
                                    {{ $proformaInvoice->workOrder->wo_number }}
                                </a>
                            </td>
                            <th>Created By</th>
                            <td>{{ optional($proformaInvoice->creator)->name }}</td>
                        </tr>
                        <tr>
                            <th>Customer</th>
                            <td colspan="3">{{ optional($proformaInvoice->workOrder->customer)->name }}</td>
                        </tr>
                        @if ($proformaInvoice->notes)
                            <tr>
                                <th>Notes</th>
                                <td colspan="3">{{ $proformaInvoice->notes }}</td>
                            </tr>
                        @endif
                    </table>

                    {{-- Discount Lines --}}
                    @if ($proformaInvoice->discountLines->isNotEmpty())
                        <h6 class="font-weight-bold mb-3">Discount Lines</h6>

                        @foreach ($proformaInvoice->discountLines as $line)
                            @php
                                $linePending = $line->isPendingMyApproval($authUser->id);
                                $lb = $line->getStatusBadge();
                                $typeLabels = [
                                    'package' => 'Package',
                                    'extra_item' => 'Extra Item',
                                    'extra_labor' => 'Extra Labor',
                                ];
                                $typeBadges = [
                                    'package' => 'badge-primary',
                                    'extra_item' => 'badge-info',
                                    'extra_labor' => 'badge-secondary',
                                ];
                                $typeLabel = $typeLabels[$line->target_type] ?? $line->target_type;
                                $typeBadge = $typeBadges[$line->target_type] ?? 'badge-dark';
                            @endphp
                            <div class="card card-outline card-{{ $lb['color'] }} mb-3">
                                <div class="card-header py-2 d-flex align-items-center">
                                    <span class="badge {{ $typeBadge }} mr-2">{{ $typeLabel }}</span>
                                    <strong>{{ $line->description }}</strong>
                                    <span class="ml-auto">
                                        <span class="badge badge-{{ $lb['color'] }}">{{ $lb['label'] }}</span>
                                    </span>
                                </div>
                                <div class="card-body py-2">
                                    {{-- Pricing row --}}
                                    <div class="row text-center mb-2">
                                        <div class="col-sm-3">
                                            <div class="text-muted small">Original Price</div>
                                            <strong>Rp {{ number_format($line->original_price, 0, ',', '.') }}</strong>
                                        </div>
                                        <div class="col-sm-2">
                                            <div class="text-muted small">Discount</div>
                                            <strong
                                                class="text-danger">{{ number_format($line->discount_percentage, 2) }}%</strong>
                                        </div>
                                        <div class="col-sm-3">
                                            <div class="text-muted small">Discount Amount</div>
                                            <strong class="text-danger">
                                                @if ($line->status === 'rejected')
                                                    <span class="text-muted"><del>Rp
                                                            {{ number_format(($line->original_price * $line->discount_percentage) / 100, 0, ',', '.') }}</del></span>
                                                @else
                                                    Rp {{ number_format($line->discount_amount, 0, ',', '.') }}
                                                @endif
                                            </strong>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="text-muted small">Final Price</div>
                                            <strong class="text-success h6">
                                                Rp {{ number_format($line->final_price, 0, ',', '.') }}
                                            </strong>
                                        </div>
                                    </div>

                                    {{-- Approval tier notice --}}
                                    @if ($line->approvals_required === 1)
                                        <p class="text-muted small mb-1">
                                            <span class="badge badge-warning">
                                                < 20%</span>
                                                    Any one of the 3 approvers approving is sufficient.
                                        </p>
                                    @elseif ($line->approvals_required === 2)
                                        <p class="text-muted small mb-1">
                                            <span class="badge badge-danger">≥ 20%</span>
                                            Both Mgr/Acc and Director must approve in sequence.
                                        </p>
                                    @endif

                                    {{-- Approvers table --}}
                                    <table class="table table-sm table-bordered mb-2">
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
                                                    [
                                                        1,
                                                        $line->approver1,
                                                        $line->approver1_approved_at,
                                                        $line->approver1_rejected_at,
                                                    ],
                                                    [
                                                        2,
                                                        $line->approver2,
                                                        $line->approver2_approved_at,
                                                        $line->approver2_rejected_at,
                                                    ],
                                                ];
                                                if ($line->approvals_required === 1 && $line->approver3_id) {
                                                    $slots[] = [
                                                        3,
                                                        $line->approver3,
                                                        $line->approver3_approved_at,
                                                        $line->approver3_rejected_at,
                                                    ];
                                                }
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
                                                            @elseif ($line->approvals_required === 2 && $slot === 2 && is_null($line->approver1_approved_at))
                                                                <span class="badge badge-secondary">Waiting for Stage
                                                                    1</span>
                                                            @elseif ($line->status === 'approved' || $line->status === 'rejected')
                                                                <span class="badge badge-secondary">Not Required</span>
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

                                    {{-- Per-line Approve / Reject buttons --}}
                                    @if ($linePending)
                                        <form
                                            action="{{ route('proforma_invoices.approve_line', [$proformaInvoice, $line]) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Approve the discount for: {{ addslashes($line->description) }}?')">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i> Approve this Discount
                                            </button>
                                        </form>
                                        <form
                                            action="{{ route('proforma_invoices.reject_line', [$proformaInvoice, $line]) }}"
                                            method="POST" class="d-inline ml-2"
                                            onsubmit="return confirm('Reject the discount for: {{ addslashes($line->description) }}? Full price will apply.')">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i> Reject Discount
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        @if ($proformaInvoice->voucher_code)
                            <div class="alert alert-success">
                                <i class="fas fa-ticket-alt"></i>
                                <strong>Voucher Applied:</strong>
                                {{ $proformaInvoice->voucher_code }}
                                &mdash; Rp {{ number_format($proformaInvoice->voucher_amount, 0, ',', '.') }} off global
                                total.
                                <br><small class="text-muted">
                                    No per-line approval required — voucher discount is pre-authorized.
                                </small>
                            </div>
                        @else
                            <div class="alert alert-secondary">
                                <i class="fas fa-tag"></i> No discount lines — Finance can invoice at full WO total.
                            </div>
                        @endif
                    @endif

                    {{-- Aggregate Pricing Summary --}}
                    <h6 class="font-weight-bold mt-4">Pricing Summary</h6>
                    <table class="table table-bordered table-sm" style="max-width:450px;">
                        <tr>
                            <th>WO Subtotal</th>
                            <td class="text-right">Rp {{ number_format($proformaInvoice->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <th>Total Discount <small class="text-muted">(approved lines only)</small></th>
                            <td class="text-right text-danger">
                                @if ($proformaInvoice->discount_amount > 0)
                                    — Rp {{ number_format($proformaInvoice->discount_amount, 0, ',', '.') }}
                                    <small
                                        class="text-muted">({{ number_format($proformaInvoice->discount_percentage, 2) }}%)</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        @if ($proformaInvoice->voucher_amount > 0)
                            <tr>
                                <th>Voucher
                                    @if ($proformaInvoice->voucher_code)
                                        <small class="text-muted">({{ $proformaInvoice->voucher_code }})</small>
                                    @endif
                                </th>
                                <td class="text-right text-danger">
                                    — Rp {{ number_format($proformaInvoice->voucher_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endif
                        <tr class="table-success">
                            <th>Final Total</th>
                            <td class="text-right font-weight-bold">
                                Rp {{ number_format($proformaInvoice->total, 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>{{-- /card-body --}}

                <div class="card-footer">
                    {{-- Edit button (only if no lines yet) --}}
                    @if (
                        !$proformaInvoice->discountLines->count() &&
                            in_array($proformaInvoice->status, ['no_discount', 'pending_approval']) &&
                            $authUser->hasAnyRole(['service_advisor', 'admin', 'super_admin']) &&
                            !$proformaInvoice->workOrder->invoice)
                        <a href="{{ route('proforma_invoices.edit', $proformaInvoice) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit / Add Discounts
                        </a>
                    @endif

                    {{-- Create Invoice once approved --}}
                    @if (in_array($proformaInvoice->status, ['approved', 'no_discount']) &&
                            \App\Helpers\PermissionHelper::canCreate('invoices'))
                        @if (!$proformaInvoice->workOrder->invoice)
                            <a href="{{ route('invoices.create', ['work_order_id' => $proformaInvoice->work_order_id]) }}"
                                class="btn btn-primary">
                                <i class="fas fa-file-invoice-dollar"></i> Create Invoice
                            </a>
                        @else
                            <a href="{{ route('invoices.show', $proformaInvoice->workOrder->invoice) }}"
                                class="btn btn-info">
                                <i class="fas fa-file-invoice-dollar"></i> View Invoice
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
