@extends('layouts.admin')

@section('title', 'Edit PO: ' . $purchaseOrder->po_number)
@section('page_title', 'Edit Purchase Order: ' . $purchaseOrder->po_number)

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit: {{ $purchaseOrder->po_number }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('purchase_orders.show', $purchaseOrder) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <form action="{{ route('purchase_orders.update', $purchaseOrder) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Note:</strong> PO Type cannot be changed after creation. Current type:
                            <strong>{{ $purchaseOrder->po_type === 'purchase_order' ? 'Purchase Order (PPB) - Goods' : 'Service Order (PPJ) - Services' }}</strong>
                        </div>

                        {{-- Hidden po_type (cannot change) --}}
                        <input type="hidden" name="po_type" value="{{ $purchaseOrder->po_type }}">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="order_date">Order Date <span class="text-danger">*</span></label>
                                    <input type="date" name="order_date" id="order_date"
                                        class="form-control @error('order_date') is-invalid @enderror"
                                        value="{{ old('order_date', $purchaseOrder->order_date?->format('Y-m-d')) }}"
                                        required>
                                    @error('order_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="expected_delivery_date">Expected Delivery Date</label>
                                    <input type="date" name="expected_delivery_date" id="expected_delivery_date"
                                        class="form-control"
                                        value="{{ old('expected_delivery_date', $purchaseOrder->expected_delivery_date?->format('Y-m-d')) }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supplier_id">Supplier</label>
                                    <select name="supplier_id" id="supplier_id" class="form-control">
                                        <option value="">Select Supplier (Optional)</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" data-name="{{ $supplier->name }}"
                                                data-phone="{{ $supplier->phone }}"
                                                data-address="{{ $supplier->address }}"
                                                {{ old('supplier_id', $purchaseOrder->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="supplier_name">Supplier Name <span class="text-danger">*</span></label>
                                    <input type="text" name="supplier_name" id="supplier_name"
                                        class="form-control @error('supplier_name') is-invalid @enderror"
                                        value="{{ old('supplier_name', $purchaseOrder->supplier_name) }}" required>
                                    @error('supplier_name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="supplier_phone">Supplier Phone</label>
                                    <input type="text" name="supplier_phone" id="supplier_phone" class="form-control"
                                        value="{{ old('supplier_phone', $purchaseOrder->supplier_phone) }}">
                                </div>

                                <div class="form-group">
                                    <label for="purchase_request_id">Link to PPB / PPJ</label>
                                    <select name="purchase_request_id" id="purchase_request_id" class="form-control">
                                        <option value="">None</option>
                                        @foreach ($prs as $pr)
                                            <option value="{{ $pr->id }}" data-pr='@json($pr)'
                                                data-type='{{ $pr->type }}'
                                                {{ old('purchase_request_id', $purchaseOrder->purchase_request_id) == $pr->id ? 'selected' : '' }}>
                                                {{ $pr->pr_number }} ({{ $pr->type }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-exclamation-circle text-warning"></i>
                                        Changing PR link will reload items below
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="supplier_address">Supplier Address</label>
                            <textarea name="supplier_address" id="supplier_address" class="form-control" rows="2">{{ old('supplier_address', $purchaseOrder->supplier_address) }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group" id="lokasi_pengerjaan_field"
                                    style="{{ $purchaseOrder->po_type === 'service_order' ? '' : 'display:none;' }}">
                                    <label for="lokasi_pengerjaan">Lokasi Pengerjaan (Work Location)</label>
                                    <input type="text" name="lokasi_pengerjaan" id="lokasi_pengerjaan"
                                        class="form-control" placeholder="e.g., Workshop A"
                                        value="{{ old('lokasi_pengerjaan', $purchaseOrder->lokasi_pengerjaan) }}">
                                </div>
                                <div class="form-group" id="lokasi_pengiriman_field"
                                    style="{{ $purchaseOrder->po_type === 'purchase_order' ? '' : 'display:none;' }}">
                                    <label for="lokasi_pengiriman">Lokasi Pengiriman (Delivery Location)</label>
                                    <input type="text" name="lokasi_pengiriman" id="lokasi_pengiriman"
                                        class="form-control" placeholder="e.g., Warehouse A"
                                        value="{{ old('lokasi_pengiriman', $purchaseOrder->lokasi_pengiriman) }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes', $purchaseOrder->notes) }}</textarea>
                        </div>

                        <hr>
                        <h5>Payment & Work Terms</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="include_ppn" id="include_ppn"
                                            class="custom-control-input"
                                            {{ old('include_ppn', $purchaseOrder->include_ppn) ? 'checked' : '' }}
                                            value="1">
                                        <label class="custom-control-label" for="include_ppn">
                                            Include PPN 11%
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3" id="pph_field"
                                style="{{ $purchaseOrder->po_type === 'service_order' ? '' : 'display:none;' }}">
                                <div class="form-group">
                                    <label for="pph_type">PPH Type</label>
                                    <select name="pph_type" id="pph_type" class="form-control">
                                        <option value="none"
                                            {{ old('pph_type', $purchaseOrder->pph_type) === 'none' ? 'selected' : '' }}>
                                            None</option>
                                        <option value="pph_21"
                                            {{ old('pph_type', $purchaseOrder->pph_type) === 'pph_21' ? 'selected' : '' }}>
                                            PPH 21 (2.5%)</option>
                                        <option value="pph_23"
                                            {{ old('pph_type', $purchaseOrder->pph_type) === 'pph_23' ? 'selected' : '' }}>
                                            PPH 23 (2%)</option>
                                    </select>
                                    <small class="form-text text-muted">Only for Service Orders</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="waktu_pengerjaan">
                                        {{ $purchaseOrder->po_type === 'service_order' ? 'Waktu Pengerjaan' : 'Waktu Pengiriman' }}
                                    </label>
                                    <input type="text" name="waktu_pengerjaan" id="waktu_pengerjaan"
                                        class="form-control" placeholder="e.g., 30 Hari"
                                        value="{{ old('waktu_pengerjaan', $purchaseOrder->waktu_pengerjaan) }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="pembayaran">Pembayaran <span class="text-danger">*</span></label>
                                    <select name="pembayaran" id="pembayaran" class="form-control" required>
                                        <option value="">-- Pilih --</option>
                                        <option value="tunai"
                                            {{ old('pembayaran', $purchaseOrder->pembayaran) === 'tunai' ? 'selected' : '' }}>
                                            Tunai (Cash)</option>
                                        <option value="cicilan"
                                            {{ old('pembayaran', $purchaseOrder->pembayaran) === 'cicilan' ? 'selected' : '' }}>
                                            Cicilan (Installment)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bank_account">Bank Account</label>
                                    <input type="text" name="bank_account" id="bank_account" class="form-control"
                                        placeholder="e.g., Bank BCA - 0882597666"
                                        value="{{ old('bank_account', $purchaseOrder->bank_account) }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jatuh_tempo">Jatuh Tempo (Due Date)</label>
                                    <input type="text" name="jatuh_tempo" id="jatuh_tempo" class="form-control"
                                        placeholder="e.g., 14 Hari"
                                        value="{{ old('jatuh_tempo', $purchaseOrder->jatuh_tempo) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row" id="payment-terms-section"
                            style="{{ old('pembayaran', $purchaseOrder->pembayaran) === 'cicilan' ? '' : 'display:none;' }}">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="payment_terms"><strong>Syarat Pembayaran</strong>
                                        <small class="text-muted">(satu syarat per baris)</small>
                                    </label>
                                    <textarea name="payment_terms" id="payment_terms" class="form-control" rows="5"
                                        placeholder="Uang muka 20%&#10;Pembayaran 25% saat progress 65%&#10;...">{{ old('payment_terms', $purchaseOrder->payment_terms) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label><strong>Lain-lain</strong> <small class="text-muted">(Ongkir, Materai,
                                            etc)</small></label>
                                    <div id="misc-costs-container">
                                        @forelse ($purchaseOrder->miscCosts as $miscIdx => $misc)
                                            <div class="misc-row d-flex mb-1 align-items-center">
                                                <input type="text" name="misc_costs[{{ $miscIdx }}][description]"
                                                    class="form-control mr-2" placeholder="e.g., Ongkos Kirim"
                                                    style="flex:2;"
                                                    value="{{ old('misc_costs.' . $miscIdx . '.description', $misc->description) }}">
                                                <input type="number" name="misc_costs[{{ $miscIdx }}][amount]"
                                                    class="form-control mr-2" placeholder="Rp" step="0.01"
                                                    min="0" style="flex:1;"
                                                    value="{{ old('misc_costs.' . $miscIdx . '.amount', $misc->amount) }}">
                                                <button type="button" class="btn btn-danger btn-sm remove-misc"
                                                    style="white-space:nowrap;">Remove</button>
                                            </div>
                                        @empty
                                            <div class="misc-row d-flex mb-1 align-items-center">
                                                <input type="text" name="misc_costs[0][description]"
                                                    class="form-control mr-2" placeholder="e.g., Ongkos Kirim"
                                                    style="flex:2;" value="{{ old('misc_costs.0.description') }}">
                                                <input type="number" name="misc_costs[0][amount]"
                                                    class="form-control mr-2" placeholder="Rp" step="0.01"
                                                    min="0" style="flex:1;"
                                                    value="{{ old('misc_costs.0.amount', 0) }}">
                                                <button type="button" class="btn btn-danger btn-sm remove-misc"
                                                    style="white-space:nowrap;">Remove</button>
                                            </div>
                                        @endforelse
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
                            @foreach ($purchaseOrder->details as $dIdx => $detail)
                                <div class="item-row border p-3 mb-2">
                                    <div class="row">
                                        @if ($purchaseOrder->po_type === 'purchase_order')
                                            <div class="item-section" style="width:100%;">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <label>Item</label>
                                                        <select name="items[{{ $dIdx }}][item_id]"
                                                            class="form-control item-select" required>
                                                            <option value="">Select Item</option>
                                                            @foreach ($items as $item)
                                                                <option value="{{ $item->id }}"
                                                                    data-uoms='@json($item->itemUoms)'
                                                                    {{ old('items.' . $dIdx . '.item_id', $detail->item_id) == $item->id ? 'selected' : '' }}>
                                                                    {{ $item->name }} ({{ $item->code }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label>UOM</label>
                                                        <select name="items[{{ $dIdx }}][uom_id]"
                                                            class="form-control uom-select" required>
                                                            @if ($detail->uom)
                                                                <option value="{{ $detail->uom_id }}" selected>
                                                                    {{ $detail->uom->name }} ({{ $detail->uom->code }})
                                                                </option>
                                                            @else
                                                                <option value="">Select UOM</option>
                                                            @endif
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label>Quantity</label>
                                                        <input type="number" name="items[{{ $dIdx }}][quantity]"
                                                            class="form-control qty" step="0.01" min="0.01"
                                                            value="{{ old('items.' . $dIdx . '.quantity', $detail->quantity) }}"
                                                            required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label>Unit Price</label>
                                                        <input type="number"
                                                            name="items[{{ $dIdx }}][unit_price]"
                                                            class="form-control price" step="0.01" min="0"
                                                            value="{{ old('items.' . $dIdx . '.unit_price', $detail->unit_price) }}"
                                                            required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label>Total</label>
                                                        <input type="text" class="form-control total" readonly disabled
                                                            value="{{ number_format($detail->total_price, 2, '.', '') }}">
                                                    </div>
                                                    <div class="col-md-1">
                                                        <label>&nbsp;</label><br>
                                                        <button type="button"
                                                            class="btn btn-danger btn-sm remove-item">Remove</button>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="service-section" style="width:100%;">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <label>Service Description <span
                                                                class="text-danger">*</span></label>
                                                        <input type="text"
                                                            name="items[{{ $dIdx }}][service_description]"
                                                            class="form-control service-description"
                                                            value="{{ old('items.' . $dIdx . '.service_description', $detail->service_description) }}"
                                                            required>
                                                    </div>
                                                    <div class="col-md-1">
                                                        <label>Qty</label>
                                                        <input type="number" name="items[{{ $dIdx }}][quantity]"
                                                            class="form-control qty" step="0.01" min="0.01"
                                                            value="{{ old('items.' . $dIdx . '.quantity', $detail->quantity) }}"
                                                            required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label>Unit Price (Rp)</label>
                                                        <input type="number"
                                                            name="items[{{ $dIdx }}][unit_price]"
                                                            class="form-control price" step="0.01" min="0"
                                                            value="{{ old('items.' . $dIdx . '.unit_price', $detail->unit_price) }}"
                                                            required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label>Total</label>
                                                        <input type="text" class="form-control total" readonly disabled
                                                            value="{{ number_format($detail->total_price, 2, '.', '') }}">
                                                    </div>
                                                    <div class="col-md-1">
                                                        <label>&nbsp;</label><br>
                                                        <button type="button"
                                                            class="btn btn-danger btn-sm remove-item">Remove</button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-success btn-sm" id="add-item">Add Item</button>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="{{ route('purchase_orders.show', $purchaseOrder) }}"
                            class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let itemIndex = {{ $purchaseOrder->details->count() }};
        const poType = '{{ $purchaseOrder->po_type }}';

        function getPriceForUom(uoms, uomId) {
            const match = uoms.find(uom => String(uom.uom_id) === String(uomId));
            return match ? parseFloat(match.price || 0) : 0;
        }

        document.getElementById('add-item').addEventListener('click', function() {
            const container = document.getElementById('items-container');
            const newItemRow = document.createElement('div');
            newItemRow.className = 'item-row border p-3 mb-2';

            if (poType === 'service_order') {
                newItemRow.innerHTML = `
                    <div class="row">
                        <div class="service-section" style="width: 100%;">
                            <div class="row">
                                <div class="col-md-4">
                                    <label>Service Description <span class="text-danger">*</span></label>
                                    <input type="text" name="items[${itemIndex}][service_description]"
                                        class="form-control service-description"
                                        placeholder="e.g., Cat Mobil, Poles ABC" required>
                                </div>
                                <div class="col-md-1">
                                    <label>Qty</label>
                                    <input type="number" name="items[${itemIndex}][quantity]"
                                        class="form-control qty" step="0.01" min="0.01" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Unit Price (Rp)</label>
                                    <input type="number" name="items[${itemIndex}][unit_price]"
                                        class="form-control price" step="0.01" min="0" required>
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
                    </div>`;
            } else {
                const itemOptions = Array.from(document.querySelectorAll('.item-select option')).map(opt =>
                    `<option value="${opt.value}" ${opt.dataset.uoms ? `data-uoms='${opt.dataset.uoms}'` : ''}>${opt.text}</option>`
                ).join('');

                newItemRow.innerHTML = `
                    <div class="row">
                        <div class="item-section" style="width: 100%;">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Item</label>
                                    <select name="items[${itemIndex}][item_id]"
                                        class="form-control item-select" required>
                                        ${itemOptions}
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>UOM</label>
                                    <select name="items[${itemIndex}][uom_id]"
                                        class="form-control uom-select" required>
                                        <option value="">Select UOM</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Quantity</label>
                                    <input type="number" name="items[${itemIndex}][quantity]"
                                        class="form-control qty" step="0.01" min="0.01" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Unit Price</label>
                                    <input type="number" name="items[${itemIndex}][unit_price]"
                                        class="form-control price" step="0.01" min="0" required>
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
                    </div>`;
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
                input.removeEventListener('input', handlePriceChange);
                input.addEventListener('input', handlePriceChange);
            });
            document.querySelectorAll('.remove-item').forEach(btn => {
                btn.removeEventListener('click', handleRemoveItem);
                btn.addEventListener('click', handleRemoveItem);
            });
        }

        function handleItemChange() {
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

        function handleUomChange() {
            const row = this.closest('.item-row');
            const itemSelect = row.querySelector('.item-select');
            const priceInput = row.querySelector('.price');
            const totalInput = row.querySelector('.total');
            const option = itemSelect ? itemSelect.options[itemSelect.selectedIndex] : null;
            const uoms = option ? JSON.parse(option.dataset.uoms || '[]') : [];
            const price = getPriceForUom(uoms, this.value);
            priceInput.value = price.toFixed(2);
            const qty = parseFloat(row.querySelector('.qty').value) || 0;
            totalInput.value = (qty * price).toFixed(2);
        }

        function handlePriceChange() {
            const row = this.closest('.item-row');
            const qty = parseFloat(row.querySelector('.qty').value) || 0;
            const price = parseFloat(row.querySelector('.price').value) || 0;
            row.querySelector('.total').value = (qty * price).toFixed(2);
        }

        function handleRemoveItem() {
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

        // PR change: reload items
        const prSelect = document.getElementById('purchase_request_id');
        const itemsData = @json($items);

        prSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (!this.value) return;

            const prData = JSON.parse(selectedOption.dataset.pr || '{}');
            if (!prData.details || prData.details.length === 0) return;

            if (!confirm('Loading items from PR will replace the current item list. Continue?')) return;

            const itemsContainer = document.getElementById('items-container');
            itemsContainer.innerHTML = '';
            itemIndex = 0;

            const isPPJ = prData.type === 'Jasa';

            prData.details.forEach((detail, index) => {
                const newItemRow = document.createElement('div');
                newItemRow.className = 'item-row border p-3 mb-2';

                if (isPPJ) {
                    newItemRow.innerHTML = `
                    <div class="row">
                        <div class="service-section" style="width: 100%;">
                            <div class="row">
                                <div class="col-md-4">
                                    <label>Service Description <span class="text-danger">*</span></label>
                                    <input type="text" name="items[${index}][service_description]"
                                        class="form-control service-description"
                                        value="${detail.service_description || ''}" required>
                                </div>
                                <div class="col-md-1">
                                    <label>Qty</label>
                                    <input type="number" name="items[${index}][quantity]" class="form-control qty"
                                        step="0.01" min="0.01" value="${detail.quantity || 0}" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Unit Price (Rp)</label>
                                    <input type="number" name="items[${index}][unit_price]" class="form-control price"
                                        step="0.01" min="0" value="0" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Total</label>
                                    <input type="text" class="form-control total" readonly disabled value="0.00">
                                </div>
                                <div class="col-md-1">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>`;
                } else {
                    let itemOptions = '<option value="">Select Item</option>';
                    itemsData.forEach(item => {
                        const sel = detail.item_id == item.id ? 'selected' : '';
                        itemOptions +=
                            `<option value="${item.id}" data-uoms='${JSON.stringify(item.item_uoms || [])}' ${sel}>${item.name} (${item.code})</option>`;
                    });

                    newItemRow.innerHTML = `
                    <div class="row">
                        <div class="item-section" style="width: 100%;">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>Item</label>
                                    <select name="items[${index}][item_id]" class="form-control item-select" required>
                                        ${itemOptions}
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>UOM</label>
                                    <select name="items[${index}][uom_id]" class="form-control uom-select" required>
                                        <option value="${detail.uom_id || ''}" selected>${detail.uom ? detail.uom.name + ' (' + detail.uom.code + ')' : ''}</option>
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
                                        step="0.01" min="0" value="0" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Total</label>
                                    <input type="text" class="form-control total" readonly disabled value="0.00">
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

        // Misc costs dynamic rows
        let miscIndex = {{ $purchaseOrder->miscCosts->count() > 0 ? $purchaseOrder->miscCosts->count() : 1 }};

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
                    const row = e.target.closest('.misc-row');
                    row.querySelector('input[name*="description"]').value = '';
                    row.querySelector('input[name*="amount"]').value = 0;
                }
            }
        });

        // Payment terms visibility
        const pembayaranSelect = document.getElementById('pembayaran');
        const paymentTermsSection = document.getElementById('payment-terms-section');

        function togglePaymentTerms() {
            paymentTermsSection.style.display = pembayaranSelect.value === 'cicilan' ? 'block' : 'none';
        }
        pembayaranSelect.addEventListener('change', togglePaymentTerms);

        // Warn on zero prices before submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const zeroPriceInputs = Array.from(document.querySelectorAll('.item-row .price'))
                .filter(input => parseFloat(input.value) === 0);
            if (zeroPriceInputs.length > 0) {
                const proceed = confirm(
                    zeroPriceInputs.length +
                    ' item(s) have a unit price of Rp 0.\n\nAre you sure you want to save the PO with zero prices?'
                );
                if (!proceed) {
                    e.preventDefault();
                    zeroPriceInputs.forEach(input => {
                        input.style.border = '2px solid #dc3545';
                    });
                }
            }
        });
    </script>
@endsection
