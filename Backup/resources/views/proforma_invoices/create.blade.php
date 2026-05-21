@extends('layouts.admin')

@section('title', 'Create Proforma Invoice')
@section('page_title', 'Create Proforma Invoice')

@section('content')
    <div class="row">
        <div class="col-md-11">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">New Proforma Invoice</h3>
                </div>

                <form action="{{ route('proforma_invoices.store') }}" method="POST" id="proformaForm">
                    @csrf
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

                        {{-- Work Order --}}
                        <div class="form-group">
                            <label>Work Order <span class="text-danger">*</span></label>
                            <select name="work_order_id" id="work_order_id" class="form-control select2" required>
                                <option value="">— Select Work Order —</option>
                                @foreach ($workOrders as $wo)
                                    <option value="{{ $wo->id }}"
                                        {{ old('work_order_id', $selectedWorkOrderId) == $wo->id ? 'selected' : '' }}>
                                        {{ $wo->wo_number }} — {{ optional($wo->customer)->name }}
                                        (Rp {{ number_format($wo->grand_total, 0, ',', '.') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('work_order_id')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- WO Summary --}}
                        <div id="woSummary" class="alert alert-info" style="display:none;">
                            <strong>Customer:</strong> <span id="woCustomer">—</span>
                            &nbsp;&nbsp;
                            <strong>WO Total:</strong> <span id="woSubtotalDisplay">—</span>
                        </div>

                        {{-- Per-line discount cards --}}
                        <div id="discountLinesContainer">
                            <p class="text-muted" id="noWoMsg">Select a Work Order to set up discount lines.</p>
                        </div>

                        {{-- Voucher section (mutually exclusive with % discount lines) --}}
                        <div id="voucherSection" style="display:none;">
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
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group mb-0">
                                                <label class="small">Voucher Code <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="voucher_code" id="voucher_code_input"
                                                    class="form-control @error('voucher_code') is-invalid @enderror"
                                                    placeholder="e.g. SAVE50OFF" style="text-transform:uppercase;"
                                                    value="{{ old('voucher_code') }}" autocomplete="off">
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
                                                        value="{{ old('voucher_amount') }}"
                                                        oninput="updateVoucherPreview()">
                                                    @error('voucher_amount')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-end">
                                            <div id="voucherPreview" class="alert alert-success py-2 mb-0 w-100"
                                                style="display:none;">
                                                <i class="fas fa-ticket-alt"></i>
                                                Discount: <strong id="vAmt">—</strong>
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
                        <div class="form-group mt-3" id="notesGroup" style="display:none;">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>

                        {{-- Grand Total Preview --}}
                        <div id="grandTotalPreview" class="alert alert-success" style="display:none;">
                            <div class="row text-center text-white">
                                <div class="col-sm-4">
                                    <div class="small">WO Subtotal</div>
                                    <strong id="ptSubtotal">—</strong>
                                </div>
                                <div class="col-sm-4">
                                    <div class="small">Total Discount</div>
                                    <strong id="ptDiscount">—</strong>
                                </div>
                                <div class="col-sm-4">
                                    <div class="small">Final Total</div>
                                    <strong id="ptTotal" class="h5">—</strong>
                                </div>
                            </div>
                        </div>

                    </div>{{-- /card-body --}}

                    <div class="card-footer">
                        <a href="{{ route('proforma_invoices.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary float-right" id="submitBtn" disabled>
                            <i class="fas fa-paper-plane" id="submitIcon"></i>
                            <span id="submitLabel">Submit for Approval</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @php
        // Build HTML option strings for each approver pool
        $allOpts = '<option value="">— Select approver —</option>';
        foreach ($approverCandidates as $u) {
            $allOpts .= '<option value="' . $u->id . '">' . e($u->name) . '</option>';
        }
        $mgrAccOpts = '<option value="">— Select approver —</option>';
        foreach ($managerAccountingCandidates as $u) {
            $mgrAccOpts .= '<option value="' . $u->id . '">' . e($u->name) . '</option>';
        }
        $dirOpts = '<option value="">— Select approver —</option>';
        foreach ($directorCandidates as $u) {
            $dirOpts .= '<option value="' . $u->id . '">' . e($u->name) . '</option>';
        }
    @endphp
    <script>
        const WO_DETAILS = @json($woDetails);
        const ALL_OPTS = @json($allOpts);
        const MGR_ACC_OPTS = @json($mgrAccOpts);
        const DIR_OPTS = @json($dirOpts);

        let cardCount = 0; // total cards rendered for this WO
        let currentVoucherAmount = 0;

        // ─── Helpers ────────────────────────────────────────────────────────────────
        function fmtRp(n) {
            n = parseFloat(n) || 0;
            return 'Rp\u00a0' + n.toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }

        function escHtml(s) {
            return String(s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // ─── WO Change ──────────────────────────────────────────────────────────────
        $('#work_order_id').on('change', function() {
            const woId = $(this).val();
            const wrap = $('#discountLinesContainer');
            wrap.empty();
            $('#woSummary, #grandTotalPreview, #notesGroup, #voucherSection').hide();
            $('#submitBtn').prop('disabled', true);
            cardCount = 0;
            currentVoucherAmount = 0;
            $('#voucher_code_input, #voucher_amount_input').val('').prop('disabled', false);
            $('#voucherPreview').hide();
            $('#voucherBlockedMsg').hide();

            if (!woId || !WO_DETAILS[woId]) {
                wrap.html('<p class="text-muted" id="noWoMsg">Select a Work Order to set up discount lines.</p>');
                return;
            }

            const wo = WO_DETAILS[woId];
            $('#woCustomer').text(wo.customer);
            $('#woSubtotalDisplay').text(fmtRp(wo.subtotal));
            $('#woSummary').show();

            // Collect discountable lines in display order
            const lines = [];
            if (wo.package) {
                lines.push({
                    type: 'package',
                    target_id: '',
                    description: wo.package.description,
                    original_price: wo.package.original_price
                });
            }
            (wo.extra_items || []).forEach(i => lines.push({
                type: 'extra_item',
                target_id: i.target_id,
                description: i.description,
                original_price: i.original_price
            }));
            (wo.extra_labors || []).forEach(l => lines.push({
                type: 'extra_labor',
                target_id: l.target_id,
                description: l.description,
                original_price: l.original_price
            }));

            if (lines.length === 0) {
                wrap.html(
                    '<div class="alert alert-warning"><i class="fas fa-exclamation-circle"></i> This Work Order has no discountable items. The WO total will be invoiced in full.</div>'
                );
                $('#notesGroup').show();
                return;
            }

            lines.forEach((line, i) => {
                wrap.append(buildCard(cardCount++, line));
            });

            // Init Select2 on newly inserted selects
            wrap.find('select').select2({
                width: '100%'
            });

            $('#notesGroup, #grandTotalPreview, #voucherSection').show();
            updateGrandTotal();
            $('#submitBtn').prop('disabled', false);
        });

        // ─── Card Builder ────────────────────────────────────────────────────────────
        function buildCard(idx, line) {
            const labels = {
                package: 'Package',
                extra_item: 'Extra Item',
                extra_labor: 'Extra Labor'
            };
            const badges = {
                package: 'badge-primary',
                extra_item: 'badge-info',
                extra_labor: 'badge-secondary'
            };
            const typeLabel = labels[line.type] || line.type;
            const typeBadge = badges[line.type] || 'badge-dark';

            return `
<div class="card card-outline card-info mb-3" id="card-${idx}">
  <div class="card-header py-2">
    <input type="hidden" name="lines[${idx}][target_type]"   id="ht-${idx}" value="${escHtml(line.type)}">
    <input type="hidden" name="lines[${idx}][target_id]"     id="htid-${idx}" value="${escHtml(String(line.target_id))}">
    <input type="hidden" name="lines[${idx}][description]"   id="hdesc-${idx}" value="${escHtml(line.description)}">
    <input type="hidden" name="lines[${idx}][original_price]" id="horig-${idx}" value="${line.original_price}">
    <span class="badge ${typeBadge}">${typeLabel}</span>
    <strong class="ml-1">${escHtml(line.description)}</strong>
    <span class="text-muted ml-2">${fmtRp(line.original_price)}</span>
  </div>
  <div class="card-body">
    <div class="row align-items-end">
      <div class="col-md-3">
        <div class="form-group mb-0">
          <label class="mb-1 small">Discount % <span class="text-danger">*</span></label>
          <div class="input-group input-group-sm">
            <input type="number" name="lines[${idx}][discount_percentage]" id="pct-${idx}"
                   class="form-control" min="0.01" max="100" step="0.01" placeholder="e.g. 10"
                   oninput="updateLine(${idx}, ${line.original_price})">
            <div class="input-group-append"><span class="input-group-text">%</span></div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group mb-0">
          <label class="mb-1 small">Discount Amount</label>
          <input type="text" class="form-control form-control-sm" id="disc-amt-${idx}" readonly placeholder="—">
        </div>
      </div>
      <div class="col-md-3">
        <div class="form-group mb-0">
          <label class="mb-1 small">Final Price</label>
          <input type="text" class="form-control form-control-sm" id="final-${idx}" readonly placeholder="—">
        </div>
      </div>
      <div class="col-md-3">
        <div id="tier-badge-${idx}"></div>
      </div>
    </div>

    {{-- Approvers --}}
    <div id="approvers-${idx}" class="mt-3" style="display:none;">
      {{-- < 20% group: any 1 of 3 --}}
      <div id="lt20-${idx}">
        <div class="alert alert-warning py-1 mb-2" style="font-size:.85em;">
          <i class="fas fa-info-circle"></i> Under 20% &mdash; <strong>any one</strong> of the 3 approvers approving is sufficient.
        </div>
        <div class="row">
          <div class="col-md-4">
            <div class="form-group mb-1">
              <label class="small">Approver 1 <span class="text-danger">*</span></label>
              <select name="lines[${idx}][approver1_id]" id="lt20-a1-${idx}" class="form-control form-control-sm select2">${ALL_OPTS}</select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group mb-1">
              <label class="small">Approver 2 <span class="text-danger">*</span></label>
              <select name="lines[${idx}][approver2_id]" id="lt20-a2-${idx}" class="form-control form-control-sm select2">${ALL_OPTS}</select>
            </div>
          </div>
          <div class="col-md-4">
            <div class="form-group mb-1">
              <label class="small">Approver 3 <span class="text-danger">*</span></label>
              <select name="lines[${idx}][approver3_id]" id="lt20-a3-${idx}" class="form-control form-control-sm select2">${ALL_OPTS}</select>
            </div>
          </div>
        </div>
      </div>

      {{-- >= 20% group: Mgr/Acc then Director, sequential --}}
      <div id="gte20-${idx}" style="display:none;">
        <div class="alert alert-danger py-1 mb-2" style="font-size:.85em;">
          <i class="fas fa-exclamation-triangle"></i> 20% or above &mdash; <strong>both</strong> approvers must approve in sequence (Mgr/Acc first, then Director).
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="form-group mb-1">
              <label class="small">Approver 1 — Manager / Accounting <span class="text-danger">*</span></label>
              <select name="lines[${idx}][approver1_id]" id="gte20-a1-${idx}" class="form-control form-control-sm select2" disabled>${MGR_ACC_OPTS}</select>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group mb-1">
              <label class="small">Approver 2 — Director <span class="text-danger">*</span></label>
              <select name="lines[${idx}][approver2_id]" id="gte20-a2-${idx}" class="form-control form-control-sm select2" disabled>${DIR_OPTS}</select>
            </div>
          </div>
        </div>
        <input type="hidden" name="lines[${idx}][approver3_id]" id="gte20-a3-${idx}" value="" disabled>
      </div>
    </div>
    <p class="text-muted small mt-2 mb-0" id="approver-hint-${idx}">
      <i class="fas fa-arrow-up"></i> Enter a discount percentage to configure approval.
    </p>
  </div>
</div>`;
        }

        // ─── Line Update (on % input) ────────────────────────────────────────────────
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
                // Disable everything in approver sections so they are excluded from form
                lt20.find('select').prop('disabled', true);
                gte20.find('select, input[type=hidden]').prop('disabled', true);
            } else if (pct < 20) {
                appDiv.show();
                hint.hide();
                badge.html('<span class="badge badge-warning">< 20% tier</span>');
                // Show lt20, hide gte20
                lt20.show();
                gte20.hide();
                lt20.find('select').prop('disabled', false);
                gte20.find('select, input[type=hidden]').prop('disabled', true);
            } else {
                appDiv.show();
                hint.hide();
                badge.html('<span class="badge badge-danger">≥ 20% tier</span>');
                // Show gte20, hide lt20
                lt20.hide();
                gte20.show();
                lt20.find('select').prop('disabled', true);
                gte20.find('select, input[type=hidden]').prop('disabled', false);
            }

            // Mutual exclusion: if any % entered, block voucher section
            syncVoucherMutualExclusion();
            updateGrandTotal();
        }

        // ─── Grand Total Preview ─────────────────────────────────────────────────────
        function updateGrandTotal() {
            const woId = $('#work_order_id').val();
            if (!woId || !WO_DETAILS[woId]) return;
            const subtotal = WO_DETAILS[woId].subtotal;
            let totalDiscount = 0;

            for (let i = 0; i < cardCount; i++) {
                const pctEl = document.getElementById('pct-' + i);
                const origEl = document.getElementById('horig-' + i);
                if (!pctEl || !origEl) continue;
                const pct = parseFloat(pctEl.value) || 0;
                const orig = parseFloat(origEl.value) || 0;
                totalDiscount += orig * pct / 100;
            }

            const vAmt = currentVoucherAmount;
            $('#ptSubtotal').text(fmtRp(subtotal));
            $('#ptDiscount').text(fmtRp(totalDiscount + vAmt));
            $('#ptTotal').text(fmtRp(subtotal - totalDiscount - vAmt));
        }

        // ─── Pre-submit: disable empty cards so they are not included ────────────────
        $('#proformaForm').on('submit', function(e) {
            const hasVoucher = ($('#voucher_amount_input').val() || '').trim() !== '' &&
                parseFloat($('#voucher_amount_input').val()) > 0;

            // Voucher path: disable all line inputs so they are not submitted
            if (hasVoucher) {
                for (let i = 0; i < cardCount; i++) {
                    document.querySelectorAll('#card-' + i + ' input, #card-' + i + ' select')
                        .forEach(el => {
                            el.disabled = true;
                        });
                }
                return; // allow submit
            }

            let activeCount = 0;
            for (let i = 0; i < cardCount; i++) {
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
            if (activeCount === 0 && cardCount > 0) {
                e.preventDefault();
                alert(
                    'Please enter a discount percentage for at least one line, or fill in a voucher code and amount.'
                );
            }
        });

        // ─── Voucher: helpers ────────────────────────────────────────────────────────
        function anyLineHasPercent() {
            for (let i = 0; i < cardCount; i++) {
                const el = document.getElementById('pct-' + i);
                if (el && parseFloat(el.value) > 0) return true;
            }
            return false;
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

        // When voucher amount changes, also update mutual exclusion flag
        $('#voucher_amount_input').on('input', function() {
            const amt = parseFloat($(this).val()) || 0;
            if (amt > 0) {
                // Entering a voucher amount → disable all % inputs
                for (let i = 0; i < cardCount; i++) {
                    $('#pct-' + i).prop('disabled', true);
                }
            } else {
                // Cleared → re-enable % inputs
                for (let i = 0; i < cardCount; i++) {
                    $('#pct-' + i).prop('disabled', false);
                }
            }
        });

        // ─── Repopulate old() values after validation failure ───────────────────────
        @if (old('work_order_id') && count(old('lines', [])) > 0)
            $(document).ready(function() {
                $('#work_order_id').trigger('change');
                // Let the DOM settle before re-filling values
                setTimeout(function() {
                    @foreach (old('lines', []) as $lineIdx => $line)
                        @php $pctOld = (float)($line['discount_percentage'] ?? 0); @endphp
                        @if ($pctOld > 0)
                            (function() {
                                const idx = {{ $lineIdx }};
                                const pctEl = document.getElementById('pct-' + idx);
                                if (!pctEl) return;
                                const origEl = document.getElementById('horig-' + idx);
                                const orig = origEl ? parseFloat(origEl.value) : 0;
                                pctEl.value = '{{ $pctOld }}';
                                updateLine(idx, orig);
                                @if (!empty($line['approver1_id']))
                                    $('#lt20-a1-' + idx + ', #gte20-a1-' + idx).val(
                                        '{{ $line['approver1_id'] }}').trigger('change');
                                @endif
                                @if (!empty($line['approver2_id']))
                                    $('#lt20-a2-' + idx + ', #gte20-a2-' + idx).val(
                                        '{{ $line['approver2_id'] }}').trigger('change');
                                @endif
                                @if (!empty($line['approver3_id']))
                                    $('#lt20-a3-' + idx).val('{{ $line['approver3_id'] }}').trigger(
                                        'change');
                                @endif
                            })();
                        @endif
                    @endforeach
                }, 500);
            });
        @endif

        {{-- Pre-select WO from query string (e.g. coming from WO show page) --}}
        @if (!old('work_order_id') && $selectedWorkOrderId)
            $(document).ready(function() {
                $('#work_order_id').trigger('change');
            });
        @endif
    </script>
@endsection

@push('scripts')
    @php
        $approverPoolsData = [
            'any' => $approverCandidates
                ->map(
                    fn($u) => [
                        'id' => $u->id,
                        'text' => $u->name . ' (' . ucwords(str_replace(['_', '|'], [' ', ', '], $u->role)) . ')',
                    ],
                )
                ->values()
                ->all(),
            'mgr_acc' => $managerAccountingCandidates
                ->map(
                    fn($u) => [
                        'id' => $u->id,
                        'text' => $u->name . ' (' . ucwords(str_replace(['_', '|'], [' ', ', '], $u->role)) . ')',
                    ],
                )
                ->values()
                ->all(),
            'director' => $directorCandidates
                ->map(
                    fn($u) => [
                        'id' => $u->id,
                        'text' => $u->name . ' (' . ucwords(str_replace(['_', '|'], [' ', ', '], $u->role)) . ')',
                    ],
                )
                ->values()
                ->all(),
        ];
    @endphp
    <script>
        // Approver pool data (used by switchApproverPool to rebuild Select2 options)
        const approverPools = @json($approverPoolsData);
        const oldApprovers = {
            approver1_id: '{{ old('approver1_id') }}',
            approver2_id: '{{ old('approver2_id') }}',
            approver3_id: '{{ old('approver3_id') }}',
        };

        let subtotal = 0;

        function fmt(n) {
            return 'Rp ' + Math.round(n).toLocaleString('id-ID');
        }

        function loadWoDetails() {
            const sel = document.getElementById('work_order_id');
            const opt = sel.options[sel.selectedIndex];
            subtotal = parseFloat(opt.dataset.subtotal) || 0;

            if (subtotal > 0) {
                document.getElementById('woCustomer').textContent = opt.dataset.customer || '—';
                document.getElementById('woSubtotal').textContent = fmt(subtotal);
                document.getElementById('woSummary').style.display = 'block';
                document.getElementById('calcSummary').style.display = 'block';
            } else {
                document.getElementById('woSummary').style.display = 'none';
                document.getElementById('calcSummary').style.display = 'none';
            }
            recalculate();
        }

        function toggleDiscount() {
            const type = document.getElementById('discount_type').value;
            const valGroup = document.getElementById('discountValueGroup');
            const prefix = document.getElementById('discountPrefix').querySelector('span');

            if (type) {
                valGroup.style.display = 'block';
                prefix.textContent = type === 'percentage' ? '%' : 'Rp';
            } else {
                valGroup.style.display = 'none';
                document.getElementById('discount_value').value = 0;
            }
            recalculate();
        }

        function recalculate() {
            const type = document.getElementById('discount_type').value;
            const value = parseFloat(document.getElementById('discount_value').value) || 0;

            let discountAmt = 0;
            let discountPct = 0;

            if (type === 'percentage') {
                discountPct = Math.min(value, 100);
                discountAmt = subtotal * discountPct / 100;
            } else if (type === 'amount') {
                discountAmt = Math.min(value, subtotal);
                discountPct = subtotal > 0 ? (discountAmt / subtotal * 100) : 0;
            }

            const total = subtotal - discountAmt;

            document.getElementById('calcSubtotal').textContent = fmt(subtotal);
            document.getElementById('calcDiscount').textContent = fmt(discountAmt);
            document.getElementById('calcDiscountPct').textContent = discountPct > 0 ? '(' + discountPct.toFixed(2) + '%)' :
                '';
            document.getElementById('calcTotal').textContent = fmt(total);

            const approvalSection = document.getElementById('approvalSection');
            const anyNotice = document.getElementById('anyOfThreeNotice');
            const seqNotice = document.getElementById('sequentialThreeNotice');

            if (discountAmt > 0) {
                approvalSection.style.display = 'block';
                if (discountPct >= 20) {
                    seqNotice.style.display = 'block';
                    anyNotice.style.display = 'none';
                    switchApproverPool('sequential');
                } else {
                    anyNotice.style.display = 'block';
                    seqNotice.style.display = 'none';
                    switchApproverPool('any');
                }
                syncApprovers();
            } else {
                approvalSection.style.display = 'none';
            }
        }

        // Rebuild Select2 options for a slot from a pool array
        function buildOptions($sel, pool, preserveVal) {
            $sel.find('option:not(:first)').remove();
            pool.forEach(function(u) {
                $sel.append(new Option(u.text, u.id, false, false));
            });
            // Restore old/existing value if still in pool
            if (preserveVal && pool.some(function(u) {
                    return String(u.id) === String(preserveVal);
                })) {
                $sel.val(preserveVal);
            } else {
                $sel.val('');
            }
            $sel.trigger('change.select2');
        }

        // Switch approver dropdown option pools based on mode
        function switchApproverPool(mode) {
            if (mode === 'sequential') {
                buildOptions($('#approver1_id'), approverPools.mgr_acc, oldApprovers.approver1_id);
                buildOptions($('#approver2_id'), approverPools.mgr_acc, oldApprovers.approver2_id);
                buildOptions($('#approver3_id'), approverPools.director, oldApprovers.approver3_id);
                document.getElementById('approver3Label').innerHTML =
                    'Approver 3 — Director <span class="text-danger">*</span>';
            } else {
                buildOptions($('#approver1_id'), approverPools.any, oldApprovers.approver1_id);
                buildOptions($('#approver2_id'), approverPools.any, oldApprovers.approver2_id);
                buildOptions($('#approver3_id'), approverPools.any, oldApprovers.approver3_id);
                document.getElementById('approver3Label').innerHTML = 'Approver 3 <span class="text-danger">*</span>';
            }
        }

        // Mutual exclusion across all 3 dropdowns
        function syncApprovers() {
            const vals = [
                $('#approver1_id').val(),
                $('#approver2_id').val(),
                $('#approver3_id').val(),
            ];
            ['approver1_id', 'approver2_id', 'approver3_id'].forEach(function(id, idx) {
                const others = vals.filter(function(v, i) {
                    return i !== idx && v;
                });
                $('#' + id + ' option').prop('disabled', false);
                others.forEach(function(ov) {
                    $('#' + id + ' option[value="' + ov + '"]').prop('disabled', true);
                });
                if (others.includes(vals[idx])) {
                    $('#' + id).val('').trigger('change');
                }
                $('#' + id).trigger('change.select2');
            });
        }

        // Init on page load (for validation error repopulation)
        $(function() {
            loadWoDetails();
            toggleDiscount();
            recalculate();

            $('#approver1_id, #approver2_id, #approver3_id').on('change', syncApprovers);
        });
    </script>
@endpush
