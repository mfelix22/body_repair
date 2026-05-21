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
                                                    {{ $refWo->paket_name ?? '-' }}
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

                                {{-- ===== PAKET SELECTION ===== --}}
                                <div class="col-md-4">
                                    <h6><i class="fas fa-tags"></i> Paket HR Auto Studio 2026</h6>
                                    <div class="form-group">
                                        <label for="paket_select">Pilih Paket</label>
                                        <select id="paket_select" class="form-control select2">
                                            <option value="">-- Pilih Paket --</option>
                                            @foreach ($packages as $category => $pkgs)
                                                <optgroup label="{{ $category }}">
                                                    @foreach ($pkgs as $code => $pkg)
                                                        <option value="{{ $code }}"
                                                            data-name="{{ $pkg['name'] }}"
                                                            data-category="{{ $category }}"
                                                            data-sizes='@json($pkg['sizes'])'
                                                            data-bom='@json($pkg['bom'] ?? [])'>
                                                            {{ $code }} &mdash; {{ $pkg['name'] }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group" id="size_field" style="display:none;">
                                        <label for="size_select">Ukuran / Variant</label>
                                        <select id="size_select" class="form-control select2">
                                            <option value="">-- Pilih Ukuran --</option>
                                        </select>
                                    </div>

                                    {{-- Hidden fields stored to DB --}}
                                    <input type="hidden" name="paket_code" id="paket_code"
                                        value="{{ old('paket_code') }}">
                                    <input type="hidden" name="paket_name" id="paket_name"
                                        value="{{ old('paket_name') }}">
                                    <input type="hidden" name="paket_size" id="paket_size"
                                        value="{{ old('paket_size') }}">
                                    <input type="hidden" name="paket_grand_total" id="paket_grand_total"
                                        value="{{ old('paket_grand_total', 0) }}">

                                    {{-- Ala-Carte: add extra services --}}
                                    <div id="alacarte-section" style="display:none;margin-top:10px;">
                                        <label class="font-weight-bold text-primary"><i class="fas fa-plus-circle"></i>
                                            Add
                                            more Ala-Carte services:</label>
                                        <div class="input-group mb-1">
                                            <select id="alacarte-add-select" class="form-control form-control-sm">
                                                <option value="">-- Select service --</option>
                                            </select>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    id="alacarte-add-btn">
                                                    <i class="fas fa-plus"></i> Add
                                                </button>
                                            </div>
                                        </div>
                                        <div id="alacarte-chips" class="mb-1"></div>
                                    </div>

                                    {{-- Price breakdown display --}}
                                    <div id="paket_price_display" style="display:none;">
                                        <table class="table table-sm table-bordered mt-2">
                                            <tr>
                                                <td>Jasa Paket</td>
                                                <td class="text-right"><strong id="display_material">Rp 0</strong></td>
                                            </tr>
                                            <tr>
                                                <td>Labor (Paket)</td>
                                                <td class="text-right"><strong>Rp 75.000</strong></td>
                                            </tr>
                                            <tr id="display_addon_row" style="display:none;">
                                                <td>Addon Labors</td>
                                                <td class="text-right"><strong id="display_addon_labor">Rp 0</strong></td>
                                            </tr>
                                            <tr class="table-success">
                                                <td><strong>Grand Total</strong></td>
                                                <td class="text-right text-success"><strong id="display_grand_total">Rp
                                                        0</strong></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5><i class="fas fa-boxes"></i> Materials Used</h5>
                            <select id="item-options-source" class="d-none">
                                <option value="">Select Item (Optional)</option>
                                @foreach ($items as $item)
                                    @php
                                        $stock = (float) $item->stocks->sum('quantity');
                                        $stockFormatted =
                                            $stock == floor($stock)
                                                ? number_format($stock, 0)
                                                : rtrim(rtrim(number_format($stock, 2), '0'), '.');
                                    @endphp
                                    <option value="{{ $item->id }}" data-stock="{{ $stockFormatted }}"
                                        data-uom="{{ $item->smallestUom->code ?? '-' }}"
                                        data-code="{{ $item->code }}">
                                        [{{ $item->code }}] {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="items-container"></div>
                            <button type="button" class="btn btn-success btn-sm" id="add-item">
                                <i class="fas fa-plus"></i> Add Material
                            </button>

                            <hr>
                            <h5><i class="fas fa-user-tie"></i> Labor</h5>

                            <div id="labors-container">
                                <div class="labor-row card mb-2 border-left-success">
                                    <div class="card-body py-2">
                                        <div class="row align-items-end">
                                            <div class="col-md-6">
                                                <div class="form-group mb-0">
                                                    <label><strong>Labor Description</strong></label>
                                                    <input type="text" name="labors[0][description]"
                                                        class="form-control labor-description"
                                                        placeholder="Enter labor description">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label><strong>Qty</strong></label>
                                                    <input type="number" name="labors[0][qty]"
                                                        class="form-control labor-qty" step="1" min="1"
                                                        value="1">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-0">
                                                    <label><strong>Remarks</strong></label>
                                                    <input type="text" name="labors[0][remarks]" class="form-control"
                                                        placeholder="Optional notes">
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger btn-sm remove-labor">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-success btn-sm" id="add-labor">
                                <i class="fas fa-plus"></i> Add Labor Entry
                            </button>
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
                laborIndex = 1;
            let initItemSelect2Timer = null;
            const LABOR_FIXED = 75000;

            // ===== PAKET SELECTOR =====
            const ALACARTE_CATEGORY = 'PAKET ALA-CARTE';
            const paketSelect = document.getElementById('paket_select');
            const sizeSelect = document.getElementById('size_select');
            const sizeField = document.getElementById('size_field');
            const alacarteSection = document.getElementById('alacarte-section');
            const alacarteAddSel = document.getElementById('alacarte-add-select');
            const alacarteAddBtn = document.getElementById('alacarte-add-btn');
            const alacarteChips = document.getElementById('alacarte-chips');

            // Tracks all selected packages: [{code, name, size, price, bom}]
            let selectedPackages = [];

            console.log('Paket selector initialized. paketSelect:', paketSelect);

            // ---- Rebuild hidden inputs from selectedPackages ----
            function rebuildPrimaryFields() {
                if (!selectedPackages.length) return;
                const codes = selectedPackages.map(p => p.code);
                const names = selectedPackages.map(p => p.name);
                const total = selectedPackages.reduce((s, p) => s + parseFloat(p.price), 0);
                const sizes = selectedPackages.filter(p => p.size && p.size !== 'All').map(p => p.size);
                document.getElementById('paket_code').value = codes.join(' + ');
                document.getElementById('paket_name').value = names.join(' + ');
                document.getElementById('paket_size').value = sizes.length ? sizes.join(', ') : 'All';
                document.getElementById('paket_grand_total').value = total;
                updatePriceDisplay(total);
            }

            // ---- Render chips for extras (index > 0) ----
            function renderChips() {
                alacarteChips.innerHTML = '';
                selectedPackages.slice(1).forEach(pkg => {
                    const el = document.createElement('span');
                    el.className = 'badge badge-primary mr-1 mb-1 p-2';
                    el.style.fontSize = '12px';
                    el.innerHTML =
                        `${pkg.code} &ndash; ${pkg.name}${pkg.size !== 'All' ? ' (' + pkg.size + ')' : ''}
                    &nbsp; Rp ${pkg.price.toLocaleString('id-ID')}
                    <a href="#" class="text-white ml-1" data-rm-pkg="${pkg.code}" style="text-decoration:none;">&times;</a>`;
                    alacarteChips.appendChild(el);
                });
            }

            alacarteChips.addEventListener('click', function(e) {
                const a = e.target.closest('a[data-rm-pkg]');
                if (!a) return;
                e.preventDefault();
                const code = a.dataset.rmPkg;
                removeBomItemsByPkg(code);
                selectedPackages = selectedPackages.filter(p => p.code !== code);
                rebuildPrimaryFields();
                renderChips();
                buildAlacarteExtras();
            });

            // ---- Build the "Add extra" dropdown with unused Ala-Carte options ----
            function buildAlacarteExtras() {
                alacarteAddSel.innerHTML = '<option value="">-- Select service --</option>';
                const used = selectedPackages.map(p => p.code);
                Array.from(paketSelect.options).forEach(opt => {
                    if (opt.dataset.category === ALACARTE_CATEGORY && opt.value && !used.includes(opt.value)) {
                        alacarteAddSel.appendChild(opt.cloneNode(true));
                    }
                });
                // Reset size picker
                const spDiv = document.getElementById('alacarte-size-div');
                if (spDiv) {
                    spDiv.style.display = 'none';
                    spDiv.innerHTML = '';
                }
            }

            // Dynamic size picker for extra services
            alacarteAddSel.addEventListener('change', function() {
                let spDiv = document.getElementById('alacarte-size-div');
                if (!spDiv) {
                    spDiv = document.createElement('div');
                    spDiv.id = 'alacarte-size-div';
                    this.closest('.input-group').after(spDiv);
                }
                const opt = this.options[this.selectedIndex];
                if (!opt || !opt.value) {
                    spDiv.style.display = 'none';
                    spDiv.innerHTML = '';
                    return;
                }
                const sizes = JSON.parse(opt.dataset.sizes || '{}');
                const keys = Object.keys(sizes);
                if (keys.length === 1 && keys[0] === 'All') {
                    spDiv.style.display = 'none';
                    spDiv.innerHTML = '';
                } else {
                    let html =
                        '<select id="alacarte-size-sel" class="form-control form-control-sm mt-1"><option value="">-- Select size --</option>';
                    keys.forEach(s => {
                        html +=
                            `<option value="${s}" data-price="${sizes[s]}">${s} &mdash; Rp ${sizes[s].toLocaleString('id-ID')}</option>`;
                    });
                    html += '</select>';
                    spDiv.innerHTML = html;
                    spDiv.style.display = 'block';
                }
            });

            alacarteAddBtn.addEventListener('click', function() {
                try {
                    const opt = alacarteAddSel.options[alacarteAddSel.selectedIndex];
                    if (!opt || !opt.value) {
                        alert('Please select a service to add.');
                        return;
                    }
                    const sizes = JSON.parse(opt.dataset.sizes || '{}');
                    const keys = Object.keys(sizes);
                    let size = 'All',
                        price = 0;
                    if (keys.length === 1 && keys[0] === 'All') {
                        price = parseFloat(sizes['All']);
                    } else {
                        const sSel = document.getElementById('alacarte-size-sel');
                        if (!sSel || !sSel.value) {
                            alert('Please select a size for ' + opt.dataset.name);
                            return;
                        }
                        size = sSel.value;
                        price = parseInt(sSel.options[sSel.selectedIndex].dataset.price);
                    }

                    let bom = [];
                    try {
                        const bomRaw = opt.dataset.bom;
                        bom = bomRaw ? JSON.parse(bomRaw) : [];
                    } catch (e) {
                        console.error('Error parsing ala-carte BOM:', e);
                    }

                    const pkg = {
                        code: opt.value,
                        name: opt.dataset.name,
                        size,
                        price,
                        bom
                    };
                    selectedPackages.push(pkg);
                    if (bom.length > 0) {
                        appendBomItems(bom, pkg.code);
                    }
                    rebuildPrimaryFields();
                    renderChips();
                    buildAlacarteExtras();
                } catch (e) {
                    console.error('Error in ala-carte add button:', e);
                    alert('Error adding package. Please check browser console.');
                }
            });

            // ---- Main package select change ----
            // Handle the actual package change logic (will be called by event listeners)
            function handlePackageChange() {
                try {
                    const selectedIndex = this.selectedIndex;
                    const option = this.options[selectedIndex];
                    console.log('Handling package change...', option.value, option.dataset.name);
                    console.log('Raw data-bom:', option.dataset.bom);

                    sizeSelect.innerHTML = '<option value="">-- Pilih Ukuran --</option>';
                    // Clear old BOM items
                    selectedPackages.forEach(p => removeBomItemsByPkg(p.code));
                    selectedPackages = [];
                    alacarteChips.innerHTML = '';
                    document.getElementById('paket_code').value = '';
                    document.getElementById('paket_name').value = '';
                    document.getElementById('paket_size').value = '';
                    document.getElementById('paket_grand_total').value = 0;
                    document.getElementById('paket_price_display').style.display = 'none';
                    alacarteSection.style.display = 'none';

                    if (!this.value) {
                        sizeField.style.display = 'none';
                        return;
                    }

                    const sizes = JSON.parse(option.dataset.sizes || '{}');
                    const sizeKeys = Object.keys(sizes);
                    const category = option.dataset.category || '';
                    const bomRaw = option.dataset.bom;
                    let bom = [];

                    try {
                        bom = bomRaw ? JSON.parse(bomRaw) : [];
                        console.log('Parsed BOM:', bom);
                    } catch (e) {
                        console.error('Error parsing BOM data:', e, 'Raw data:', bomRaw);
                        bom = [];
                    }

                    if (sizeKeys.length === 1 && sizeKeys[0] === 'All') {
                        sizeField.style.display = 'none';
                        const pkg = {
                            code: this.value,
                            name: option.dataset.name,
                            size: 'All',
                            price: parseFloat(sizes['All']),
                            bom
                        };
                        selectedPackages = [pkg];
                        console.log('Single-size package selected with', bom.length, 'BOM items');
                        if (bom.length > 0) {
                            console.log('Appending BOM items now...');
                            appendBomItems(bom, pkg.code);
                        } else {
                            console.log('No BOM items to append');
                        }
                        rebuildPrimaryFields();
                    } else {
                        sizeField.style.display = 'block';
                        document.getElementById('paket_code').value = this.value;
                        document.getElementById('paket_name').value = option.dataset.name;
                        sizeKeys.forEach(size => {
                            const opt = document.createElement('option');
                            opt.value = size;
                            opt.textContent = size + ' \u2014 Rp ' + sizes[size].toLocaleString('id-ID');
                            opt.dataset.price = sizes[size];
                            sizeSelect.appendChild(opt);
                        });
                        // Clear items until size picked; add an empty placeholder entry
                        selectedPackages = [{
                            code: this.value,
                            name: option.dataset.name,
                            size: '',
                            price: 0,
                            bom
                        }];
                        console.log('Multi-size package. BOM stored for later:', bom.length, 'items');
                    }

                    if (category === ALACARTE_CATEGORY) {
                        alacarteSection.style.display = 'block';
                        buildAlacarteExtras();
                    }
                } catch (e) {
                    console.error('Error in paket select change:', e);
                }
            }

            function handleSizeChange() {
                try {
                    if (!this.value) return;
                    const price = parseInt(this.options[this.selectedIndex].dataset.price);
                    // Update primary package size & price
                    if (selectedPackages.length > 0) {
                        removeBomItemsByPkg(selectedPackages[0].code);
                        const bom = selectedPackages[0].bom;
                        selectedPackages[0].size = this.value;
                        selectedPackages[0].price = price;
                        if (bom.length > 0) {
                            console.log('Size selected, appending', bom.length, 'BOM items');
                            appendBomItems(bom, selectedPackages[0].code);
                        }
                    }
                    rebuildPrimaryFields();
                } catch (e) {
                    console.error('Error in size select change:', e);
                }
            }

            function updatePriceDisplay(grandTotal) {
                const material = grandTotal - LABOR_FIXED;
                document.getElementById('display_material').textContent = 'Rp ' + material.toLocaleString('id-ID');
                document.getElementById('display_grand_total').textContent = 'Rp ' + (grandTotal + getAddonLaborTotal())
                    .toLocaleString('id-ID');
                document.getElementById('paket_price_display').style.display = 'block';
            }

            function getAddonLaborTotal() {
                let total = 0;
                document.querySelectorAll('.labor-total-price').forEach(function(el) {
                    total += parseFloat(el.value) || 0;
                });
                return total;
            }

            function refreshGrandTotal() {
                const paketTotal = parseFloat(document.getElementById('paket_grand_total').value) || 0;
                if (!paketTotal) return;
                const addonTotal = getAddonLaborTotal();
                const material = paketTotal - LABOR_FIXED;
                document.getElementById('display_material').textContent = 'Rp ' + material.toLocaleString('id-ID');
                const addonRow = document.getElementById('display_addon_row');
                if (addonTotal > 0) {
                    document.getElementById('display_addon_labor').textContent = 'Rp ' + addonTotal.toLocaleString('id-ID');
                    addonRow.style.display = '';
                } else {
                    addonRow.style.display = 'none';
                }
                document.getElementById('display_grand_total').textContent = 'Rp ' + (paketTotal + addonTotal).toLocaleString(
                    'id-ID');
                document.getElementById('paket_price_display').style.display = 'block';
            }

            // Stub - will be overridden once jQuery/Select2 are loaded
            function initItemSelect2() {}

            // ===== ITEM ROWS =====
            function buildItemOptions(selectedItemId = null) {
                const sourceEl = document.getElementById('item-options-source');
                if (!sourceEl) return '<option value="">Select Item (Optional)</option>';

                const sid = (selectedItemId !== null && selectedItemId !== undefined) ? String(selectedItemId) : '';
                let html = '';
                Array.from(sourceEl.options).forEach(function(opt) {
                    const isSelected = sid !== '' && String(opt.value) === sid;
                    const extra = [];
                    if (opt.dataset.stock !== undefined) extra.push('data-stock="' + opt.dataset.stock + '"');
                    if (opt.dataset.uom !== undefined) extra.push('data-uom="' + opt.dataset.uom + '"');
                    if (opt.dataset.code !== undefined) extra.push('data-code="' + opt.dataset.code + '"');
                    html += '<option value="' + opt.value + '" ' + extra.join(' ') + (isSelected ? ' selected' : '') +
                        '>' + opt.text + '</option>';
                });
                return html;
            }

            function buildBomRow(bi, idx, pkgCode) {
                const div = document.createElement('div');
                div.className = 'item-row card mb-2 border-left-primary';
                div.dataset.pkg = pkgCode || '';
                div.innerHTML = `
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label><strong>Item</strong></label>
                                <select name="items[${idx}][item_id]" class="form-control item-select">
                                    ${buildItemOptions(bi.item_id)}
                                </select>
                                <small class="form-text text-muted item-stock">Stock: ${bi.stock} ${bi.uom_code}</small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <label><strong>Demand Qty</strong></label>
                                <div class="input-group">
                                    <input type="number" name="items[${idx}][demand_quantity]" class="form-control qty"
                                        step="0.01" min="0.01" value="${bi.quantity}">
                                    <div class="input-group-append"><span class="input-group-text uom-display">${bi.uom_code}</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label><strong>Remark</strong></label>
                                <input type="text" name="items[${idx}][remark]" class="form-control" placeholder="e.g., Bundling HRM, WIP 39780">
                            </div>
                        </div>
                        <div class="col-md-2 mt-3">
                            <button type="button" class="btn btn-danger btn-sm remove-item"><i class="fas fa-trash"></i> Remove</button>
                        </div>
                    </div>
                </div>`;
                return div;
            }

            /** Append BOM items to the items container, tagged with pkgCode */
            function appendBomItems(bom, pkgCode) {
                try {
                    const container = document.getElementById('items-container');
                    if (!container) {
                        console.error('items-container not found');
                        return;
                    }

                    console.log('Appending BOM items:', bom.length, 'items for package:', pkgCode);

                    // Remove the empty placeholder row if present and there are real BOM items
                    if (bom.length > 0) {
                        const empties = container.querySelectorAll('.item-row[data-pkg=""]');
                        if (empties.length === 1 && !empties[0].querySelector('select.item-select')?.value) {
                            empties[0].remove();
                        }
                    }

                    // Use DocumentFragment for better performance
                    const fragment = document.createDocumentFragment();
                    bom.forEach(bi => {
                        fragment.appendChild(buildBomRow(bi, itemIndex, pkgCode));
                        itemIndex++;
                    });
                    container.appendChild(fragment);
                    attachItemListeners();

                    // Defer select2 initialization to prevent freezing
                    if (initItemSelect2Timer) clearTimeout(initItemSelect2Timer);
                    initItemSelect2Timer = setTimeout(() => {
                        if (typeof initItemSelect2 === 'function') {
                            initItemSelect2();
                        }
                    }, 150);
                } catch (e) {
                    console.error('Error in appendBomItems:', e);
                }
            }

            /** Remove BOM rows that were added for the given pkgCode and renumber */
            function removeBomItemsByPkg(pkgCode) {
                document.querySelectorAll(`.item-row[data-pkg="${pkgCode}"]`).forEach(r => r.remove());
                renumberItemRows();
            }

            function renumberItemRows() {
                document.querySelectorAll('.item-row').forEach((row, i) => {
                    row.querySelectorAll('[name^="items["]').forEach(el => {
                        el.name = el.name.replace(/^items\[\d+\]/, `items[${i}]`);
                    });
                });
                itemIndex = document.querySelectorAll('.item-row').length;
            }

            document.getElementById('add-item').addEventListener('click', function() {
                const container = document.getElementById('items-container');
                const newRow = document.createElement('div');
                newRow.className = 'item-row card mb-2 border-left-primary';
                newRow.dataset.pkg = '';
                newRow.innerHTML = `
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="form-group mb-0">
                                <label><strong>Item</strong></label>
                                <select name="items[${itemIndex}][item_id]" class="form-control item-select">
                                    ${buildItemOptions()}
                                </select>
                                <small class="form-text text-muted item-stock"></small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <label><strong>Demand Qty</strong></label>
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
                container.appendChild(newRow);
                itemIndex++;
                attachItemListeners();
                initItemSelect2();
            });
            document.getElementById('add-labor').addEventListener('click', function() {
                const container = document.getElementById('labors-container');
                const newRow = document.createElement('div');
                newRow.className = 'labor-row card mb-2 border-left-success';
                newRow.innerHTML = `
                <div class="card-body py-2">
                    <div class="row align-items-end">
                        <div class="col-md-6">
                            <div class="form-group mb-0">
                                <label><strong>Labor Description</strong></label>
                                <input type="text" name="labors[${laborIndex}][description]" class="form-control labor-description" placeholder="Enter labor description">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group mb-0">
                                <label><strong>Qty</strong></label>
                                <input type="number" name="labors[${laborIndex}][qty]" class="form-control labor-qty" step="1" min="1" value="1">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group mb-0">
                                <label><strong>Remarks</strong></label>
                                <input type="text" name="labors[${laborIndex}][remarks]" class="form-control" placeholder="Optional notes">
                            </div>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm remove-labor"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>`;
                container.appendChild(newRow);
                laborIndex++;
                attachLaborListeners();
            });

            // ===== ITEM SELECT LISTENERS =====
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

            // ===== REMOVE BUTTONS =====
            document.addEventListener('click', function(e) {
                if (e.target.closest('.remove-item')) {
                    const row = e.target.closest('.item-row');
                    if (row) {
                        row.remove();
                        renumberItemRows();
                    }
                }
                if (e.target.closest('.remove-labor')) {
                    if (document.querySelectorAll('.labor-row').length > 1)
                        e.target.closest('.labor-row').remove();
                }
            });

            attachItemListeners();

            // ===== LABOR FUNCTIONS =====
            function attachLaborListeners() {}

            attachLaborListeners();

            // ===== STRIP BLANK ROWS BEFORE SUBMIT =====
            document.querySelector('form').addEventListener('submit', function() {
                // Remove labor rows with no description entered
                document.querySelectorAll('.labor-row').forEach(function(row) {
                    const desc = row.querySelector('.labor-description');
                    if (!desc || !desc.value.trim()) {
                        row.remove();
                    }
                });
                // Remove item rows with no item selected
                document.querySelectorAll('.item-row').forEach(function(row) {
                    const sel = row.querySelector('select[name*="[item_id]"]');
                    if (!sel || !sel.value) {
                        row.remove();
                    }
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
                $('#paket_select').select2({
                    placeholder: '-- Pilih Paket --',
                    allowClear: true,
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownAutoWidth: true
                }).on('select2:select', function() {
                    console.log('[SELECT2] Package selected:', this.value);
                    handlePackageChange.call(this);
                });

                // Initialize Size select with Select2
                $('#size_select').select2({
                    placeholder: '-- Pilih Ukuran --',
                    allowClear: true,
                    theme: 'bootstrap4',
                    width: '100%',
                    dropdownAutoWidth: true
                }).on('select2:select', function() {
                    console.log('[SELECT2] Size selected:', this.value);
                    handleSizeChange.call(this);
                });

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
            initItemSelect2();
            initLaborSelect2();
        </script>
    @endsection
