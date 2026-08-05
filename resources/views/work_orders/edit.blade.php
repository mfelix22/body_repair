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
                                        <option value="ASURANSI"
                                            {{ old('account_code', $workOrder->account_code) === 'ASURANSI' ? 'selected' : '' }}>
                                            Asuransi</option>
                                    </select>
                                </div>

                                <div class="form-group" id="insurance_group" style="display:none;">
                                    <label for="insurance_id">Nama Asuransi <span class="text-danger">*</span></label>
                                    <select name="insurance_id" id="insurance_id"
                                        class="form-control select2 @error('insurance_id') is-invalid @enderror">
                                        <option value="">-- Pilih Asuransi --</option>
                                        @foreach ($insurances as $insurance)
                                            <option value="{{ $insurance->id }}"
                                                {{ old('insurance_id', $workOrder->insurance_id) == $insurance->id ? 'selected' : '' }}>
                                                {{ $insurance->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('insurance_id')
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

                            {{-- Col 3: Panel + Tier + Price Summary --}}
                            <div class="col-md-4">
                                <h6><i class="fas fa-wrench"></i> Panel</h6>
                                <p class="text-muted small mb-2">Pilih panel yang dikerjakan.</p>

                                <div id="labors-container">
                                    @foreach ($workOrder->generalLabors->where('is_extra', false) as $index => $labor)
                                        <div class="labor-row card mb-2 border-left-info">
                                            <div class="card-body py-2">
                                                <div class="form-group mb-1">
                                                    <label class="mb-1"><strong>Panel</strong></label>
                                                    <select name="labors[{{ $index }}][labor_id]" class="form-control form-control-sm labor-select">
                                                        <option value="">-- Pilih Panel --</option>
                                                        @foreach ($masterLabors as $ml)
                                                            <option value="{{ $ml->id }}"
                                                                data-price="{{ $ml->price }}"
                                                                data-p0300="{{ $ml->price_0_300 }}"
                                                                data-p300500="{{ $ml->price_300_500 }}"
                                                                data-p500800="{{ $ml->price_500_800 }}"
                                                                data-p8002000="{{ $ml->price_800_2000 }}"
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
                                                        <input type="number" name="labors[{{ $index }}][rate]" class="form-control form-control-sm labor-rate" step="0.01" min="0" value="{{ $labor->rate ? number_format($labor->rate, 0, '', '') : '' }}" data-manual="1">
                                                    </div>
                                                    <div class="col-4">
                                                        <label class="mb-1 small"><strong>Total</strong></label>
                                                        <input type="text" class="form-control form-control-sm labor-total-display" readonly value="{{ $labor->total_price ? number_format($labor->total_price, 0, ',', '.') : '' }}">
                                                    </div>
                                                </div>
                                                <div class="row mt-1">
                                                    <div class="col-6">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input labor-three-coat" id="three_coat_{{ $index }}" name="labors[{{ $index }}][is_three_coat]" value="1" {{ $labor->is_three_coat ? 'checked' : '' }}>
                                                            <label class="custom-control-label small" for="three_coat_{{ $index }}">Three Coat/Candy (+Rp 1.250.000)</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input labor-special-repair" id="special_repair_{{ $index }}" name="labors[{{ $index }}][is_special_repair]" value="1" {{ $labor->is_special_repair ? 'checked' : '' }}>
                                                            <label class="custom-control-label small" for="special_repair_{{ $index }}">Special Repair (x1.5)</label>
                                                        </div>
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
                                    <i class="fas fa-plus"></i> Tambah Panel
                                </button>

                                <div class="form-group" style="display: none;">
                                    <label for="vehicle_price_tier"><i class="fas fa-car-crash mr-1"></i> Kisaran Harga Kendaraan <span class="text-danger">*</span></label>
                                    <select name="vehicle_price_tier" id="vehicle_price_tier"
                                        class="form-control @error('vehicle_price_tier') is-invalid @enderror">
                                        <option value="">-- Pilih Kisaran Harga --</option>

                                    </select>
                                    @error('vehicle_price_tier')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="text-muted">Menentukan tarif untuk semua panel di atas.</small>
                                </div>

                                @php
                                    $baseLaborTotal = $workOrder->generalLabors->where('is_extra', false)->sum('total_price');
                                @endphp
                                <div id="labor_price_summary" style="{{ $baseLaborTotal > 0 ? '' : 'display:none;' }}">
                                    <table class="table table-sm table-bordered mb-0">
                                        <tr>
                                            <td>Total Panel</td>
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

                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6><i class="fas fa-cogs"></i> Pergantian Sparepart</h6>
                                    <p class="text-muted small mb-2">Pilih sparepart yang akan diganti.</p>
                                    <table class="table table-sm table-bordered">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Pergantian Sparepart</th>
                                                <th style="width: 130px;">Harga Satuan</th>
                                                <th style="width: 80px;">Qty</th>
                                                <th style="width: 130px;">Jumlah</th>
                                                <th style="width: 50px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="items-container">
                                            @foreach ($workOrder->items as $index => $woItem)
                                                @php
                                                    $item = $items->firstWhere('id', $woItem->item_id);
                                                    $price = optional($item?->stocks->where('location', 'default')->first())->avg_cost ?? 0;
                                                    $lineTotal = $price * $woItem->demand_quantity;
                                                @endphp
                                                <tr class="item-row">
                                                    <td>
                                                        <select name="items[{{ $index }}][item_id]" class="form-control form-control-sm item-select">
                                                            <option value=""></option>
                                                            @foreach ($items->where('item_type', 'SP') as $item)
                                                                <option value="{{ $item->id }}"
                                                                    data-uom="{{ optional($item->smallestUom)->name ?? '-' }}"
                                                                    data-stock="{{ optional($item->stocks->where('location', 'default')->first())->quantity ?? 0 }}"
                                                                    data-price="{{ optional($item->stocks->where('location', 'default')->first())->avg_cost ?? 0 }}"
                                                                    {{ $woItem->item_id == $item->id ? 'selected' : '' }}>
                                                                    {{ $item->code }} — {{ $item->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td><input type="text" class="form-control form-control-sm item-price text-right" readonly value="{{ $price ? number_format($price, 0, ',', '.') : '0' }}"></td>
                                                    <td><input type="number" name="items[{{ $index }}][demand_quantity]" class="form-control form-control-sm item-qty text-right" step="0.01" min="0.01" value="{{ $woItem->demand_quantity }}"></td>
                                                    <td><input type="text" class="form-control form-control-sm item-total text-right" readonly value="{{ $lineTotal ? number_format($lineTotal, 0, ',', '.') : '0' }}"></td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-danger btn-xs remove-item"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <button type="button" class="btn btn-warning btn-sm mb-3" id="add-sparepart">
                                        <i class="fas fa-plus"></i> Tambah Sparepart
                                    </button>
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
        let laborIndex = {{ $workOrder->generalLabors->where('is_extra', false)->count() }};

        // ===== LABOR PRICE SUMMARY =====
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

        const THREE_COAT_SURCHARGE = 1250000;
        const SPECIAL_REPAIR_MULTIPLIER = 1.5;

        function updateRowTotal(row, tierKey) {
            const select = row.querySelector('.labor-select');
            const qtyInput = row.querySelector('.labor-qty');
            const rateInput = row.querySelector('.labor-rate');
            const totalInput = row.querySelector('.labor-total-display');
            const threeCoatInput = row.querySelector('.labor-three-coat');
            const specialRepairInput = row.querySelector('.labor-special-repair');
            if (!select || !qtyInput) return 0;

            if (!select.value) {
                if (rateInput) rateInput.value = '';
                if (totalInput) totalInput.value = '';
                return 0;
            }

            const opt = select.options[select.selectedIndex];
            let price;
            if (rateInput && rateInput.dataset.manual) {
                price = parseFloat(rateInput.value) || 0;
            } else {
                price = getPriceFromOption(opt, tierKey);
                if (specialRepairInput && specialRepairInput.checked) price *= SPECIAL_REPAIR_MULTIPLIER;
                if (threeCoatInput && threeCoatInput.checked) price += THREE_COAT_SURCHARGE;
                if (rateInput) rateInput.value = price;
            }
            const qty = parseFloat(qtyInput.value) || 0;
            const rowTotal = price * qty;
            if (totalInput) totalInput.value = rowTotal.toLocaleString('id-ID');
            return rowTotal;
        }

        function updatePriceDisplay() {
            const tierKey = getPriceTierKey();
            let laborTotal = 0;
            document.querySelectorAll('.labor-row').forEach(function(row) {
                laborTotal += updateRowTotal(row, tierKey);
            });

            const grandTotal = laborTotal;
            const summaryEl = document.getElementById('labor_price_summary');
            if (laborTotal > 0) {
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

        // Expose so the Select2 init block (later scripts) can call them
        window.updatePriceDisplay = updatePriceDisplay;
        window.updateItemRow = updateItemRow;

        // Build item options HTML
        @php
            $itemsFormatted = $items->where('item_type', 'SP')->map(function ($item) {
                $defaultStock = $item->stocks->where('location', 'default')->first();
                $stock = (float) $item->stocks->sum('quantity');
                $stockFormatted = $stock == floor($stock) ? number_format($stock, 0, '', '') : rtrim(rtrim(number_format($stock, 2, '.', ''), '0'), '.');
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'stock' => $stockFormatted,
                    'price' => (float) (optional($defaultStock)->avg_cost ?? 0),
                    'smallest_uom' => $item->smallestUom,
                ];
            });
        @endphp
        const itemsData = @json($itemsFormatted);

        function buildItemOptions(selectedItemId = null) {
            const sid = (selectedItemId !== null && selectedItemId !== undefined) ? String(selectedItemId) : '';
            let html = '<option value="">Pilih Sparepart</option>';
            itemsData.forEach(item => {
                const isSelected = sid !== '' && String(item.id) === sid;
                html +=
                    `<option value="${item.id}" data-stock="${item.stock}" data-uom="${(item.smallest_uom && item.smallest_uom.code) || '-'}" data-price="${item.price}" data-code="${item.code}" ${isSelected ? 'selected' : ''}>[${item.code}] ${item.name}</option>`;
            });
            return html;
        }

        // Stub - will be overridden once jQuery/Select2 are loaded
        function initItemSelect2() {}

        function updateItemRow(row, selectedOpt = null) {
            const select = row.querySelector('.item-select');
            let price = 0;
            if (selectedOpt && selectedOpt.dataset.price) {
                price = parseFloat(selectedOpt.dataset.price) || 0;
            } else if (select && select.value) {
                const opt = select.querySelector('option[value="' + select.value + '"]');
                if (opt && opt.dataset.price) {
                    price = parseFloat(opt.dataset.price) || 0;
                }
            }
            const qtyInput = row.querySelector('.item-qty');
            const qty = qtyInput ? parseFloat(qtyInput.value) || 0 : 0;
            const priceInput = row.querySelector('.item-price');
            const totalInput = row.querySelector('.item-total');
            if (priceInput) priceInput.value = price ? price.toLocaleString('id-ID') : '0';
            if (totalInput) totalInput.value = (price * qty) ? (price * qty).toLocaleString('id-ID') : '0';
        }

        const addItemBtn = document.getElementById('add-sparepart');
        if (addItemBtn) addItemBtn.addEventListener('click', function() {
            const container = document.getElementById('items-container');
            const itemOptions = buildItemOptions();

            const newItemRow = document.createElement('tr');
            newItemRow.className = 'item-row';
            newItemRow.innerHTML = `
                <td>
                    <select name="items[${itemIndex}][item_id]" class="form-control form-control-sm item-select">
                        ${itemOptions}
                    </select>
                </td>
                <td><input type="text" class="form-control form-control-sm item-price text-right" readonly></td>
                <td><input type="number" name="items[${itemIndex}][demand_quantity]" class="form-control form-control-sm item-qty text-right" step="0.01" min="0.01" value="1"></td>
                <td><input type="text" class="form-control form-control-sm item-total text-right" readonly></td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-xs remove-item"><i class="fas fa-trash"></i></button>
                </td>`;
            container.appendChild(newItemRow);
            itemIndex++;
            initItemSelect2();
            updateItemRow(newItemRow);
        });

        const addLaborBtn = document.getElementById('add-labor');
        if (addLaborBtn) addLaborBtn.addEventListener('click', function() {
            const container = document.getElementById('labors-container');
            const newLaborRow = document.createElement('div');
            newLaborRow.className = 'labor-row card mb-2 border-left-info';
            newLaborRow.innerHTML = `
                <div class="card-body py-2">
                    <div class="form-group mb-1">
                        <label class="mb-1"><strong>Panel</strong></label>
                        <select name="labors[${laborIndex}][labor_id]" class="form-control form-control-sm labor-select">
                            <option value="">-- Pilih Panel --</option>
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
                            <input type="number" name="labors[${laborIndex}][rate]" class="form-control form-control-sm labor-rate" step="0.01" min="0">
                        </div>
                        <div class="col-4">
                            <label class="mb-1 small"><strong>Total</strong></label>
                            <input type="text" class="form-control form-control-sm labor-total-display" readonly>
                        </div>
                    </div>
                    <div class="row mt-1">
                        <div class="col-6">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input labor-three-coat" id="three_coat_${laborIndex}" name="labors[${laborIndex}][is_three_coat]" value="1">
                                <label class="custom-control-label small" for="three_coat_${laborIndex}">Three Coat/Candy (+Rp 1.250.000)</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input labor-special-repair" id="special_repair_${laborIndex}" name="labors[${laborIndex}][is_special_repair]" value="1">
                                <label class="custom-control-label small" for="special_repair_${laborIndex}">Special Repair (x1.5)</label>
                            </div>
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
        function attachLaborListeners() {
            document.querySelectorAll('.labor-select').forEach(select => {
                if (select.dataset.hasListener) return;
                select.dataset.hasListener = '1';
                select.onchange = function() {
                    const row = this.closest('.labor-row');
                    const rateInput = row ? row.querySelector('.labor-rate') : null;
                    if (rateInput) {
                        rateInput.value = '';
                        delete rateInput.dataset.manual;
                    }
                    updatePriceDisplay();
                };
            });
        }

        // Event delegation for qty and manual rate — covers both static and dynamically added rows
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('labor-qty')) {
                updatePriceDisplay();
            }
            if (e.target.classList.contains('labor-rate')) {
                e.target.dataset.manual = '1';
                updatePriceDisplay();
            }
            if (e.target.classList.contains('item-qty')) {
                const row = e.target.closest('.item-row');
                if (row) updateItemRow(row);
            }
        });

        // Event delegation for the Three Coat/Candy & Special Repair checkboxes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('labor-three-coat') || e.target.classList.contains('labor-special-repair')) {
                updatePriceDisplay();
            }
        });

        attachLaborListeners();

        function attachItemListeners() {
            document.querySelectorAll('.item-select').forEach(select => {
                select.onchange = function() {
                    const row = this.closest('.item-row');
                    const opt = this.options[this.selectedIndex];
                    row.querySelector('.item-stock').textContent = opt.value ?
                        'Stock: ' + opt.dataset.stock + ' ' + opt.dataset.uom : '';
                    row.querySelector('.uom-display').textContent = opt.dataset.uom || '-';
                };
            });
        }

        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item')) {
                if (document.querySelectorAll('.item-row').length > 1) e.target.closest('.item-row').remove();
            }
            if (e.target.closest('.remove-labor')) {
                if (document.querySelectorAll('.labor-row').length > 1) e.target.closest('.labor-row').remove();
                updatePriceDisplay();
            }
        });

        attachItemListeners();

        // ===== STRIP BLANK ROWS BEFORE SUBMIT =====
        document.querySelector('form').addEventListener('submit', function() {
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
            if (accountCode === 'ASURANSI') {
                $('#insurance_group').show();
            } else {
                $('#insurance_group').hide();
                $('#insurance_id').val('');
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
                        placeholder: 'Pilih Sparepart',
                        allowClear: true,
                        theme: 'bootstrap4',
                        width: '100%'
                    }).on('change', function() {
                        const row = this.closest('.item-row');
                        if (row && typeof window.updateItemRow === 'function') window.updateItemRow(row);
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
            initLaborSelect2();
        });

        function initLaborSelect2() {
            $('.labor-select').not('.select2-hidden-accessible').each(function() {
                const savedVal = $(this).val();
                $(this).select2({
                    theme: 'bootstrap4',
                    placeholder: '-- Pilih Labor --',
                    allowClear: true,
                    width: '100%'
                }).on('change', function() {
                    if (typeof window.updatePriceDisplay === 'function') window.updatePriceDisplay();
                });
                if (savedVal) {
                    $(this).val(savedVal).trigger('change');
                }
            });
        }
    </script>
@endsection
