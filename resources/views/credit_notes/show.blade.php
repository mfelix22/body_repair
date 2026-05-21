@extends('layouts.admin')

@section('title', $creditNote->credit_note_number)
@section('page_title', 'Credit Note: ' . $creditNote->credit_note_number)

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $creditNote->credit_note_number }}</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canPrint('invoices'))
                            <a href="{{ \URL::temporarySignedRoute('credit_notes.print', now()->addMinutes(5), $creditNote) }}"
                                target="_blank" class="btn btn-default btn-sm">
                                <i class="fas fa-print"></i> Print
                            </a>
                        @endif
                        <a href="{{ route('credit_notes.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Credit Note Details</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Credit Note #:</th>
                                    <td>{{ $creditNote->credit_note_number }}</td>
                                </tr>
                                <tr>
                                    <th>Date:</th>
                                    <td>{{ $creditNote->credit_note_date->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Ref Invoice #:</th>
                                    <td>
                                        <a href="{{ route('invoices.show', $creditNote->invoice_id) }}">
                                            {{ $creditNote->invoice->invoice_number ?? '-' }}
                                        </a>
                                    </td>
                                </tr>
                                @if ($creditNote->cancellation_reason)
                                    <tr>
                                        <th>Cancellation Reason:</th>
                                        <td class="text-danger">{{ $creditNote->cancellation_reason }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6>Customer Details</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Customer:</th>
                                    <td>
                                        @if ($creditNote->customer)
                                            <a href="{{ route('customers.show', $creditNote->customer) }}">
                                                {{ $creditNote->customer->name }}
                                            </a>
                                            @if ($creditNote->qq)
                                                <span class="text-muted">- {{ $creditNote->qq }}</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @if ($creditNote->workOrder)
                                    <tr>
                                        <th>Work Order:</th>
                                        <td>
                                            <a href="{{ route('work_orders.show', $creditNote->workOrder) }}">
                                                {{ $creditNote->workOrder->wo_number }}
                                            </a>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <hr>

                    <div class="row justify-content-end">
                        <div class="col-md-4">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th>Subtotal</th>
                                    <td class="text-right">Rp {{ number_format($creditNote->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @if ($creditNote->discount_amount > 0)
                                    <tr>
                                        <th>Discount
                                            @if ($creditNote->discount_percentage > 0)
                                                ({{ number_format($creditNote->discount_percentage, 2) }}%)
                                            @endif
                                        </th>
                                        <td class="text-right text-danger">
                                            — Rp {{ number_format($creditNote->discount_amount, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endif
                                <tr class="table-active">
                                    <th><strong>Grand Total</strong></th>
                                    <td class="text-right">
                                        <strong>Rp {{ number_format($creditNote->grand_total, 0, ',', '.') }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if ($creditNote->notes)
                        <hr>
                        <p><strong>Notes:</strong><br>{{ $creditNote->notes }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
