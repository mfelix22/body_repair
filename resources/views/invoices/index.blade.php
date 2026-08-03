@extends('layouts.admin')

@section('title', 'Invoices')
@section('page_title', 'Invoices')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Invoices</h3>
                    <div class="card-tools">
                        @if ($canModify)
                            <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Create Invoice
                            </a>
                        @endif
                    </div>
                </div>


                <div class="card-body">
                    @php
                        $activeMonth = request('month');
                        $activeYear = request('year', date('Y'));
                    @endphp
                    <form method="GET" action="{{ route('invoices.index') }}" class="form-inline mb-3">
                        <div class="form-group mr-2">
                            <label for="month" class="mr-1 font-weight-bold">Month</label>
                            <select name="month" id="month" class="form-control form-control-sm">
                                <option value="">-- All Months --</option>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ (int) $activeMonth === $m ? 'selected' : '' }}>
                                        {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group mr-2">
                            <label for="year" class="mr-1 font-weight-bold">Year</label>
                            <select name="year" id="year" class="form-control form-control-sm">
                                <option value="">-- All Years --</option>
                                @foreach ($allYears as $y)
                                    <option value="{{ $y }}"
                                        {{ (int) $activeYear === (int) $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-info btn-sm mr-1">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                        @if (request('month') || request('year'))
                            <a href="{{ route('invoices.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times mr-1"></i>Clear
                            </a>
                        @endif
                        @if (request('month') || request('year'))
                            <span class="ml-3 text-muted small">
                                Showing {{ $invoices->count() }} invoice(s)
                            </span>
                        @endif
                    </form>

                    <table id="invoices-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Invoice Date</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                <tr>
                                    <td><strong>{{ $invoice->invoice_number }}</strong></td>
                                    <td>{{ $invoice->customer->name }}</td>
                                    <td>{{ $invoice->invoice_date->format('M d, Y') }}</td>
                                    <td>{{ $invoice->due_date?->format('M d, Y') ?? '-' }}</td>
                                    <td><strong>Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</strong></td>
                                    <td>
                                        <span
                                            class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'cancelled' ? 'danger' : ($invoice->status === 'on_progress' ? 'secondary' : 'info')) }}">
                                            {{ $invoice->status === 'on_progress' ? 'On Progress' : ucfirst($invoice->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if ($canEdit && $invoice->status === 'on_progress')
                                            <a href="{{ route('invoices.edit', $invoice) }}"
                                                class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif

                                        @if ($canChangeStatus && in_array($invoice->status, ['on_progress', 'sent', 'partial']))
                                            <form action="{{ route('invoices.markAsPaid', $invoice) }}" method="POST"
                                                class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm" title="Mark as Paid">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('invoices.cancel', $invoice) }}" method="POST"
                                                class="d-inline" onsubmit="return confirm('Cancel this invoice?')">
                                                @csrf
                                                <button type="button" class="btn btn-danger btn-sm" title="Cancel Invoice"
                                                    data-toggle="modal" data-target="#cancelModal"
                                                    data-invoice-id="{{ $invoice->id }}"
                                                    data-invoice-number="{{ $invoice->invoice_number }}"
                                                    data-cancel-url="{{ route('invoices.cancel', $invoice) }}">
                                                    <i class="fas fa-ban"></i>
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

    {{-- Shared Cancel Invoice Modal --}}
    <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="cancelForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="cancelModalLabel">Cancel Invoice</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">The work order will be reverted to <strong>Completed</strong> status.</p>
                        <div class="form-group">
                            <label for="cancellation_reason_idx">Reason for Cancellation <span
                                    class="text-danger">*</span></label>
                            <textarea name="cancellation_reason" id="cancellation_reason_idx" rows="3" class="form-control"
                                placeholder="Explain why this invoice is being cancelled..." required maxlength="500"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Confirm Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            $(function() {
                $('#invoices-table').DataTable({
                    order: [[2, 'desc']],
                    pageLength: 25,
                });
            });

            $('#cancelModal').on('show.bs.modal', function(e) {
                var btn = $(e.relatedTarget);
                var url = btn.data('cancel-url');
                var number = btn.data('invoice-number');
                $(this).find('#cancelModalLabel').text('Cancel Invoice ' + number);
                $(this).find('#cancelForm').attr('action', url);
                $(this).find('#cancellation_reason_idx').val('');
            });
        </script>
    @endpush
@endsection
