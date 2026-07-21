    @extends('layouts.admin')

    @push('styles')
        <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
        <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    @endpush

    @section('title', 'Create Work Order')
    @section('page_title', 'Create Work Order')

    @section('content')
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Work Order Details</h3>
                    </div>

                    <form action="{{ route('work_orders.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="alert alert-info py-2">
                                        <i class="fas fa-info-circle"></i> WO Number will be auto-generated
                                    </div>

                                    <div class="form-group">
                                        <label for="account_code">Account Code <span class="text-danger">*</span></label>
                                        <select name="account_code" id="account_code"
                                            class="form-control select2 @error('account_code') is-invalid @enderror"
                                            required>
                                            <option value="C" {{ old('account_code', 'C') === 'C' ? 'selected' : '' }}>
                                                Cash
                                            </option>
                                            <option value="INT_WS" {{ old('account_code') === 'INT_WS' ? 'selected' : '' }}>
                                                Internal WS</option>
                                            <option value="INT_W3" {{ old('account_code') === 'INT_W3' ? 'selected' : '' }}>
                                                Internal W3</option>
                                            <option value="ASURANSI" {{ old('account_code') === 'ASURANSI' ? 'selected' : '' }}>
                                                Asuransi</option>
                                        </select>
                                        @error('account_code')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Reference WO (visible only when INT_W3) --}}
                                    <div class="form-group" id="reference_wo_group" style="display:none;">
                                        <label for="reference_wo_id">Reference Work Order <span
                                                class="text-danger">*</span></label>
                                        <select name="reference_wo_id" id="reference_wo_id"
                                            class="form-control select2 @error('reference_wo_id') is-invalid @enderror"
                                            style="width:100%">
                                            <option value="">-- Select Reference WO --</option>
                                            @foreach ($completedWos as $refWo)
                                                <option value="{{ $refWo->id }}"
                                                    {{ old('reference_wo_id') == $refWo->id ? 'selected' : '' }}>
                                                    {{ $refWo->wo_number }} — {{ $refWo->customer->name ?? '-' }} —
                                                    {{ $refWo->vehicle_plate ?? '-' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('reference_wo_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="customer_id">Customer <span class="text-danger">*</span></label>
                                        <select name="customer_id" id="customer_id"
                                            class="form-control select2 @error('customer_id') is-invalid @enderror"
                                            required>
                                            <option value="">Select Customer</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}"
                                                    data-vehicles='@json($customer->vehicles)'
                                                    {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('customer_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="billing_customer_id">Ditujukan Kepada
                                            <small class="text-muted">(opsional — nama customer di Invoice)</small>
                                        </label>
                                        <select name="billing_customer_id" id="billing_customer_id"
                                            class="form-control select2 @error('billing_customer_id') is-invalid @enderror">
                                            <option value="">-- Sama dengan Customer --</option>
                                            @foreach ($customers as $customer)
                                                <option value="{{ $customer->id }}"
                                                    {{ old('billing_customer_id') == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('billing_customer_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <label for="work_date">Work Date <span class="text-danger">*</span></label>
                                        <input type="date" name="work_date" id="work_date"
                                            class="form-control @error('work_date') is-invalid @enderror"
                                            value="{{ old('work_date', date('Y-m-d')) }}" required>
                                        @error('work_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label for="deadline">Deadline</label>
                                        <input type="date" name="deadline" id="deadline" class="form-control"
                                            value="{{ old('deadline') }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="sa_sales">SA / Sales</label>
                                        <input type="text" name="sa_sales" id="sa_sales" class="form-control"
                                            placeholder="Nama SA/Sales" value="{{ old('sa_sales') }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="notes">Notes</label>
                                        <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                                    </div>
                                </div>

                                {{-- ===== VEHICLE INFO ===== --}}
                                <div class="col-md-4">
                                    <h6><i class="fas fa-car"></i> Vehicle Info</h6>

                                    {{-- Vehicle picker (filled when customer has registered vehicles) --}}
                                    <div class="form-group" id="vehicle-picker-group" style="display:none;">
                                        <label for="vehicle_id">Registered Vehicle</label>
                                        <select id="vehicle_id" name="vehicle_id" class="form-control select2">
                                            <option value="">-- Enter manually below --</option>
                                        </select>
                                        <small class="form-text text-muted">Select to autofill, or leave blank to enter
                                            manually.</small>
                                    </div>

                                    <div class="form-group">
                                        <label for="vehicle_plate">Licence Plate</label>
                                        <input type="text" name="vehicle_plate" id="vehicle_plate" class="form-control"
                                            placeholder="e.g., W 1988 MR" value="{{ old('vehicle_plate') }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="vehicle_merk">Merk (Brand)</label>
                                        <input type="text" name="vehicle_merk" id="vehicle_merk" class="form-control"
                                            placeholder="e.g., Mercedes-Benz, Toyota" value="{{ old('vehicle_merk') }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="vehicle_type_year">Type / Year</label>
                                        <input type="text" name="vehicle_type_year" id="vehicle_type_year"
                                            class="form-control" placeholder="e.g., E 350 / 2019"
                                            value="{{ old('vehicle_type_year') }}">
                                    </div>
                                    <div class="form-group">
                                        <label for="vehicle_km">Mileage KM</label>
                                        <div class="input-group">
                                            <input type="number" name="vehicle_km" id="vehicle_km" class="form-control"
                                                placeholder="e.g., 28197" min="0"
                                                value="{{ old('vehicle_km') }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text">KM</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="chasis_no">Chasis No</label>
                                        <input type="text" name="chasis_no" id="chasis_no" class="form-control"
                                            placeholder="e.g., MHL213085KJ001691" value="{{ old('chasis_no') }}">
                                    </div>
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input"
                                                id="save_to_vehicle_master" name="save_to_vehicle_master" value="1"
                                                {{ old('save_to_vehicle_master') ? 'checked' : '' }}>
                                            <label class="custom-control-label font-weight-bold"
                                                for="save_to_vehicle_master">
                                                <i class="fas fa-save mr-1 text-success"></i>Save vehicle to master data
                                            </label>
                                        </div>
                                        <small class="text-muted ml-4">Registers this vehicle so it can be selected in
                                            future Work Orders.</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="description">Work Description</label>
                                        <textarea name="description" id="description" class="form-control" rows="2"
                                            placeholder="Brief description of work required">{{ old('description') }}</textarea>
                                    </div>
                                </div>

                                {{-- Col 3: Panel list + Labor + Tier + Price Summary --}}
                                <div class="col-md-4">
                                <h6><i class="fas fa-tools"></i> Panel yang Dikerjakan</h6>
                                <p class="text-muted small mb-2">Pilih panel, lalu tentukan kisaran harga kendaraan di bawah.</p>

                                <div id="panels-container">
                                    <div class="panel-row card mb-2 border-left-success">
                                        <div class="card-body py-2">
                                            <div class="form-group mb-1">
                                                <label class="mb-1"><strong>Panel</strong></label>
                                                <select name="panels[0][panel_id]" class="form-control form-control-sm panel-select">
                                                    <option value="">-- Pilih Panel --</option>
                                                    @foreach ($masterPanels as $mp)
                                                        <option value="{{ $mp->id }}"
                                                            data-price="{{ $mp->price }}"
                                                            data-p0300="{{ $mp->price_0_300 }}"
                                                            data-p300500="{{ $mp->price_300_500 }}"
                                                            data-p500800="{{ $mp->price_500_800 }}"
                                                            data-p8002000="{{ $mp->price_800_2000 }}">
                                                            {{ $mp->panel_code }} — {{ $mp->description }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="row">
                                                <div class="col-4">
                                                    <label class="mb-1 small"><strong>Qty</strong></label>
                                                    <input type="number" name="panels[0][qty]" class="form-control form-control-sm panel-qty" step="1" min="1" value="1">
                                                </div>
                                                <div class="col-4">
                                                    <label class="mb-1 small"><strong>Rate</strong></label>
                                                    <input type="number" class="form-control form-control-sm panel-rate" readonly>
                                                </div>
                                                <div class="col-4">
                                                    <label class="mb-1 small"><strong>Total</strong></label>
                                                    <input type="text" class="form-control form-control-sm panel-total-display" readonly>
                                                </div>
                                            </div>
                                            <div class="text-right mt-1">
                                                <button type="button" class="btn btn-danger btn-xs remove-panel"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-success btn-sm mb-3" id="add-panel">
                                    <i class="fas fa-plus"></i> Tambah Panel
                                </button>

                                <h6><i class="fas fa-wrench"></i> Labor</h6>
                                <p class="text-muted small mb-2">Pilih pekerjaan umum yang dikerjakan.</p>

                                <div id="labors-container">
                                    <div class="labor-row card mb-2 border-left-info">
                                        <div class="card-body py-2">
                                            <div class="form-group mb-1">
                                                <label class="mb-1"><strong>Labor</strong></label>
                                                <select name="labors[0][labor_id]" class="form-control form-control-sm labor-select">
                                                    <option value="">-- Pilih Labor --</option>
                                                    @foreach ($masterLabors as $ml)
                                                        <option value="{{ $ml->id }}"
                                                            data-price="{{ $ml->price }}"
                                                            data-p0300="{{ $ml->price_0_300 }}"
                                                            data-p300500="{{ $ml->price_300_500 }}"
                                                            data-p500800="{{ $ml->price_500_800 }}"
                                                            data-p8002000="{{ $ml->price_800_2000 }}">
                                                            {{ $ml->labor_code }} — {{ $ml->description }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="row">
                                                <div class="col-4">
                                                    <label class="mb-1 small"><strong>Qty</strong></label>
                                                    <input type="number" name="labors[0][qty]" class="form-control form-control-sm labor-qty" step="1" min="1" value="1">
                                                </div>
                                                <div class="col-4">
                                                    <label class="mb-1 small"><strong>Rate</strong></label>
                                                    <input type="number" class="form-control form-control-sm labor-rate" readonly>
                                                </div>
                                                <div class="col-4">
                                                    <label class="mb-1 small"><strong>Total</strong></label>
                                                    <input type="text" class="form-control form-control-sm labor-total-display" readonly>
                                                </div>
                                            </div>
                                            <div class="text-right mt-1">
                                                <button type="button" class="btn btn-danger btn-xs remove-labor"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-info btn-sm mb-3" id="add-labor">
                                    <i class="fas fa-plus"></i> Tambah Labor
                                </button>

                                <div class="form-group">
                                    <label for="vehicle_price_tier"><i class="fas fa-car-crash mr-1"></i> Kisaran Harga Kendaraan <span class="text-danger">*</span></label>
                                    <select name="vehicle_price_tier" id="vehicle_price_tier"
                                        class="form-control @error('vehicle_price_tier') is-invalid @enderror">
                                        <option value="">-- Pilih Kisaran Harga --</option>
                                        <option value="0_300"   {{ old('vehicle_price_tier') === '0_300'   ? 'selected' : '' }}>0 – 300 juta</option>
                                        <option value="300_500" {{ old('vehicle_price_tier') === '300_500' ? 'selected' : '' }}>300 – 500 juta</option>
                                        <option value="500_800" {{ old('vehicle_price_tier') === '500_800' ? 'selected' : '' }}>500 – 800 juta</option>
                                        <option value="800_2000" {{ old('vehicle_price_tier') === '800_2000' ? 'selected' : '' }}>800 juta – 2 miliar</option>
                                    </select>
                                    @error('vehicle_price_tier')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="text-muted">Menentukan tarif untuk semua panel di atas.</small>
                                </div>

                                <div id="panel_price_summary" style="display:none;">
                                    <table class="table table-sm table-bordered mb-0">
                                        <tr>
                                            <td>Total Panel</td>
                                            <td class="text-right"><strong id="display_panel_total">Rp 0</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Total Labor</td>
                                            <td class="text-right"><strong id="display_labor_total">Rp 0</strong></td>
                                        </tr>
                                        <tr class="table-success">
                                            <td><strong>Grand Total</strong></td>
                                            <td class="text-right text-success"><strong id="display_grand_total">Rp 0</strong></td>
                                        </tr>
                                    </table>
                                </div>

                                </div>
                            </div>{{-- end row --}}

                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Create Work Order</button>
                            <a href="{{ route('work_orders.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            let itemIndex = 0,
                panelIndex = 1,
                laborIndex = 1;
            let initItemSelect2Timer = null;

            // ===== PANEL + LABOR PRICE SUMMARY =====
            function getPriceTierKey() {
                const el = document.getElementById('vehicle_price_tier');
                const tier = el ? el.value : '';
                const map = {
                    '0_300':   'p0300',
                    '300_500': 'p300500',
                    '500_800': 'p500800',
                    '800_2000':'p8002000'
                };
                return map[tier] || null;
            }

            function getPriceFromOption(opt, tierKey) {
                if (tierKey && opt.dataset[tierKey] && parseFloat(opt.dataset[tierKey]) > 0) {
                    return parseFloat(opt.dataset[tierKey]);
                }
                return parseFloat(opt.dataset.price) || 0;
            }

            function updateRowTotal(row, tierKey) {
                const isPanel = row.classList.contains('panel-row');
                const select = row.querySelector(isPanel ? '.panel-select' : '.labor-select');
                const qtyInput = row.querySelector(isPanel ? '.panel-qty' : '.labor-qty');
                const rateInput = row.querySelector(isPanel ? '.panel-rate' : '.labor-rate');
                const totalInput = row.querySelector(isPanel ? '.panel-total-display' : '.labor-total-display');
                if (!select || !qtyInput) return 0;

                if (!select.value) {
                    if (rateInput) rateInput.value = '';
                    if (totalInput) totalInput.value = '';
                    return 0;
                }

                const opt = select.options[select.selectedIndex];
                const price = getPriceFromOption(opt, tierKey);
                const qty = parseFloat(qtyInput.value) || 0;
                const rowTotal = price * qty;
                if (rateInput) rateInput.value = price;
                if (totalInput) totalInput.value = rowTotal.toLocaleString('id-ID');
                return rowTotal;
            }

            function updatePriceDisplay() {
                const tierKey = getPriceTierKey();
                let panelTotal = 0;
                document.querySelectorAll('.panel-row').forEach(function(row) {
                    panelTotal += updateRowTotal(row, tierKey);
                });
                let laborTotal = 0;
                document.querySelectorAll('.labor-row').forEach(function(row) {
                    laborTotal += updateRowTotal(row, tierKey);
                });

                const grandTotal = panelTotal + laborTotal;
                const summaryEl = document.getElementById('panel_price_summary');
                if (panelTotal > 0 || laborTotal > 0) {
                    document.getElementById('display_panel_total').textContent = 'Rp ' + panelTotal.toLocaleString('id-ID');
                    document.getElementById('display_labor_total').textContent = 'Rp ' + laborTotal.toLocaleString('id-ID');
                    document.getElementById('display_grand_total').textContent  = 'Rp ' + grandTotal.toLocaleString('id-ID');
                    summaryEl.style.display = 'block';
                } else {
                    summaryEl.style.display = 'none';
                }
            }

            // Recompute rates whenever the tier dropdown changes
            document.addEventListener('DOMContentLoaded', function() {
                const tierEl = document.getElementById('vehicle_price_tier');
                if (tierEl) tierEl.addEventListener('change', function() {
                    updatePriceDisplay();
                });
            });

            // Stub - will be overridden once jQuery/Select2 are loaded
            function initItemSelect2() {}

            // ===== PANEL ROWS =====
            const addPanelBtn = document.getElementById('add-panel');
            if (addPanelBtn) addPanelBtn.addEventListener('click', function() {
                const container = document.getElementById('panels-container');
                const newRow = document.createElement('div');
                newRow.className = 'panel-row card mb-2 border-left-success';
                newRow.innerHTML = `
                <div class="card-body py-2">
                    <div class="form-group mb-1">
                        <label class="mb-1"><strong>Panel</strong></label>
                        <select name="panels[${panelIndex}][panel_id]" class="form-control form-control-sm panel-select">
                            <option value="">-- Pilih Panel --</option>
                            @foreach ($masterPanels as $mp)
                                <option value="{{ $mp->id }}"
                                    data-price="{{ $mp->price }}"
                                    data-p0300="{{ $mp->price_0_300 }}"
                                    data-p300500="{{ $mp->price_300_500 }}"
                                    data-p500800="{{ $mp->price_500_800 }}"
                                    data-p8002000="{{ $mp->price_800_2000 }}">{{ $mp->panel_code }} — {{ $mp->description }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-4">
                            <label class="mb-1 small"><strong>Qty</strong></label>
                            <input type="number" name="panels[${panelIndex}][qty]" class="form-control form-control-sm panel-qty" step="1" min="1" value="1">
                        </div>
                        <div class="col-4">
                            <label class="mb-1 small"><strong>Rate</strong></label>
                            <input type="number" class="form-control form-control-sm panel-rate" readonly>
                        </div>
                        <div class="col-4">
                            <label class="mb-1 small"><strong>Total</strong></label>
                            <input type="text" class="form-control form-control-sm panel-total-display" readonly>
                        </div>
                    </div>
                    <div class="text-right mt-1">
                        <button type="button" class="btn btn-danger btn-xs remove-panel"><i class="fas fa-trash"></i></button>
                    </div>
                </div>`;
                container.appendChild(newRow);
                panelIndex++;
                attachPanelListeners();
                if (typeof initPanelSelect2 === 'function') initPanelSelect2();
            });

            // ===== LABOR ROWS =====
            const addLaborBtn = document.getElementById('add-labor');
            if (addLaborBtn) addLaborBtn.addEventListener('click', function() {
                const container = document.getElementById('labors-container');
                const newRow = document.createElement('div');
                newRow.className = 'labor-row card mb-2 border-left-info';
                newRow.innerHTML = `
                <div class="card-body py-2">
                    <div class="form-group mb-1">
                        <label class="mb-1"><strong>Labor</strong></label>
                        <select name="labors[${laborIndex}][labor_id]" class="form-control form-control-sm labor-select">
                            <option value="">-- Pilih Labor --</option>
                            @foreach ($masterLabors as $ml)
                                <option value="{{ $ml->id }}"
                                    data-price="{{ $ml->price }}"
                                    data-p0300="{{ $ml->price_0_300 }}"
                                    data-p300500="{{ $ml->price_300_500 }}"
                                    data-p500800="{{ $ml->price_500_800 }}"
                                    data-p8002000="{{ $ml->price_800_2000 }}">{{ $ml->labor_code }} — {{ $ml->description }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-4">
                            <label class="mb-1 small"><strong>Qty</strong></label>
                            <input type="number" name="labors[${laborIndex}][qty]" class="form-control form-control-sm labor-qty" step="1" min="1" value="1">
                        </div>
                        <div class="col-4">
                            <label class="mb-1 small"><strong>Rate</strong></label>
                            <input type="number" class="form-control form-control-sm labor-rate" readonly>
                        </div>
                        <div class="col-4">
                            <label class="mb-1 small"><strong>Total</strong></label>
                            <input type="text" class="form-control form-control-sm labor-total-display" readonly>
                        </div>
                    </div>
                    <div class="text-right mt-1">
                        <button type="button" class="btn btn-danger btn-xs remove-labor"><i class="fas fa-trash"></i></button>
                    </div>
                </div>`;
                container.appendChild(newRow);
                laborIndex++;
                attachLaborListeners();
                if (typeof initLaborSelect2 === 'function') initLaborSelect2();
            });

            // ===== REMOVE BUTTONS =====
            document.addEventListener('click', function(e) {
                if (e.target.closest('.remove-panel')) {
                    if (document.querySelectorAll('.panel-row').length > 1)
                        e.target.closest('.panel-row').remove();
                    updatePriceDisplay();
                }
                if (e.target.closest('.remove-labor')) {
                    if (document.querySelectorAll('.labor-row').length > 1)
                        e.target.closest('.labor-row').remove();
                    updatePriceDisplay();
                }
            });

            // ===== ROW LISTENERS =====
            function attachPanelListeners() {
                document.querySelectorAll('.panel-select').forEach(select => {
                    if (select.dataset.hasListener) return;
                    select.dataset.hasListener = '1';
                    select.onchange = function() {
                        updatePriceDisplay();
                    };
                });
            }

            function attachLaborListeners() {
                document.querySelectorAll('.labor-select').forEach(select => {
                    if (select.dataset.hasListener) return;
                    select.dataset.hasListener = '1';
                    select.onchange = function() {
                        updatePriceDisplay();
                    };
                });
            }

            // Event delegation for qty — covers both static and dynamically added rows
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('panel-qty') || e.target.classList.contains('labor-qty')) {
                    updatePriceDisplay();
                }
            });

            attachPanelListeners();
            attachLaborListeners();

            // ===== STRIP BLANK ROWS BEFORE SUBMIT =====
            document.querySelector('form').addEventListener('submit', function() {
                document.querySelectorAll('.panel-row').forEach(function(row) {
                    const sel = row.querySelector('.panel-select');
                    if (!sel || !sel.value) row.remove();
                });
                document.querySelectorAll('.labor-row').forEach(function(row) {
                    const sel = row.querySelector('.labor-select');
                    if (!sel || !sel.value) row.remove();
                });
            });

            // ===== REFERENCE WO TOGGLE (INT_W3) =====
            // Moved to scripts section to work with Select2
        </script>
    @endsection

    @section('scripts')
        <script>
            console.log('jQuery loaded, initializing Select2...');

            // ===== CUSTOMER & VEHICLE PICKER =====
            function updateVehiclePicker() {
                const $customerSelect = $('#customer_id');
                const opt = $customerSelect.find('option:selected')[0];
                const vehicles = opt ? JSON.parse(opt.dataset.vehicles || '[]') : [];
                const $vehicleSelect = $('#vehicle_id');
                const $vehiclePickerGroup = $('#vehicle-picker-group');

                $vehicleSelect.html('<option value="">-- Enter manually below --</option>');

                if (vehicles.length > 0) {
                    vehicles.forEach(v => {
                        const label = [v.plate_number, v.brand, v.model, v.year].filter(Boolean).join(' – ');
                        $vehicleSelect.append(`<option value="${v.id}"
                            data-plate="${v.plate_number || ''}"
                            data-brand="${v.brand || ''}"
                            data-model="${v.model || ''}"
                            data-year="${v.year || ''}"
                            data-chasis="${v.chasis_no || ''}">${label}</option>`);
                    });
                    $vehiclePickerGroup.show();

                    // Re-initialize Select2 on vehicle select after options updated
                    if ($vehicleSelect.hasClass('select2-hidden-accessible')) {
                        $vehicleSelect.select2('destroy');
                    }
                    $vehicleSelect.select2({
                        theme: 'bootstrap4',
                        placeholder: '-- Enter manually below --',
                        allowClear: true,
                        width: '100%'
                    });
                } else {
                    $vehiclePickerGroup.hide();
                }
            }

            // Use jQuery .on() which works with Select2
            $('#customer_id').on('change', function() {
                console.log('[CUSTOMER] Customer changed:', this.value);
                updateVehiclePicker();
            });

            $('#vehicle_id').on('change', function() {
                console.log('[VEHICLE] Vehicle selected:', this.value);
                const opt = $(this).find('option:selected')[0];
                if (opt && opt.value) {
                    $('#vehicle_plate').val(opt.dataset.plate || '');
                    $('#vehicle_merk').val(opt.dataset.brand || '');
                    const typeYear = [opt.dataset.model, opt.dataset.year].filter(Boolean).join(' / ');
                    $('#vehicle_type_year').val(typeYear);
                    $('#chasis_no').val(opt.dataset.chasis || '');
                }
            });

            // ===== REFERENCE WO TOGGLE (INT_W3) =====
            function toggleRefWo() {
                const accountCode = $('#account_code').val();
                if (accountCode === 'INT_W3') {
                    $('#reference_wo_group').show();
                } else {
                    $('#reference_wo_group').hide();
                    $('#reference_wo_id').val('');
                }
            }

            $('#account_code').on('change', function() {
                console.log('[ACCOUNT] Account code changed:', this.value);
                toggleRefWo();
            });

            // Run on page load in case of old() re-population
            // NOTE: must be inside $(document).ready so it fires AFTER the layout's
            // global $('.select2').select2({...}) init — otherwise the layout re-wraps
            // our already-initialized selects and strips the custom event handlers.
            $(document).ready(function() {
                updateVehiclePicker();
                toggleRefWo();
                $('#reference_wo_id').select2({
                    placeholder: '-- Select Reference WO --',
                    allowClear: true,
                    theme: 'bootstrap4'
                });
            });

            function initItemSelect2() {
                // Batch process select2 initialization to prevent freezing
                const selects = $('.item-select').not('.select2-hidden-accessible').toArray();
                let index = 0;

                function processBatch() {
                    const batchSize = 5; // Process 5 at a time
                    const end = Math.min(index + batchSize, selects.length);

                    for (let i = index; i < end; i++) {
                        $(selects[i]).select2({
                            placeholder: 'Select Item (Optional)',
                            allowClear: true,
                            theme: 'bootstrap4',
                            width: '100%',
                            dropdownAutoWidth: true
                        }).on('change', function() {
                            const row = this.closest('.item-row');
                            const opt = this.options[this.selectedIndex];
                            if (row) {
                                const stockEl = row.querySelector('.item-stock');
                                const uomEl = row.querySelector('.uom-display');
                                if (stockEl) {
                                    stockEl.textContent = opt.value ?
                                        'Stock: ' + (opt.dataset.stock || '0') + ' ' + (opt.dataset.uom || '') : '';
                                }
                                if (uomEl) {
                                    uomEl.textContent = opt.dataset.uom || '-';
                                }
                            }
                        });
                    }

                    index = end;
                    if (index < selects.length) {
                        requestAnimationFrame(processBatch);
                    }
                }

                if (selects.length > 0) {
                    requestAnimationFrame(processBatch);
                }
            }

            function initPanelSelect2() {
                $('.panel-select').not('.select2-hidden-accessible').each(function() {
                    const savedVal = $(this).val();
                    $(this).select2({
                        theme: 'bootstrap4',
                        placeholder: '-- Pilih Panel --',
                        allowClear: true,
                        width: '100%'
                    }).on('change', function() {
                        updatePriceDisplay();
                    });
                    if (savedVal) {
                        $(this).val(savedVal).trigger('change');
                    }
                });
            }

            function initLaborSelect2() {
                $('.labor-select').not('.select2-hidden-accessible').each(function() {
                    const savedVal = $(this).val();
                    $(this).select2({
                        theme: 'bootstrap4',
                        placeholder: '-- Pilih Labor --',
                        allowClear: true,
                        width: '100%'
                    }).on('change', function() {
                        updatePriceDisplay();
                    });
                    if (savedVal) {
                        $(this).val(savedVal).trigger('change');
                    }
                });
            }

            $(document).ready(function() {
                initItemSelect2();
                initPanelSelect2();
                initLaborSelect2();
            });
        </script>
    @endsection
