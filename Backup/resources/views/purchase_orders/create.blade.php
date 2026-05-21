@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@section('title', 'Create Purchase Order')
@section('page_title', 'Create Purchase Order')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Purchase Order Details</h3>
                </div>

                <form action="{{ route('purchase_orders.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> PO Number will be auto-generated
                                </div>

                                <div class="form-group">
                                    <label for="po_type">PO Type <span class="text-danger">*</span></label>
                                    <select name="po_type" id="po_type"
                                        class="form-control @error('po_type') is-invalid @enderror" required>
                                        <option value="">Select PO Type</option>
                                        <option value="purchase_order"
                                            @if (old('po_type') === 'purchase_order') selected @elseif ($selectedPr && $selectedPr->type === 'Barang') selected @endif>
                                            Purchase Order (PPB)
                                            - Goods</option>
                                        <option value="service_order"
                                            @if (old('po_type') === 'service_order') selected @elseif ($selectedPr && $selectedPr->type === 'Jasa') selected @endif>
                                            Service Order (PPJ) -
                                            Services</option>
                                    </select>
                                    @error('po_type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="order_date">Order Date <span class="text-danger">*</span></label>
                                    <input type="date" name="order_date" id="order_date"
                                        class="form-control @error('order_date') is-invalid @enderror"
                                        value="{{ old('order_date', date('Y-m-d')) }}" required>
                                    @error('order_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>


                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_id">Supplier</label>
                                    <select name="supplier_id" id="supplier_id" class="form-control">
                                        <option value="">Select Supplier (Optional)</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" data-name="{{ $supplier->name }}"
                                                data-phone="{{ $supplier->phone }}" data-address="{{ $supplier->address }}"
                                                {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Auto-fills supplier details below
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="supplier_name">Supplier Name <span class="text-danger">*</span></label>
                                    <input type="text" name="supplier_name" id="supplier_name"
                                        class="form-control @error('supplier_name') is-invalid @enderror"
                                        value="{{ old('supplier_name') }}" required>
                                    @error('supplier_name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="supplier_phone">Supplier Phone</label>
                                    <input type="text" name="supplier_phone" id="supplier_phone" class="form-control"
                                        value="{{ old('supplier_phone') }}">
                                </div>

                                <div class="form-group">
                                    <label for="purchase_request_id">Link to PPB / PPJ</label>
                                    <select name="purchase_request_id" id="purchase_request_id" class="form-control">
                                        <option value="">Select PPB / PPJ (Optional)</option>
                                        @foreach ($prs as $pr)
                                            <option value="{{ $pr->id }}" data-pr='@json($pr)'
                                                data-type='{{ $pr->type }}'
                                                {{ old('purchase_request_id', $selectedPrId) == $pr->id ? 'selected' : '' }}>
                                                {{ $pr->pr_number }} ({{ $pr->type }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Auto-fills items from PR
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="supplier_address">Supplier Address</label>
                            <textarea name="supplier_address" id="supplier_address" class="form-control" rows="2">{{ old('supplier_address') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group" id="lokasi_pengerjaan_field" style="display: none;">
                                    <label for="lokasi_pengerjaan">Lokasi Pengerjaan (Work Location)</label>
                                    <input type="text" name="lokasi_pengerjaan" id="lokasi_pengerjaan"
                                        class="form-control" placeholder="e.g., Workshop A, Bengkel Utama"
                                        value="{{ old('lokasi_pengerjaan') }}">
                                </div>
                                <div class="form-group" id="lokasi_pengiriman_field" style="display: none;">
                                    <label for="lokasi_pengiriman">Lokasi Pengiriman (Delivery Location)</label>
                                    <input type="text" name="lokasi_pengiriman" id="lokasi_pengiriman"
                                        class="form-control" placeholder="e.g., Warehouse A, Kantor Pusat"
                                        value="{{ old('lokasi_pengiriman') }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                        </div>

                        <hr>
                        <h5>Payment & Work Terms</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="include_ppn" id="include_ppn"
                                            class="custom-control-input" {{ old('include_ppn', true) ? 'checked' : '' }}
                                            value="1">
                                        <label class="custom-control-label" for="include_ppn">
                                            Include PPN 11%
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3" id="pph_field" style="display:none;">
                                <div class="form-group">
                                    <label for="pph_type">PPH Type <span id="pph_required" class="text-danger"
                                            style="display:none;">*</span></label>
                                    <select name="pph_type" id="pph_type" class="form-control">
                                        <option value="none" {{ old('pph_type') === 'none' ? 'selected' : '' }}>None
                                        </option>
                                        <option value="pph_21" {{ old('pph_type') === 'pph_21' ? 'selected' : '' }}>PPH 21
                                            (2.5%)</option>
                                        <option value="pph_23" {{ old('pph_type') === 'pph_23' ? 'selected' : '' }}>PPH 23
                                            (2%)</option>
                                    </select>
                                    <small class="form-text text-muted">Only for Service Orders</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="waktu_pengerjaan" id="waktu_pengerjaan_label">Waktu Pengerjaan (e.g., 30
                                        Hari)</label>
                                    <input type="text" name="waktu_pengerjaan" id="waktu_pengerjaan"
                                        class="form-control" placeholder="e.g., 30 Hari"
                                        value="{{ old('waktu_pengerjaan') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="pembayaran">Pembayaran (Payment) <span
                                            class="text-danger">*</span></label>
                                    <select name="pembayaran" id="pembayaran" class="form-control" required>
                                        <option value="">-- Pilih --</option>
                                        <option value="tunai" {{ old('pembayaran') === 'tunai' ? 'selected' : '' }}>Tunai
                                            (Cash)
                                        </option>
                                        <option value="non_tunai"
                                            {{ old('pembayaran') === 'non_tunai' ? 'selected' : '' }}>Non Tunai
                                        </option>
                                        <option value="cicilan" {{ old('pembayaran') === 'cicilan' ? 'selected' : '' }}>
                                            Cicilan (Installment)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bank_account">Bank Account (for Credit/Non Tunai)</label>
                                    <input type="text" name="bank_account" id="bank_account" class="form-control"
                                        placeholder="e.g., Bank BCA - 0882597666 an. PT Megah Jaya"
                                        value="{{ old('bank_account') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jatuh_tempo">Jatuh Tempo (Due Date)</label>
                                    <input type="text" name="jatuh_tempo" id="jatuh_tempo" class="form-control"
                                        placeholder="e.g., 14 Hari" value="{{ old('jatuh_tempo') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row" id="payment-terms-section" style="display: none;">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="payment_terms"><strong>Syarat Pembayaran</strong>
                                        <small class="text-muted">(satu syarat per baris / one term per line)</small>
                                    </label>
                                    <textarea name="payment_terms" id="payment_terms" class="form-control" rows="5"
                                        placeholder="Contoh:&#10;Uang muka 20%&#10;Pembayaran 25% saat progress pekerjaan 65%&#10;Pembayaran 25% saat progress pekerjaan 85%&#10;Pembayaran 25% saat progress pekerjaan 100%&#10;Pembayaran retensi 5%, 3 bulan setelah pekerjaan selesai">{{ old('payment_terms') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label><strong>Lain-lain</strong> <small class="text-muted">(Ongkir, Materai, etc -
                                            semua akan dijumlahkan di print sebagai "Lain-lain")</small></label>
                                    <div id="misc-costs-container">
                                        <div class="misc-row d-flex mb-1 align-items-center">
                                            <input type="text" name="misc_costs[0][description]"
                                                class="form-control mr-2" placeholder="e.g., Ongkos Kirim"
                                                style="flex:2;" value="{{ old('misc_costs.0.description') }}">
                                            <input type="number" name="misc_costs[0][amount]" class="form-control mr-2"
                                                placeholder="Rp" step="0.01" min="0" style="flex:1;"
                                                value="{{ old('misc_costs.0.amount', 0) }}">
                                            <button type="button" class="btn btn-danger btn-sm remove-misc"
                                                style="white-space:nowrap;">Remove</button>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm mt-1" id="add-misc">
                                        <i class="fas fa-plus"></i> Add Lain-lain
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5>Purchase Order Items</h5>
                        <div id="items-container">
                            <div class="item-row border p-3 mb-2">
                                <div class="row">
                                    <!-- For Purchase Order (Items) -->
                                    <div class="item-section" id="item-section-0" style="display: none; width: 100%;">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <label>Item</label>
                                                <select name="items[0][item_id]" class="form-control item-select"
                                                    required>
                                                    <option value="">Select Item</option>
                                                    @foreach ($items as $item)
                                                        <option value="{{ $item->id }}"
                                                            data-uoms='@json($item->itemUoms)'>
                                                            {{ $item->name }} ({{ $item->code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label>UOM</label>
                                                <select name="items[0][uom_id]" class="form-control uom-select" required>
                                                    <option value="">Select UOM</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label>Quantity</label>
                                                <input type="number" name="items[0][quantity]" class="form-control qty"
                                                    step="0.01" min="0.01" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label>Unit Price</label>
                                                <input type="number" name="items[0][unit_price]"
                                                    class="form-control price" step="0.01" min="0" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label>Total</label>
                                                <input type="text" class="form-control total" readonly disabled>
                                            </div>
                                            <div class="col-md-1">
                                                <label>&nbsp;</label><br>
                                                <button type="button"
                                                    class="btn btn-danger btn-sm remove-item">Remove</button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- For Service Order (Services) -->
                                    <div class="service-section" id="service-section-0"
                                        style="display: none; width: 100%;">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label>Service Description <span class="text-danger">*</span></label>
                                                <input type="text" name="items[0][service_description]"
                                                    class="form-control service-description"
                                                    placeholder="e.g., Cat Mobil, Poles ABC, Ganti Oli" required>
                                            </div>
                                            <div class="col-md-1">
                                                <label>Qty</label>
                                                <input type="number" name="items[0][quantity]" class="form-control qty"
                                                    step="0.01" min="0.01" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label>Unit Price (Rp)</label>
                                                <input type="number" name="items[0][unit_price]"
                                                    class="form-control price" step="0.01" min="0" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label>Total</label>
                                                <input type="text" class="form-control total" readonly disabled>
                                            </div>
                                            <div class="col-md-1">
                                                <label>&nbsp;</label><br>
                                                <button type="button"
                                                    class="btn btn-danger btn-sm remove-item">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-success btn-sm" id="add-item">Add Item</button>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Create PO</button>
                        <a href="{{ route('purchase_orders.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let itemIndex = 1;

        function getPriceForUom(uoms, uomId) {
            const match = uoms.find(uom => String(uom.uom_id) === String(uomId));
            return match ? parseFloat(match.price || 0) : 0;
        }

        // Function to update item sections based on PO type
        function updateItemSections() {
            const poType = document.getElementById('po_type').value;
            const itemSections = document.querySelectorAll('.item-section');
            const serviceSections = document.querySelectorAll('.service-section');

            if (poType === 'service_order') {
                itemSections.forEach(section => section.style.display = 'none');
                serviceSections.forEach(section => section.style.display = 'block');
            } else if (poType === 'purchase_order') {
                itemSections.forEach(section => section.style.display = 'block');
                serviceSections.forEach(section => section.style.display = 'none');
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateItemSections();
        });

        // Update sections when PO type changes
        document.getElementById('po_type').addEventListener('change', function() {
            updateItemSections();
        });

        document.getElementById('add-item').addEventListener('click', function() {
            const container = document.getElementById('items-container');
            const poType = document.getElementById('po_type').value;

            if (!poType) {
                alert('Please select a PO type first.');
                return;
            }

            const newItemRow = document.createElement('div');
            newItemRow.className = 'item-row border p-3 mb-2';

            if (poType === 'service_order') {
                // Service row
                newItemRow.innerHTML = `
                    <div class="row">
                        <div class="service-section" id="service-section-${itemIndex}" style="width: 100%;">
                            <div class="row">
                                <div class="col-md-4">
                                    <label>Service Description <span class="text-danger">*</span></label>
                                    <input type="text" name="items[${itemIndex}][service_description]" class="form-control service-description"
                                        placeholder="e.g., Cat Mobil, Poles ABC, Ganti Oli" required>
                                </div>
                                <div class="col-md-1">
                                    <label>Qty</label>
                                    <input type="number" name="items[${itemIndex}][quantity]" class="form-control qty"
                                        step="0.01" min="0.01" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Unit Price (Rp)</label>
                                    <input type="number" name="items[${itemIndex}][unit_price]" class="form-control price"
                                        step="0.01" min="0" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Total</label>
                                    <input type="text" class="form-control total" readonly disabled>
                                </div>
                                <div class="col-md-1">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                // Item row
                const itemSelect = document.querySelector('.item-select');
                const itemOptions = Array.from(document.querySelectorAll('.item-select option')).map(opt =>
                    `<option value="${opt.value}" ${opt.dataset.uoms ? `data-uoms='${opt.dataset.uoms}'` : ''}>${opt.text}</option>`
                ).join('');

                newItemRow.innerHTML = `
                    <div class="row">
                        <div class="item-section" id="item-section-${itemIndex}" style="width: 100%;">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Item</label>
                                    <select name="items[${itemIndex}][item_id]" class="form-control item-select" required>
                                        ${itemOptions}
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>UOM</label>
                                    <select name="items[${itemIndex}][uom_id]" class="form-control uom-select" required>
                                        <option value="">Select UOM</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Quantity</label>
                                    <input type="number" name="items[${itemIndex}][quantity]" class="form-control qty"
                                        step="0.01" min="0.01" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Unit Price</label>
                                    <input type="number" name="items[${itemIndex}][unit_price]" class="form-control price"
                                        step="0.01" min="0" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Total</label>
                                    <input type="text" class="form-control total" readonly disabled>
                                </div>
                                <div class="col-md-1">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            container.appendChild(newItemRow);
            itemIndex++;
            attachItemEventListeners();
        });

        function attachItemEventListeners() {
            document.querySelectorAll('.item-select').forEach(select => {
                select.removeEventListener('change', handleItemChange);
                select.addEventListener('change', handleItemChange);
            });

            document.querySelectorAll('.uom-select').forEach(select => {
                select.removeEventListener('change', handleUomChange);
                select.addEventListener('change', handleUomChange);
            });

            document.querySelectorAll('.qty, .price').forEach(input => {
                input.removeEventListener('change', handlePriceChange);
                input.addEventListener('change', handlePriceChange);
            });

            document.querySelectorAll('.remove-item').forEach(btn => {
                btn.removeEventListener('click', handleRemoveItem);
                btn.addEventListener('click', handleRemoveItem);
            });
        }

        function handleItemChange(e) {
            const uomSelect = this.closest('.item-row').querySelector('.uom-select');
            const priceInput = this.closest('.item-row').querySelector('.price');
            const totalInput = this.closest('.item-row').querySelector('.total');
            const option = this.options[this.selectedIndex];
            const uoms = JSON.parse(option.dataset.uoms || '[]');

            uomSelect.innerHTML = '<option value="">Select UOM</option>';
            uoms.forEach(uom => {
                const opt = document.createElement('option');
                opt.value = uom.uom_id;
                opt.textContent = uom.uom.name + ' (' + uom.uom.code + ')';
                if (uom.is_default) opt.selected = true;
                uomSelect.appendChild(opt);
            });

            if (uomSelect.value) {
                const price = getPriceForUom(uoms, uomSelect.value);
                priceInput.value = price.toFixed(2);
                const qty = parseFloat(this.closest('.item-row').querySelector('.qty').value) || 0;
                totalInput.value = (qty * price).toFixed(2);
            }
        }

        function handleUomChange(e) {
            const row = this.closest('.item-row');
            const itemSelect = row.querySelector('.item-select');
            const priceInput = row.querySelector('.price');
            const totalInput = row.querySelector('.total');
            const option = itemSelect.options[itemSelect.selectedIndex];
            const uoms = JSON.parse(option.dataset.uoms || '[]');
            const price = getPriceForUom(uoms, this.value);

            priceInput.value = price.toFixed(2);

            const qty = parseFloat(row.querySelector('.qty').value) || 0;
            totalInput.value = (qty * price).toFixed(2);
        }

        function handlePriceChange(e) {
            const row = this.closest('.item-row');
            const qtyInput = row.querySelector('.qty');
            const priceInput = row.querySelector('.price');
            const totalInput = row.querySelector('.total');

            if (qtyInput && priceInput && totalInput) {
                const qty = parseFloat(qtyInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;
                const total = qty * price;
                totalInput.value = total.toFixed(2);
            }
        }

        function handleRemoveItem(e) {
            if (document.querySelectorAll('.item-row').length > 1) {
                this.closest('.item-row').remove();
            } else {
                alert('At least one item is required.');
            }
        }

        attachItemEventListeners();

        // Auto-fill supplier details when supplier is selected
        document.getElementById('supplier_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (this.value) {
                document.getElementById('supplier_name').value = selectedOption.dataset.name || '';
                document.getElementById('supplier_phone').value = selectedOption.dataset.phone || '';
                document.getElementById('supplier_address').value = selectedOption.dataset.address || '';
            }
        });

        const prSelect = document.getElementById('purchase_request_id');
        const addItemButton = document.getElementById('add-item');

        function toggleAddItemButton() {
            if (prSelect.value) {
                addItemButton.classList.add('d-none');
                addItemButton.disabled = true;
            } else {
                addItemButton.classList.remove('d-none');
                addItemButton.disabled = false;
            }
        }

        // Pre-build items data from server (avoids Blade inside JS template literals)
        const itemsData = @json($items);

        // Auto-populate items from PR when PR is selected
        prSelect.addEventListener('change', function() {
            toggleAddItemButton();

            const selectedOption = this.options[this.selectedIndex];
            if (!this.value) return;

            const prData = JSON.parse(selectedOption.dataset.pr || '{}');
            if (!prData.details || prData.details.length === 0) return;

            // Clear existing items
            const itemsContainer = document.getElementById('items-container');
            itemsContainer.innerHTML = '';
            itemIndex = 0;

            // Check if this is a service request (PPJ) or goods request (PPB)
            const isPPJ = prData.type === 'Jasa';

            prData.details.forEach((detail, index) => {
                const newItemRow = document.createElement('div');
                newItemRow.className = 'item-row border p-3 mb-2';

                // Calculate remaining quantity
                const orderedQty = detail.ordered_quantity || 0;
                const remainingQty = (detail.quantity || 0) - orderedQty;

                if (isPPJ) {
                    newItemRow.innerHTML = `
                    <div class="row">
                        <div class="service-section" id="service-section-${index}" style="width: 100%;">
                            <input type="hidden" name="items[${index}][purchase_request_detail_id]" value="${detail.id || ''}">
                            <div class="row">
                                <div class="col-md-4">
                                    <label>Service Description <span class="text-danger">*</span></label>
                                    <input type="text" name="items[${index}][service_description]"
                                        class="form-control service-description"
                                        value="${detail.service_description || ''}" required>
                                    <small class="text-muted">Requested: ${detail.quantity || 0}, Ordered: ${orderedQty}, Remaining: <strong>${remainingQty}</strong></small>
                                </div>
                                <div class="col-md-1">
                                    <label>Qty</label>
                                    <input type="number" name="items[${index}][quantity]" class="form-control qty"
                                        step="0.01" min="0.01" value="${detail.quantity || 0}" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Unit Price (Rp)</label>
                                    <input type="number" name="items[${index}][unit_price]" class="form-control price"
                                        step="0.01" min="0" value="${detail.unit_price || 0}" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Total</label>
                                    <input type="text" class="form-control total" readonly disabled
                                        value="${((detail.quantity || 0) * (detail.unit_price || 0)).toFixed(2)}">
                                </div>
                                <div class="col-md-1">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>`;
                } else {
                    const itemUoms = detail.item && detail.item.item_uoms ? detail.item.item_uoms : [];
                    const price = getPriceForUom(itemUoms, detail.uom_id);

                    let itemOptions = '<option value="">Select Item</option>';
                    itemsData.forEach(item => {
                        const sel = detail.item_id == item.id ? 'selected' : '';
                        itemOptions +=
                            `<option value="${item.id}" data-uoms='${JSON.stringify(item.item_uoms || [])}' ${sel}>${item.name} (${item.code})</option>`;
                    });

                    newItemRow.innerHTML = `
                    <div class="row">
                        <div class="item-section" id="item-section-${index}" style="width: 100%;">
                            <input type="hidden" name="items[${index}][purchase_request_detail_id]" value="${detail.id || ''}">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Item</label>
                                    <select name="items[${index}][item_id]" class="form-control item-select select2-item" required style="width: 100%;">
                                        ${itemOptions}
                                    </select>
                                    <small class="text-muted">Requested: ${detail.quantity || 0}, Ordered: ${orderedQty}, Remaining: <strong>${remainingQty}</strong></small>
                                </div>
                                <div class="col-md-2">
                                    <label>UOM</label>
                                    <select name="items[${index}][uom_id]" class="form-control uom-select" required>
                                        <option value="${detail.uom_id}" selected>${detail.uom ? detail.uom.name + ' (' + detail.uom.code + ')' : ''}</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Quantity</label>
                                    <input type="number" name="items[${index}][quantity]" class="form-control qty"
                                        step="0.01" min="0.01" value="${detail.quantity || 0}" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Unit Price</label>
                                    <input type="number" name="items[${index}][unit_price]" class="form-control price"
                                        step="0.01" min="0" value="${price.toFixed(2)}" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Total</label>
                                    <input type="text" class="form-control total" readonly disabled
                                        value="${((detail.quantity || 0) * price).toFixed(2)}">
                                </div>
                                <div class="col-md-1">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>`;
                }

                itemsContainer.appendChild(newItemRow);
                itemIndex++;
            });

            attachItemEventListeners();
        });

        // Handle po_type change: update PPH visibility, location fields, and filter PRs
        document.getElementById('po_type').addEventListener('change', function() {
            const isServiceOrder = this.value === 'service_order';
            const poType = this.value; // 'purchase_order' or 'service_order'
            const waktuLabel = document.getElementById('waktu_pengerjaan_label');

            // PPB uses delivery wording, PPJ uses work wording
            if (waktuLabel) {
                waktuLabel.textContent = isServiceOrder ? 'Waktu Pengerjaan (e.g., 30 Hari)' :
                    'Waktu Pengiriman (e.g., 30 Hari)';
            }

            // Update PPH field visibility
            document.getElementById('pph_field').style.display = isServiceOrder ? 'block' : 'none';

            // Update location field visibility
            document.getElementById('lokasi_pengerjaan_field').style.display = isServiceOrder ? 'block' : 'none';
            document.getElementById('lokasi_pengiriman_field').style.display = isServiceOrder ? 'none' : 'block';

            // Filter PR options based on PO Type
            const prSelect = document.getElementById('purchase_request_id');
            const prOptions = prSelect.querySelectorAll('option[data-type]');

            // Determine which PR type to show based on PO type
            let requiredPrType = null;
            if (poType === 'purchase_order') {
                requiredPrType = 'Barang'; // PPB shows Barang PRs
            } else if (poType === 'service_order') {
                requiredPrType = 'Jasa'; // PPJ shows Jasa PRs
            }

            // Show/hide PR options based on type match
            prOptions.forEach(option => {
                const prType = option.dataset.type;
                if (requiredPrType && prType === requiredPrType) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });

            // Reset PR selection if current selection doesn't match the new PO type
            const selectedOption = prSelect.options[prSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.type && selectedOption.dataset.type !== requiredPrType) {
                prSelect.value = '';
            }
        });

        // Misc costs (Lain-lain) dynamic rows
        let miscIndex = 1;

        document.getElementById('add-misc').addEventListener('click', function() {
            const container = document.getElementById('misc-costs-container');
            const row = document.createElement('div');
            row.className = 'misc-row d-flex mb-1 align-items-center';
            row.innerHTML = `
                <input type="text" name="misc_costs[${miscIndex}][description]" class="form-control mr-2"
                    placeholder="e.g., Materai" style="flex:2;">
                <input type="number" name="misc_costs[${miscIndex}][amount]" class="form-control mr-2"
                    placeholder="Rp" step="0.01" min="0" style="flex:1;">
                <button type="button" class="btn btn-danger btn-sm remove-misc" style="white-space:nowrap;">Remove</button>
            `;
            container.appendChild(row);
            miscIndex++;
        });

        document.getElementById('misc-costs-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-misc')) {
                if (document.querySelectorAll('.misc-row').length > 1) {
                    e.target.closest('.misc-row').remove();
                } else {
                    // Just clear the first row instead of removing
                    const row = e.target.closest('.misc-row');
                    row.querySelector('input[name*="description"]').value = '';
                    row.querySelector('input[name*="amount"]').value = 0;
                }
            }
        });

        // Run on page load
        toggleAddItemButton();
        if (prSelect.value) {
            prSelect.dispatchEvent(new Event('change'));
        }
        document.getElementById('po_type').dispatchEvent(new Event('change'));

        // Warn if any unit price is still 0 before submitting
        document.querySelector('form').addEventListener('submit', function(e) {
            const zeroPriceInputs = Array.from(document.querySelectorAll('.item-row .price'))
                .filter(input => parseFloat(input.value) === 0);
            if (zeroPriceInputs.length > 0) {
                const proceed = confirm(
                    zeroPriceInputs.length +
                    ' item(s) have a unit price of Rp 0.\n\nAre you sure you want to submit the PO with zero prices?'
                );
                if (!proceed) {
                    e.preventDefault();
                    zeroPriceInputs.forEach(input => {
                        input.style.border = '2px solid #dc3545';
                        input.focus();
                    });
                }
            }
        });

        // Payment terms visibility based on payment method
        const pembayaranSelect = document.getElementById('pembayaran');
        const paymentTermsSection = document.getElementById('payment-terms-section');

        function togglePaymentTerms() {
            if (pembayaranSelect.value === 'cicilan') {
                paymentTermsSection.style.display = 'block';
            } else {
                paymentTermsSection.style.display = 'none';
            }
        }

        pembayaranSelect.addEventListener('change', togglePaymentTerms);

        // Run on page load for old() values
        togglePaymentTerms();
    </script>
@endsection

@section('scripts')
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        function initItemSelect2() {
            $('.select2-item').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        theme: 'bootstrap4',
                        placeholder: 'Select Item',
                        allowClear: true,
                        width: '100%'
                    });
                }
            });
        }

        $(document).ready(function() {
            // Initialize select2 on existing items
            initItemSelect2();

            // Re-initialize after new rows are added
            const originalAddItem = document.getElementById('add-item').onclick;
            document.getElementById('add-item').addEventListener('click', function() {
                setTimeout(initItemSelect2, 100);
            });
        });
    </script>
@endsection
