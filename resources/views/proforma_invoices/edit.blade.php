@extends('layouts.admin')

@section('title', 'Edit Proforma: ' . $proformaInvoice->proforma_number)
@section('page_title', 'Edit Proforma Invoice: ' . $proformaInvoice->proforma_number)

@section('content')
    @php
        $wo = $proformaInvoice->workOrder;
        $woId = $wo->id;
        // woDetails is keyed by WO id (just one WO for edit)
        $woDetail = $woDetails[$woId] ?? null;
    @endphp

    <div class="row">
        <div class="col-md-11">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Proforma — {{ $proformaInvoice->proforma_number }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('proforma_invoices.show', $proformaInvoice) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                    </div>
                </div>

                <form action="{{ route('proforma_invoices.update', $proformaInvoice) }}" method="POST" id="proformaForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Work Order (read-only display) --}}
                        <div class="form-group">
                            <label>Work Order</label>
                            <p class="form-control-plaintext font-weight-bold">
                                {{ $wo->wo_number }} — {{ optional($wo->customer)->name }}
                                (Rp {{ number_format($wo->grand_total, 0, ',', '.') }})
                            </p>
                        </div>

                        <div class="alert alert-info">
                            <strong>WO Subtotal:</strong> Rp {{ number_format($wo->grand_total, 0, ',', '.') }}
                        </div>

                        {{-- Per-line discount cards (pre-rendered from Blade, same logic as create JS) --}}
                        <div id="discountLinesContainer">
                            @if ($woDetail)
                                @php
                                    $lines = [];
                                    if ($woDetail['panel']) {
                                        $lines[] = [
                                            'type' => 'package',
                                            'target_id' => '',
                                            'description' => $woDetail['panel']['description'],
                                            'original_price' => $woDetail['panel']['original_price'],
                                        ];
                                    }
                                    foreach ($woDetail['extra_items'] as $ei) {
                                        $lines[] = [
                                            'type' => 'extra_item',
                                            'target_id' => $ei['target_id'],
                                            'description' => $ei['description'],
                                            'original_price' => $ei['original_price'],
                                        ];
                                    }
                                    foreach ($woDetail['extra_labors'] as $el) {
                                        $lines[] = [
                                            'type' => 'extra_labor',
                                            'target_id' => $el['target_id'],
                                            'description' => $el['description'],
                                            'original_price' => $el['original_price'],
                                        ];
                                    }
                                    $cardCount = count($lines);
                                @endphp
                                @if ($cardCount === 0)
                                    <div class="alert alert-warning">
                                        This Work Order has no discountable items.
                                    </div>
                                @else
                                    @foreach ($lines as $idx => $line)
                                        @php
                                            $typeBadges = [
                                                'package' => 'badge-primary',
                                                'extra_item' => 'badge-info',
                                                'extra_labor' => 'badge-secondary',
                                            ];
                                            $typeLabels = [
                                                'package' => 'Panel',
                                                'extra_item' => 'Extra Item',
                                                'extra_labor' => 'Extra Labor',
                                            ];
                                            $oldPct = (float) old("lines.{$idx}.discount_percentage", 0);
                                        @endphp
                                        <div class="card card-outline card-info mb-3" id="card-{{ $idx }}">
                                            <div class="card-header py-2">
                                                <input type="hidden" name="lines[{{ $idx }}][target_type]"
                                                    value="{{ $line['type'] }}">
                                                <input type="hidden" name="lines[{{ $idx }}][target_id]"
                                                    id="htid-{{ $idx }}" value="{{ $line['target_id'] }}">
                                                <input type="hidden" name="lines[{{ $idx }}][description]"
                                                    value="{{ $line['description'] }}">
                                                <input type="hidden" name="lines[{{ $idx }}][original_price]"
                                                    id="horig-{{ $idx }}" value="{{ $line['original_price'] }}">
                                                <span
                                                    class="badge {{ $typeBadges[$line['type']] ?? 'badge-dark' }}">{{ $typeLabels[$line['type']] ?? $line['type'] }}</span>
                                                <strong class="ml-1">{{ $line['description'] }}</strong>
                                                <span class="text-muted ml-2">Rp
                                                    {{ number_format($line['original_price'], 0, ',', '.') }}</span>
                                            </div>
                                            <div class="card-body">
                                                <div class="row align-items-end">
                                                    <div class="col-md-3">
                                                        <div class="form-group mb-0">
                                                            <label class="mb-1 small">Discount % <span
                                                                    class="text-danger">*</span></label>
                                                            <div class="input-group input-group-sm">
                                                                <input type="number"
                                                                    name="lines[{{ $idx }}][discount_percentage]"
                                                                    id="pct-{{ $idx }}" class="form-control"
                                                                    min="0.01" max="100" step="0.01"
                                                                    value="{{ $oldPct ?: '' }}" placeholder="e.g. 10"
                                                                    oninput="updateLine({{ $idx }}, {{ $line['original_price'] }})">
                                                                <div class="input-group-append"><span
                                                                        class="input-group-text">%</span></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group mb-0">
                                                            <label class="mb-1 small">Discount Amount</label>
                                                            <input type="text" class="form-control form-control-sm"
                                                                id="disc-amt-{{ $idx }}" readonly
                                                                value="{{ $oldPct > 0 ? 'Rp ' . number_format(($line['original_price'] * $oldPct) / 100, 0, ',', '.') : '' }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group mb-0">
                                                            <label class="mb-1 small">Final Price</label>
                                                            <input type="text" class="form-control form-control-sm"
                                                                id="final-{{ $idx }}" readonly
                                                                value="{{ $oldPct > 0 ? 'Rp ' . number_format($line['original_price'] - ($line['original_price'] * $oldPct) / 100, 0, ',', '.') : '' }}">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div id="tier-badge-{{ $idx }}">
                                                            @if ($oldPct > 0 && $oldPct <= 20)
                                                                <span class="badge badge-warning">
                                                                    ≤ 20% tier</span>
                                                            @elseif ($oldPct > 20)
                                                                <span class="badge badge-danger">> 20% tier</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Approvers (auto-assigned, no dropdown) --}}
                                                <div id="approvers-{{ $idx }}" class="mt-3"
                                                    style="{{ $oldPct > 0 ? '' : 'display:none;' }}">
                                                    {{-- <=20%: Manager approves --}}
                                                    <div id="lt20-{{ $idx }}"
                                                        style="{{ $oldPct > 0 && $oldPct <= 20 ? '' : 'display:none;' }}">
                                                        <div class="alert alert-warning py-1 mb-2" style="font-size:.85em;">
                                                            <i class="fas fa-user-check"></i> 20% or below &mdash; approved
                                                            by
                                                            <strong>{{ $approverManager?->name ?? '(No Manager configured)' }}</strong>
                                                            (Manager)
                                                            .
                                                        </div>
                                                    </div>
                                                    {{-- >20%: Manager then Director, sequential --}}
                                                    <div id="gte20-{{ $idx }}"
                                                        style="{{ $oldPct > 20 ? '' : 'display:none;' }}">
                                                        <div class="alert alert-danger py-1 mb-2" style="font-size:.85em;">
                                                            <i class="fas fa-user-check"></i> Above 20% &mdash;
                                                            <strong>{{ $approverManager?->name ?? '(No Manager configured)' }}</strong>
                                                            (Manager) approves first,
                                                            then
                                                            <strong>{{ $approverDirector?->name ?? '(No Director configured)' }}</strong>
                                                            (Director).
                                                        </div>
                                                    </div>
                                                </div>
                                                <p class="text-muted small mt-2 mb-0"
                                                    id="approver-hint-{{ $idx }}"
                                                    style="{{ $oldPct > 0 ? 'display:none;' : '' }}">
                                                    <i class="fas fa-arrow-up"></i> Enter a discount percentage to see who
                                                    will approve.
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            @else
                                <div class="alert alert-warning">Unable to load discountable items for this Work Order.
                                </div>
                            @endif
                        </div>

                        {{-- Voucher section (mutually exclusive with % discount lines) --}}
                        <div id="voucherSection">
                            <div class="text-center my-3 position-relative">
                                <hr>
                                <span class="bg-white px-3 text-muted"
                                    style="position:absolute;top:-11px;left:50%;transform:translateX(-50%);">— OR apply a
                                    Voucher —</span>
                            </div>
                            <div class="card card-outline card-success mb-3">
                                <div class="card-header py-2">
                                    <h3 class="card-title small"><i class="fas fa-ticket-alt"></i> Voucher (from physical
                                        ticket)</h3>
                                </div>
                                <div class="card-body">
                                    @if ($proformaInvoice->voucher_code)
                                        <div class="alert alert-success py-2 mb-2">
                                            <i class="fas fa-check-circle"></i>
                                            Current voucher: <strong>{{ $proformaInvoice->voucher_code }}</strong>
                                            &nbsp; — Rp {{ number_format($proformaInvoice->voucher_amount, 0, ',', '.') }}
                                        </div>
                                    @endif
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group mb-0">
                                                <label class="small">Voucher Code <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="voucher_code" id="voucher_code_input"
                                                    class="form-control @error('voucher_code') is-invalid @enderror"
                                                    placeholder="e.g. SAVE50OFF" style="text-transform:uppercase;"
                                                    value="{{ old('voucher_code', $proformaInvoice->voucher_code) }}"
                                                    autocomplete="off">
                                                @error('voucher_code')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-0">
                                                <label class="small">Voucher Amount (Rp) <span
                                                        class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend"><span
                                                            class="input-group-text">Rp</span></div>
                                                    <input type="number" name="voucher_amount" id="voucher_amount_input"
                                                        class="form-control @error('voucher_amount') is-invalid @enderror"
                                                        placeholder="e.g. 50000" min="1" step="1"
                                                        value="{{ old('voucher_amount', $proformaInvoice->voucher_amount > 0 ? (int) $proformaInvoice->voucher_amount : '') }}"
                                                        oninput="updateVoucherPreview()">
                                                    @error('voucher_amount')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <div id="voucherPreview" class="alert alert-success py-2 mb-0 w-100"
                                                style="{{ $proformaInvoice->voucher_amount > 0 ? '' : 'display:none;' }}">
                                                <i class="fas fa-ticket-alt"></i>
                                                Discount: <strong id="vAmt">
                                                    @if ($proformaInvoice->voucher_amount > 0)
                                                        Rp
                                                        {{ number_format($proformaInvoice->voucher_amount, 0, ',', '.') }}
                                                    @else
                                                        —
                                                    @endif
                                                </strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="voucherBlockedMsg" class="alert alert-warning py-2 mt-2 mb-0"
                                        style="display:none;">
                                        <i class="fas fa-lock"></i> Remove all discount percentages above to use a voucher
                                        instead.
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div class="form-group mt-3">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $proformaInvoice->notes) }}</textarea>
                        </div>

                        {{-- Grand Total Preview --}}
                        @if ($woDetail)
                            <div class="alert alert-success" id="grandTotalPreview">
                                <div class="row text-center">
                                    <div class="col-sm-4">
                                        <div class="text-muted small">WO Subtotal</div>
                                        <strong>Rp {{ number_format($wo->grand_total, 0, ',', '.') }}</strong>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="text-muted small">Total Discount</div>
                                        <strong id="ptDiscount">—</strong>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="text-muted small">Final Total</div>
                                        <strong id="ptTotal" class="h5 text-success">—</strong>
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>{{-- /card-body --}}

                    <div class="card-footer">
                        <a href="{{ route('proforma_invoices.show', $proformaInvoice) }}"
                            class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary float-right" id="submitBtn">
                            <i class="fas fa-paper-plane" id="submitIcon"></i>
                            <span
                                id="submitLabel">{{ $proformaInvoice->voucher_code ? 'Update Voucher' : 'Submit for Approval' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const WO_SUBTOTAL = {{ $wo->grand_total }};
        const CARD_COUNT = {{ isset($cardCount) ? $cardCount : 0 }};
        let currentVoucherAmount =
            {{ $proformaInvoice->voucher_amount > 0 ? (float) $proformaInvoice->voucher_amount : 0 }};

        function fmtRp(n) {
            n = parseFloat(n) || 0;
            return 'Rp\u00a0' + n.toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }

        function updateLine(idx, origPrice) {
            const pct = parseFloat($('#pct-' + idx).val()) || 0;
            const discAmt = origPrice * pct / 100;
            const final = origPrice - discAmt;

            if (pct > 0) {
                $('#disc-amt-' + idx).val(fmtRp(discAmt));
                $('#final-' + idx).val(fmtRp(final));
            } else {
                $('#disc-amt-' + idx).val('');
                $('#final-' + idx).val('');
            }

            const lt20 = $('#lt20-' + idx);
            const gte20 = $('#gte20-' + idx);
            const appDiv = $('#approvers-' + idx);
            const hint = $('#approver-hint-' + idx);
            const badge = $('#tier-badge-' + idx);

            if (pct <= 0) {
                appDiv.hide();
                hint.show();
                badge.html('');
            } else if (pct <= 20) {
                appDiv.show();
                hint.hide();
                badge.html('<span class="badge badge-warning">≤ 20% tier</span>');
                lt20.show();
                gte20.hide();
            } else {
                appDiv.show();
                hint.hide();
                badge.html('<span class="badge badge-danger">> 20% tier</span>');
                lt20.hide();
                gte20.show();
            }

            updateGrandTotal();
            syncVoucherMutualExclusion();
        }

        function syncVoucherMutualExclusion() {
            const hasLines = anyLineHasPercent();
            const voucherCodeEl = document.getElementById('voucher_code_input');
            const voucherAmtEl = document.getElementById('voucher_amount_input');
            if (hasLines) {
                $(voucherCodeEl).prop('disabled', true);
                $(voucherAmtEl).prop('disabled', true);
                $('#voucherBlockedMsg').show();
                if (voucherCodeEl) voucherCodeEl.value = '';
                if (voucherAmtEl) voucherAmtEl.value = '';
                currentVoucherAmount = 0;
                $('#voucherPreview').hide();
                updateGrandTotal();
            } else {
                $(voucherCodeEl).prop('disabled', false);
                $(voucherAmtEl).prop('disabled', false);
                $('#voucherBlockedMsg').hide();
            }
        }

        function anyLineHasPercent() {
            for (let i = 0; i < CARD_COUNT; i++) {
                const el = document.getElementById('pct-' + i);
                if (el && parseFloat(el.value) > 0) return true;
            }
            return false;
        }

        function updateVoucherPreview() {
            const amt = parseFloat($('#voucher_amount_input').val()) || 0;
            currentVoucherAmount = amt;
            if (amt > 0) {
                $('#vAmt').text(fmtRp(amt));
                $('#voucherPreview').show();
            } else {
                $('#voucherPreview').hide();
            }
            updateGrandTotal();
        }

        // When voucher amount changes → disable % inputs (mutual exclusion)
        $('#voucher_amount_input').on('input', function() {
            const amt = parseFloat($(this).val()) || 0;
            for (let i = 0; i < CARD_COUNT; i++) {
                $('#pct-' + i).prop('disabled', amt > 0);
            }
        });

        function updateGrandTotal() {
            let totalDiscount = 0;
            for (let i = 0; i < CARD_COUNT; i++) {
                const pctEl = document.getElementById('pct-' + i);
                const origEl = document.getElementById('horig-' + i);
                if (!pctEl || !origEl) continue;
                const pct = parseFloat(pctEl.value) || 0;
                const orig = parseFloat(origEl.value) || 0;
                totalDiscount += orig * pct / 100;
            }
            $('#ptDiscount').text(fmtRp(totalDiscount + currentVoucherAmount));
            $('#ptTotal').text(fmtRp(WO_SUBTOTAL - totalDiscount - currentVoucherAmount));
        }

        // Pre-submit: disable empty cards
        $('#proformaForm').on('submit', function(e) {
            const hasVoucher = ($('#voucher_amount_input').val() || '').trim() !== '' &&
                parseFloat($('#voucher_amount_input').val()) > 0;

            if (hasVoucher) {
                for (let i = 0; i < CARD_COUNT; i++) {
                    document.querySelectorAll('#card-' + i + ' input, #card-' + i + ' select')
                        .forEach(el => {
                            el.disabled = true;
                        });
                }
                return;
            }
            let activeCount = 0;
            for (let i = 0; i < CARD_COUNT; i++) {
                const pctEl = document.getElementById('pct-' + i);
                if (!pctEl) continue;
                const pct = parseFloat(pctEl.value) || 0;
                if (pct <= 0) {
                    document.querySelectorAll('#card-' + i + ' input, #card-' + i + ' select')
                        .forEach(el => {
                            el.disabled = true;
                        });
                } else {
                    activeCount++;
                }
            }
            if (activeCount === 0 && CARD_COUNT > 0) {
                e.preventDefault();
                alert(
                    'Please enter a discount percentage for at least one line, or fill in a voucher code and amount.'
                );
            }
        });

        // Trigger initial calc for any pre-filled values
        $(document).ready(function() {
            for (let i = 0; i < CARD_COUNT; i++) {
                const pctEl = document.getElementById('pct-' + i);
                const origEl = document.getElementById('horig-' + i);
                if (pctEl && origEl && parseFloat(pctEl.value) > 0) {
                    updateLine(i, parseFloat(origEl.value));
                }
            }
            // If pre-populated with a voucher, disable % inputs
            if (currentVoucherAmount > 0) {
                for (let i = 0; i < CARD_COUNT; i++) {
                    $('#pct-' + i).prop('disabled', true);
                }
                updateGrandTotal();
            }
        });
    </script>
@endsection
