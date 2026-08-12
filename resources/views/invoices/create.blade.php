@extends('layouts.admin')

@section('title', 'Create Invoice')
@section('page_title', 'Create Invoice')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Invoice Details</h3>
                </div>

                <form action="{{ route('invoices.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Invoice Number will be auto-generated
                        </div>

                        <div class="form-group">
                            <label for="work_order_id">Work Order <span class="text-danger">*</span></label>
                            @if (isset($selectedWorkOrderId))
                                {{-- Locked: came from a specific WO or proforma --}}
                                <select name="work_order_id" id="work_order_id"
                                    class="form-control select2 @error('work_order_id') is-invalid @enderror" required
                                    disabled onchange="updateWorkOrderDetails()">
                                    @foreach ($workOrders as $wo)
                                        <option value="{{ $wo->id }}" data-subtotal="{{ $wo->grand_total }}"
                                            data-account-code="{{ $wo->account_code }}"
                                            data-discount-amt="{{ ($wo->approvedProforma?->discount_amount ?? 0) + ($wo->approvedProforma?->voucher_amount ?? 0) }}"
                                            data-proforma="{{ $wo->approvedProforma?->proforma_number ?? '' }}"
                                            {{ $selectedWorkOrderId == $wo->id ? 'selected' : '' }}>
                                            {{ $wo->wo_number }} - {{ $wo->customer->name }}
                                            (Rp {{ number_format($wo->grand_total, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                                {{-- disabled fields are not submitted, so include a hidden input --}}
                                <input type="hidden" name="work_order_id" value="{{ $selectedWorkOrderId }}">
                            @else
                                <select name="work_order_id" id="work_order_id"
                                    class="form-control select2 @error('work_order_id') is-invalid @enderror" required
                                    onchange="updateWorkOrderDetails()">
                                    <option value="">Select Work Order</option>
                                    @foreach ($workOrders as $wo)
                                        <option value="{{ $wo->id }}" data-subtotal="{{ $wo->grand_total }}"
                                            data-account-code="{{ $wo->account_code }}"
                                            data-discount-amt="{{ ($wo->approvedProforma?->discount_amount ?? 0) + ($wo->approvedProforma?->voucher_amount ?? 0) }}"
                                            data-proforma="{{ $wo->approvedProforma?->proforma_number ?? '' }}"
                                            {{ old('work_order_id') == $wo->id ? 'selected' : '' }}>
                                            {{ $wo->wo_number }} - {{ $wo->customer->name }}
                                            (Rp {{ number_format($wo->grand_total, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                            @error('work_order_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Proforma info banner --}}
                        <div id="proformaBanner" class="alert alert-info" style="display:none;">
                            <i class="fas fa-file-invoice"></i>
                            Linked Proforma: <strong id="proformaNumber">—</strong> &nbsp;|&nbsp;
                            Discount: <strong id="proformaDiscount">—</strong>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="invoice_date">Invoice Date <span class="text-danger">*</span></label>
                                    <input type="date" name="invoice_date" id="invoice_date"
                                        class="form-control @error('invoice_date') is-invalid @enderror"
                                        value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                                    @error('invoice_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="due_date">Due Date</label>
                                    <input type="date" name="due_date" id="due_date"
                                        class="form-control @error('due_date') is-invalid @enderror"
                                        value="{{ old('due_date') }}">
                                    @error('due_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="subtotal">Subtotal (from WO)</label>
                                    <input type="text" id="subtotal" class="form-control" readonly disabled>
                                </div>
                            </div>
                        </div>

                        {{-- Discount display — read-only, sourced from approved proforma --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Discount % <small class="text-muted">(from Proforma)</small></label>
                                    <input type="text" id="discountPctDisplay" class="form-control" readonly disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Discount Amount <small class="text-muted">(from Proforma)</small></label>
                                    <input type="text" id="discount_amount_display" class="form-control" readonly
                                        disabled>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="grand_total">Grand Total</label>
                            <input type="text" id="grand_total" class="form-control" readonly disabled
                                style="font-weight: bold; font-size: 1.2em;">
                        </div>

                        {{-- <div class="form-group">
                            <label for="qq">QQ</label>
                            <input type="text" name="qq" id="qq" class="form-control"
                                value="{{ old('qq') }}">
                        </div> --}}

                        @if ($isFinance)
                            <div class="form-group" id="or_amount_group" style="display:none;">
                                <label for="or_amount">OR (Own Risk) Amount</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" name="or_amount" id="or_amount"
                                        class="form-control @error('or_amount') is-invalid @enderror"
                                        step="1" min="0" value="{{ old('or_amount', 0) }}"
                                        oninput="updateWorkOrderDetails()">
                                    @error('or_amount')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <small class="text-muted">Khusus Work Order Asuransi. Hanya dapat diisi oleh Finance.</small>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Create Invoice</button>
                        <a href="{{ route('invoices.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function fmt(n) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(parseFloat(n) || 0);
        }

        function updateWorkOrderDetails() {
            const sel = document.getElementById('work_order_id');
            const opt = sel.options[sel.selectedIndex];
            const subtotal = parseFloat(opt.dataset.subtotal) || 0;
            const discountAmt = parseFloat(opt.dataset.discountAmt) || 0;
            const effectivePct = subtotal > 0 ? (discountAmt / subtotal * 100) : 0;
            const proformaNum = opt.dataset.proforma || '';
            const accountCode = opt.dataset.accountCode || '';
            const orAmount = (accountCode === 'ASURANSI')
                ? (parseFloat(document.getElementById('or_amount')?.value) || 0)
                : 0;
            const total = subtotal - discountAmt - orAmount;

            const orGroup = document.getElementById('or_amount_group');
            if (orGroup) {
                orGroup.style.display = accountCode === 'ASURANSI' ? 'block' : 'none';
            }

            document.getElementById('subtotal').value = fmt(subtotal);
            document.getElementById('discountPctDisplay').value = effectivePct.toFixed(2) + '%';
            document.getElementById('discount_amount_display').value = fmt(discountAmt);
            document.getElementById('grand_total').value = fmt(total);

            if (proformaNum) {
                document.getElementById('proformaNumber').textContent = proformaNum;
                document.getElementById('proformaDiscount').textContent =
                    discountAmt > 0 ? (fmt(discountAmt) + ' (' + effectivePct.toFixed(2) + '%)') : 'No discount';
                document.getElementById('proformaBanner').style.display = 'block';
            } else {
                document.getElementById('proformaBanner').style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const sel = document.getElementById('work_order_id');
            if (sel.value) updateWorkOrderDetails();
        });
    </script>
@endsection
