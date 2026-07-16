@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@section('title', 'Edit Work Order')
@section('page_title', 'Edit Work Order')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $workOrder->wo_number }}</h3>
                </div>

                <form action="{{ route('work_orders.update', $workOrder) }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <strong>Please fix the following errors:</strong>
                                <ul class="mb-0 mt-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="row">
                            {{-- Col 1: Basic Info --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="wo_number">WO Number <span class="text-danger">*</span></label>
                                    <input type="text" name="wo_number" id="wo_number"
                                        class="form-control @error('wo_number') is-invalid @enderror"
                                        value="{{ old('wo_number', $workOrder->wo_number) }}" required>
                                    @error('wo_number')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="account_code">Account Code <span class="text-danger">*</span></label>
                                    <select name="account_code" id="account_code" class="form-control select2" required>
                                        <option value="C"
                                            {{ old('account_code', $workOrder->account_code) === 'C' ? 'selected' : '' }}>
                                            Cash</option>
                                        <option value="INT_WS"
                                            {{ old('account_code', $workOrder->account_code) === 'INT_WS' ? 'selected' : '' }}>
                                            Internal WS</option>
                                        <option value="INT_W3"
                                            {{ old('account_code', $workOrder->account_code) === 'INT_W3' ? 'selected' : '' }}>
                                            Internal W3</option>
                                    </select>
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
                                                {{ old('reference_wo_id', $workOrder->reference_wo_id) == $refWo->id ? 'selected' : '' }}>
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
                                    <select name="customer_id" id="customer_id" class="form-control select2" required>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}"
                                                {{ $workOrder->customer_id == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
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
                                                {{ old('billing_customer_id', $workOrder->billing_customer_id) == $customer->id ? 'selected' : '' }}>
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
                                    <input type="date" name="work_date" id="work_date" class="form-control"
                                        value="{{ old('work_date', $workOrder->work_date->format('Y-m-d')) }}" required>
                                </div>
                                <div class="form-group">
                                    <label for="deadline">Deadline</label>
                                    <input type="date" name="deadline" id="deadline" class="form-control"
                                        value="{{ old('deadline', $workOrder->deadline?->format('Y-m-d')) }}">
                                </div>
                                <div class="form-group">
                                    <label for="sa_sales">SA / Sales</label>
                                    <input type="text" name="sa_sales" id="sa_sales" class="form-control"
                                        placeholder="Nama SA/Sales" value="{{ old('sa_sales', $workOrder->sa_sales) }}">
                                </div>
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes', $workOrder->notes) }}</textarea>
                                </div>
                            </div>

                            {{-- Col 2: Vehicle Info --}}
                            <div class="col-md-4">
                                <h6><i class="fas fa-car"></i> Vehicle Info</h6>
                                <div class="form-group">
                                    <label for="vehicle_plate">Licence Plate</label>
                                    <input type="text" name="vehicle_plate" id="vehicle_plate" class="form-control"
                                        placeholder="e.g., W 1988 MR"
                                        value="{{ old('vehicle_plate', $workOrder->vehicle_plate) }}">
                                </div>
                                <div class="form-group">
                                    <label for="vehicle_merk">Merk (Brand)</label>
                                    <input type="text" name="vehicle_merk" id="vehicle_merk" class="form-control"
                                        placeholder="e.g., Mercedes-Benz"
                                        value="{{ old('vehicle_merk', $workOrder->vehicle_merk) }}">
                                </div>
                                <div class="form-group">
                                    <label for="vehicle_type_year">Type / Year</label>
                                    <input type="text" name="vehicle_type_year" id="vehicle_type_year"
                                        class="form-control" placeholder="e.g., E 350 / 2019"
                                        value="{{ old('vehicle_type_year', $workOrder->vehicle_type_year) }}">
                                </div>
                                <div class="form-group">
                                    <label for="vehicle_km">Mileage KM</label>
                                    <div class="input-group">
                                        <input type="number" name="vehicle_km" id="vehicle_km" class="form-control"
                                            min="0" value="{{ old('vehicle_km', $workOrder->vehicle_km) }}">
                                        <div class="input-group-append"><span class="input-group-text">KM</span></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="chasis_no">Chasis No</label>
                                    <input type="text" name="chasis_no" id="chasis_no" class="form-control"
                                        placeholder="e.g., MHL213085KJ001691"
                                        value="{{ old('chasis_no', $workOrder->chasis_no) }}">
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="save_to_vehicle_master"
                                            name="save_to_vehicle_master" value="1"
                                            {{ old('save_to_vehicle_master', $workOrder->vehicle_id ? '1' : '') ? 'checked' : '' }}>
                                        <label class="custom-control-label font-weight-bold" for="save_to_vehicle_master">
                                            <i class="fas fa-save mr-1 text-success"></i>Save vehicle to master data
                                        </label>
                                    </div>
                                    <small class="text-muted ml-4">
                                        @if ($workOrder->vehicle_id)
                                            <span class="text-success"><i class="fas fa-check-circle mr-1"></i>Already
                                                registered in master data.</span>
                                        @else
                                            Registers this vehicle so it can be selected in future Work Orders.
                                        @endif
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label for="description">Work Description</label>
                                    <textarea name="description" id="description" class="form-control" rows="2">{{ old('description', $workOrder->description) }}</textarea>
                                </div>
                            </div>

                            {{-- Col 3: Panel list + Labor + Tier + Price Summary --}}
                            <div class="col-md-4">
                                <h6><i class="fas fa-tools"></i> Panel yang Dikerjakan</h6>
                                <p class="text-muted small mb-2">Pilih panel, lalu tentukan kisaran harga kendaraan di bawah.</p>

                                <div id="panels-container">
                                    @foreach ($workOrder->panelLabors->where('is_extra', false) as $index => $panel)
                                        <div class="panel-row card mb-2 border-left-success">
                                            <div class="card-body py-2">
                                                <div class="form-group mb-1">
                                                    <label class="mb-1"><strong>Panel</strong></label>
                                                    <select name="panels[{{ $index }}][panel_id]" class="form-control form-control-sm panel-select">
                                                        <option value="">-- Pilih Panel --</option>
                                                        @foreach ($masterPanels as $mp)
                                                            <option value="{{ $mp->id }}"
                                                                data-price="{{ $mp->price }}"
                                                                data-p0300="{{ $mp->price_0_300 }}"
                                                                data-p300500="{{ $mp->price_300_500 }}"
                                                                data-p500800="{{ $mp->price_500_800 }}"
                                                                data-p8002000="{{ $mp->price_800_2000 }}"
                                                                {{ $panel->panel_id == $mp->id ? 'selected' : '' }}>
                                                                {{ $mp->panel_code }} — {{ $mp->description }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="row">
                                                    <div class="col-4">
                                                        <label class="mb-1 small"><strong>Qty</strong></label>
                                                        <input type="number" name="panels[{{ $index }}][qty]" class="form-control form-control-sm panel-qty" step="1" min="1" value="{{ $panel->qty ?? 1 }}">
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="mb-1 small"><strong>Rate</strong></label>
                                                        <input type="number" class="form-control form-control-sm panel-rate" readonly value="{{ $panel->rate ? number_format($panel->rate, 0, '', '') : '' }}">
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="mb-1 small"><strong>Total</strong></label>
                                                        <input type="text" class="form-control form-control-sm panel-total-display" readonly value="{{ $panel->total_price ? number_format($panel->total_price, 0, ',', '.') : '' }}">
                                                    </div>
                                                </div>
                                                <div class="text-right mt-1">
                                                    <button type="button" class="btn btn-danger btn-xs remove-panel"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-success btn-sm mb-3" id="add-panel">
                                    <i class="fas fa-plus"></i> Tambah Panel
                                </button>

                                <h6><i class="fas fa-wrench"></i> Labor</h6>
                                <p class="text-muted small mb-2">Pilih pekerjaan umum yang dikerjakan.</p>

                                <div id="labors-container">
                                    @foreach ($workOrder->generalLabors->where('is_extra', false) as $index => $labor)
                                        <div class="labor-row card mb-2 border-left-info">
                                            <div class="card-body py-2">
                                                <div class="form-group mb-1">
                                                    <label class="mb-1"><strong>Labor</strong></label>
                                                    <select name="labors[{{ $index }}][labor_id]" class="form-control form-control-sm labor-select">
                                                        <option value="">-- Pilih Labor --</option>
                                                        @foreach ($masterLabors as $ml)
                                                            <option value="{{ $ml->id }}"
                                                                data-price="{{ $ml->price }}"
                                                                {{ $labor->labor_id == $ml->id ? 'selected' : '' }}>
                                                                {{ $ml->labor_code }} — {{ $ml->description }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="row">
                                                    <div class="col-4">
                                                        <label class="mb-1 small"><strong>Qty</strong></label>
                                                        <input type="number" name="labors[{{ $index }}][qty]" class="form-control form-control-sm labor-qty" step="1" min="1" value="{{ $labor->qty ?? 1 }}">
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="mb-1 small"><strong>Rate</strong></label>
                                                        <input type="number" class="form-control form-control-sm labor-rate" readonly value="{{ $labor->rate ? number_format($labor->rate, 0, '', '') : '' }}">
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="mb-1 small"><strong>Total</strong></label>
                                                        <input type="text" class="form-control form-control-sm labor-total-display" readonly value="{{ $labor->total_price ? number_format($labor->total_price, 0, ',', '.') : '' }}">
                                                    </div>
                                                </div>
                                                <div class="text-right mt-1">
                                                    <button type="button" class="btn btn-danger btn-xs remove-labor"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-info btn-sm mb-3" id="add-labor">
                                    <i class="fas fa-plus"></i> Tambah Labor
                                </button>

                                <div class="form-group">
                                    <label for="vehicle_price_tier"><i class="fas fa-car-crash mr-1"></i> Kisaran Harga Kendaraan <span class="text-danger">*</span></label>
                                    <select name="vehicle_price_tier" id="vehicle_price_tier"
                                        class="form-control @error('vehicle_price_tier') is-invalid @enderror">
                                        <option value="">-- Pilih Kisaran Harga --</option>
                                        <option value="0_300"   {{ old('vehicle_price_tier', $workOrder->vehicle_price_tier) === '0_300'   ? 'selected' : '' }}>0 – 300 juta</option>
                                        <option value="300_500" {{ old('vehicle_price_tier', $workOrder->vehicle_price_tier) === '300_500' ? 'selected' : '' }}>300 – 500 juta</option>
                                        <option value="500_800" {{ old('vehicle_price_tier', $workOrder->vehicle_price_tier) === '500_800' ? 'selected' : '' }}>500 – 800 juta</option>
                                        <option value="800_2000" {{ old('vehicle_price_tier', $workOrder->vehicle_price_tier) === '800_2000' ? 'selected' : '' }}>800 juta – 2 miliar</option>
                                    </select>
                                    @error('vehicle_price_tier')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="text-muted">Menentukan tarif untuk semua panel di atas.</small>
                                </div>

                                @php
                                    $panelTotal = $workOrder->panelLabors->where('is_extra', false)->sum('total_price');
                                    $baseLaborTotal = $workOrder->generalLabors->where('is_extra', false)->sum('total_price');
                                @endphp
                                <div id="panel_price_summary" style="{{ ($panelTotal + $baseLaborTotal) > 0 ? '' : 'display:none;' }}">
                                    <table class="table table-sm table-bordered mb-0">
                                        <tr>
                                            <td>Total Panel</td>
                                            <td class="text-right"><strong id="display_panel_total">Rp {{ number_format($panelTotal, 0, ',', '.') }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td>Total Labor</td>
                                            <td class="text-right"><strong id="display_labor_total">Rp {{ number_format($baseLaborTotal, 0, ',', '.') }}</strong></td>
                                        </tr>
                                        <tr class="table-success">
                                            <td><strong>Grand Total</strong></td>
                                            <td class="text-right text-success"><strong id="display_grand_total">Rp {{ number_format($workOrder->grand_total, 0, ',', '.') }}</strong></td>
                                        </tr>
                                    </table>
                                </div>

                            </div>
                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update Work Order</button>
                        <a href="{{ route('work_orders.show', $workOrder) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let itemIndex = {{ $workOrder->items->count() }};
        let panelIndex = {{ $workOrder->panelLabors->where('is_extra', false)->count() }};
        let laborIndex = {{ $workOrder->generalLabors->where('is_extra', false)->count() }};

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
            const price = getPriceFromOption(opt, isPanel ? tierKey : null);
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
                laborTotal += updateRowTotal(row, null);
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

        document.addEventListener('DOMContentLoaded', function() {
            const tierEl = document.getElementById('vehicle_price_tier');
            if (tierEl) tierEl.addEventListener('change', function() {
                updatePriceDisplay();
            });
        });

        // Build item options HTML
        @php
            $itemsFormatted = $items->map(function ($item) {
                $stock = (float) $item->stocks->sum('quantity');
                $stockFormatted = $stock == floor($stock) ? number_format($stock, 0, '', '') : rtrim(rtrim(number_format($stock, 2, '.', ''), '0'), '.');
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'stock' => $stockFormatted,
                    'smallest_uom' => $item->smallestUom,
                ];
            });
        @endphp
        const itemsData = @json($itemsFormatted);

        function buildItemOptions(selectedItemId = null) {
            const sid = (selectedItemId !== null && selectedItemId !== undefined) ? String(selectedItemId) : '';
            let html = '<option value="">Select Item (Optional)</option>';
            itemsData.forEach(item => {
                const isSelected = sid !== '' && String(item.id) === sid;
                html +=
                    `<option value="${item.id}" data-stock="${item.stock}" data-uom="${item.smallest_uom?.code || '-'}" data-code="${item.code}" ${isSelected ? 'selected' : ''}>[${item.code}] ${item.name}</option>`;
            });
            return html;
        }

        // Stub - will be overridden once jQuery/Select2 are loaded
        function initItemSelect2() {}

        document.getElementById('add-item').addEventListener('click', function() {
            const container = document.getElementById('items-container');
            const itemOptions = buildItemOptions();

            const newItemRow = document.createElement('div');
            newItemRow.className = 'item-row card mb-2 border-left-primary';
            newItemRow.innerHTML = `
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label><strong>Item</strong></label>
                                <select name="items[${itemIndex}][item_id]" class="form-control item-select">
                                    ${itemOptions}
                                </select>
                                <small class="form-text text-muted item-stock"></small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <label><strong>Qty</strong></label>
                                <div class="input-group">
                                    <input type="number" name="items[${itemIndex}][demand_quantity]" class="form-control qty" step="0.01" min="0.01" placeholder="0.00">
                                    <div class="input-group-append"><span class="input-group-text uom-display">-</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label><strong>Remark</strong></label>
                                <input type="text" name="items[${itemIndex}][remark]" class="form-control" placeholder="e.g., Bundling HRM, WIP 39780">
                            </div>
                        </div>
                        <div class="col-md-2 mt-3">
                            <button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-trash"></i> Remove</button>
                        </div>
                    </div>
                </div>`;
            container.appendChild(newItemRow);
            itemIndex++;
            attachItemListeners();
            initItemSelect2();
        });

        document.getElementById('add-panel').addEventListener('click', function() {
            const container = document.getElementById('panels-container');
            const newPanelRow = document.createElement('div');
            newPanelRow.className = 'panel-row card mb-2 border-left-success';
            newPanelRow.innerHTML = `
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
            container.appendChild(newPanelRow);
            panelIndex++;
            attachPanelListeners();
            if (typeof initPanelSelect2 === 'function') initPanelSelect2();
        });

        document.getElementById('add-labor').addEventListener('click', function() {
            const container = document.getElementById('labors-container');
            const newLaborRow = document.createElement('div');
            newLaborRow.className = 'labor-row card mb-2 border-left-info';
            newLaborRow.innerHTML = `
                <div class="card-body py-2">
                    <div class="form-group mb-1">
                        <label class="mb-1"><strong>Labor</strong></label>
                        <select name="labors[${laborIndex}][labor_id]" class="form-control form-control-sm labor-select">
                            <option value="">-- Pilih Labor --</option>
                            @foreach ($masterLabors as $ml)
                                <option value="{{ $ml->id }}" data-price="{{ $ml->price }}">{{ $ml->labor_code }} — {{ $ml->description }}</option>
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
            container.appendChild(newLaborRow);
            laborIndex++;
            attachLaborListeners();
            if (typeof initLaborSelect2 === 'function') initLaborSelect2();
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

        function attachItemListeners() {
            document.querySelectorAll('.item-select').forEach(select => {
                select.onchange = function() {
                    const row = this.closest('.item-row');
                    const opt = this.options[this.selectedIndex];
                    row.querySelector('.item-stock').textContent = opt.value ?
                        `Stock: ${opt.dataset.stock} ${opt.dataset.uom}` : '';
                    row.querySelector('.uom-display').textContent = opt.dataset.uom || '-';
                };
            });
        }

        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item')) {
                if (document.querySelectorAll('.item-row').length > 1) e.target.closest('.item-row').remove();
            }
            if (e.target.closest('.remove-panel')) {
                if (document.querySelectorAll('.panel-row').length > 1) e.target.closest('.panel-row').remove();
                updatePriceDisplay();
            }
            if (e.target.closest('.remove-labor')) {
                if (document.querySelectorAll('.labor-row').length > 1) e.target.closest('.labor-row').remove();
                updatePriceDisplay();
            }
        });

        attachItemListeners();

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
            document.querySelectorAll('.item-row').forEach(function(row) {
                const sel = row.querySelector('select[name*="[item_id]"]');
                if (!sel || !sel.value) row.remove();
            });
        });

        // ===== REFERENCE WO TOGGLE (INT_W3) =====
        // Moved to scripts section to work with Select2
    </script>
@endsection

@section('scripts')
    <script>
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

        function initItemSelect2() {
            $('.item-select').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    const savedVal = $(this).val();
                    $(this).select2({
                        placeholder: 'Select Item (Optional)',
                        allowClear: true,
                        theme: 'bootstrap4',
                        width: '100%'
                    }).on('change', function() {
                        const row = this.closest('.item-row');
                        const opt = this.options[this.selectedIndex];
                        row.querySelector('.item-stock').textContent = opt.value ?
                            'Stock: ' + (opt.dataset.stock || '0') + ' ' + (opt.dataset.uom || '') : '';
                        row.querySelector('.uom-display').textContent = opt.dataset.uom || '-';
                    });
                    if (savedVal) {
                        $(this).val(savedVal).trigger('change');
                    }
                }
            });
        }

        // Run INSIDE $(document).ready so it fires AFTER the layout's global
        // $('.select2').select2({...}) init — which registers its ready handler first.
        // If initItemSelect2() runs before the layout's ready handler, the layout
        // would re-wrap Select2's own .select2 container spans, corrupting the display.
        $(document).ready(function() {
            toggleRefWo();

            $('#reference_wo_id').select2({
                placeholder: '-- Select Reference WO --',
                allowClear: true,
                theme: 'bootstrap4'
            });

            initItemSelect2();
            initPanelSelect2();
            initLaborSelect2();
        });

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
    </script>
@endsection
