@extends('layouts.admin')

@section('title', 'Edit Invoice')
@section('page_title', 'Edit Invoice')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Invoice</h3>
                </div>

                <form action="{{ route('invoices.update', $invoice) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label>Work Order: {{ $invoice->workOrder->wo_number }}</label>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="invoice_date">Invoice Date <span class="text-danger">*</span></label>
                                    <input type="date" name="invoice_date" id="invoice_date" class="form-control"
                                        value="{{ old('invoice_date', $invoice->invoice_date->format('Y-m-d')) }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="due_date">Due Date</label>
                                    <input type="date" name="due_date" id="due_date" class="form-control"
                                        value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row d-none">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="discount_percentage">Discount (%)</label>
                                    <div class="input-group">
                                        <input type="number" name="discount_percentage" id="discount_percentage"
                                            class="form-control"
                                            value="{{ old('discount_percentage', $invoice->discount_percentage ?? 0) }}"
                                            step="0.01" min="0" max="100" oninput="calculateTotal()">
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="discount_amount_display">Discount Amount</label>
                                    <input type="text" id="discount_amount_display" class="form-control" readonly
                                        disabled value="Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}">
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="discount_amount" id="discount_amount"
                            value="{{ $invoice->discount_amount }}">

                        <div class="form-group">
                            <label for="grand_total">Grand Total</label>
                            <input type="text" id="grand_total" class="form-control" readonly disabled
                                style="font-weight: bold; font-size: 1.2em;"
                                value="Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}">
                        </div>

                        @if ($isFinance && $invoice->workOrder?->account_code === 'ASURANSI')
                            <div class="form-group">
                                <label for="or_amount">OR (Own Risk) Amount</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" name="or_amount" id="or_amount"
                                        class="form-control @error('or_amount') is-invalid @enderror"
                                        step="1" min="0"
                                        value="{{ old('or_amount', $invoice->or_amount ?? 0) }}"
                                        oninput="calculateTotal()">
                                    @error('or_amount')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <small class="text-muted">Hanya dapat diisi oleh Finance untuk Work Order Asuransi.</small>
                            </div>
                        @endif

                        {{-- <div class="form-group">
                            <label for="qq">QQ <small class="text-muted">(Insurance / Third Party
                                    Name)</small></label>
                            <input type="text" name="qq" id="qq" class="form-control"
                                placeholder="e.g. PT Asuransi XYZ" value="{{ old('qq', $invoice->qq) }}">
                        </div> --}}

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $invoice->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update Invoice</button>
                        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function calculateTotal() {
            const subtotal = {{ $invoice->subtotal }};
            const discountPercentage = parseFloat(document.getElementById('discount_percentage').value) || 0;

            // Calculate discount amount from percentage
            const discountAmount = (subtotal * discountPercentage) / 100;

            const orInput = document.getElementById('or_amount');
            const orAmount = orInput ? (parseFloat(orInput.value) || 0) : 0;
            const total = subtotal - discountAmount - orAmount;

            // Update hidden field with discount amount for form submission
            document.getElementById('discount_amount').value = discountAmount.toFixed(2);

            // Update display fields
            document.getElementById('discount_amount_display').value = 'Rp ' + new Intl.NumberFormat('id-ID').format(
                discountAmount);
            document.getElementById('grand_total').value = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        }
    </script>
@endsection
