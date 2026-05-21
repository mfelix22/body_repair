@extends('layouts.admin')

@section('title', 'Proforma Invoices')
@section('page_title', 'Proforma Invoices')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Proforma Invoice List</h3>
                    <div class="card-tools">
                        @if (auth()->user()->hasAnyRole(['service_advisor', 'admin', 'super_admin']))
                            <a href="{{ route('proforma_invoices.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> New Proforma
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">

                    <table class="table table-bordered table-striped table-hover" id="proformaTable">
                        <thead>
                            <tr>
                                <th>Proforma #</th>
                                <th>Work Order</th>
                                <th>Customer</th>
                                <th>Subtotal</th>
                                <th>Discount</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($proformas as $pf)
                                <tr>
                                    <td>
                                        {{ $pf->proforma_number }}
                                        @if ($pf->pendingMyApproval)
                                            <span class="badge badge-warning ml-1">
                                                <i class="fas fa-clock"></i> Awaiting Your Approval
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $pf->workOrder->wo_number }}</td>
                                    <td>{{ $pf->workOrder->customer->name }}</td>
                                    <td>Rp {{ number_format($pf->subtotal, 0, ',', '.') }}</td>
                                    <td>
                                        @if ($pf->discount_amount > 0)
                                            Rp {{ number_format($pf->discount_amount, 0, ',', '.') }}
                                            <small
                                                class="text-muted">({{ number_format($pf->discount_percentage, 2) }}%)</small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td><strong>Rp {{ number_format($pf->total, 0, ',', '.') }}</strong></td>
                                    <td>
                                        @php
                                            $color = match ($pf->status) {
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                'no_discount' => 'secondary',
                                                default => 'warning',
                                            };
                                            $label =
                                                $pf->status === 'no_discount'
                                                    ? 'No Discount'
                                                    : ucwords(str_replace('_', ' ', $pf->status));
                                        @endphp
                                        <span class="badge badge-{{ $color }}">
                                            {{ $label }}
                                        </span>
                                    </td>
                                    <td>{{ $pf->creator->name }}</td>
                                    <td>{{ $pf->created_at->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ route('proforma_invoices.show', $pf) }}" class="btn btn-info btn-xs">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted">No proforma invoices yet.</td>
                                </tr>
                            @endforelse
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
            $('#proformaTable').DataTable({
                order: [
                    [8, 'desc']
                ],
                pageLength: 25,
            });
        });
    </script>
@endpush
