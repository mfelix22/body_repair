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
                                                data-contact_person="{{ $supplier->contact_person }}"
                                                data-bank_name="{{ $supplier->bank_name }}"
                                                data-bank_account_no="{{ $supplier->bank_account_no }}"
                                                data-bank_account_name="{{ $supplier->bank_account_name }}"
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
                                    <label for="supplier_contact_person">Contact Person</label>
                                    <input type="text" name="supplier_contact_person" id="supplier_contact_person"
                                        class="form-control"
                                        value="{{ old('supplier_contact_person', $purchaseOrder->supplier_contact_person) }}"
                                        placeholder="e.g., Budi Santoso">
                                </div>

                                <div class="form-group">
                                    <label for="purchase_request_id">Link to PPB / PPJ</label>
                                    <select name="purchase_request_id" id="purchase_request_id" class="form-control">
                                        <option value="">None</option>
                                        @foreach ($prs as $pr)
                                            <option value="{{ $pr->id }}"
                                                data-type="{{ $pr->type }}"
                                                data-ack="{{ $pr->require_acknowledgement ? '1' : '0' }}"
                                                {{ old('purchase_request_id', $purchaseOrder->purchase_request_id) == $pr->id ? 'selected' : '' }}>
                                                {{ $pr->pr_number }} ({{ $pr->type }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-exclamation-circle text-warning"></i>
                                        Changing PR link will reload items below
                                    </small>
                                    @if (auth()->user()->hasAnyRole(['purchasing', 'admin', 'super_admin']))
                                        <div id="pr-ack-notice" class="alert alert-warning py-2 mt-1 mb-0"
                                            style="display:none;">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            <strong>Perhatian:</strong> PPB/PPJ ini memerlukan <strong>Acknowledgement dari
                                                Direksi / GM</strong> sebelum PO diproses.
                                        </div>
                                    @endif
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
                        <fieldset {{ $purchaseOrder->status === 'completed' ? 'disabled' : '' }}>
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
                            </div>

                            {{-- Payment Method (Step 1) --}}
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="payment_method">Metode Pembayaran <span
                                                class="text-danger">*</span></label>
                                        <select name="payment_method" id="payment_method"
                                            class="form-control @error('payment_method') is-invalid @enderror" required>
                                            <option value="">-- Pilih Metode --</option>
                                            <option value="credit"
                                                {{ old('payment_method', $purchaseOrder->payment_method) === 'credit' ? 'selected' : '' }}>
                                                Credit</option>
                                            <option value="cbd"
                                                {{ old('payment_method', $purchaseOrder->payment_method) === 'cbd' ? 'selected' : '' }}>
                                                CBD (Cash Before Delivery)</option>
                                            <option value="dp"
                                                {{ old('payment_method', $purchaseOrder->payment_method) === 'dp' ? 'selected' : '' }}>
                                                DP (Down Payment)</option>
                                        </select>
                                        <small class="form-text text-muted">Credit → selalu Non-Tunai. CBD / DP → pilih
                                            Tunai
                                            atau Non-Tunai.</small>
                                        @error('payment_method')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Step 2: Tunai / Non-Tunai --}}
                                @php
                                    $existingMethod = old('payment_method', $purchaseOrder->payment_method);
                                    $showPembayaran = in_array($existingMethod, ['cbd', 'dp']);
                                    $existingPembayaran = old('pembayaran', $purchaseOrder->pembayaran ?? 'non_tunai');
                                    // Normalise legacy values
                                    if (!in_array($existingPembayaran, ['tunai', 'non_tunai'])) {
                                        $existingPembayaran = 'non_tunai';
                                    }
                                    if ($existingMethod === 'credit') {
                                        $existingPembayaran = 'non_tunai';
                                    }
                                    $showBank = $existingPembayaran === 'non_tunai';
                                @endphp
                                <div class="col-md-4" id="pembayaran_field"
                                    style="{{ $showPembayaran ? '' : 'display:none;' }}">
                                    <div class="form-group">
                                        <label for="pembayaran">Pembayaran <span class="text-danger">*</span></label>
                                        <select name="pembayaran" id="pembayaran"
                                            class="form-control @error('pembayaran') is-invalid @enderror" required>
                                            <option value="">-- Pilih --</option>
                                            <option value="tunai"
                                                {{ $existingPembayaran === 'tunai' ? 'selected' : '' }}>
                                                Tunai (Cash)
                                            </option>
                                            <option value="non_tunai"
                                                {{ $existingPembayaran === 'non_tunai' ? 'selected' : '' }}>Non Tunai
                                                (Transfer)</option>
                                        </select>
                                        @error('pembayaran')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="jatuh_tempo">Jatuh Tempo (Due Date)</label>
                                        <input type="text" name="jatuh_tempo" id="jatuh_tempo" class="form-control"
                                            placeholder="e.g., 14 Hari"
                                            value="{{ old('jatuh_tempo', $purchaseOrder->jatuh_tempo) }}">
                                    </div>
                                </div>
                            </div>

                            {{-- Bank account: shown only when Non-Tunai --}}
                            <div class="row" id="bank_account_row"
                                style="{{ $showBank ? 'display:flex;' : 'display:none;' }}">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="bank_account">Rekening Bank <span class="text-danger">*</span></label>
                                        <input type="text" name="bank_account" id="bank_account" class="form-control"
                                            placeholder="e.g., Bank BCA - 0882597666 a.n. PT Megah Jaya"
                                            value="{{ old('bank_account', $purchaseOrder->bank_account) }}">
                                    </div>
                                </div>
                            </div>

                            {{-- Syarat Pembayaran: hidden but kept in DOM --}}
                            <div style="display:none;">
                                <textarea name="payment_terms" id="payment_terms">{{ old('payment_terms', $purchaseOrder->payment_terms) }}</textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label><strong>Lain-lain</strong> <small class="text-muted">(Ongkir, Materai,
                                                etc)</small></label>
                                        <div id="misc-costs-container">
                                            @forelse ($purchaseOrder->miscCosts as $miscIdx => $misc)
                                                <div class="misc-row d-flex mb-1 align-items-center">
                                                    <input type="text"
                                                        name="misc_costs[{{ $miscIdx }}][description]"
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
                                        <button type="button" class="btn btn-outline-secondary btn-sm mt-1"
                                            id="add-misc">
                                            <i class="fas fa-plus"></i> Add Lain-lain
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </fieldset>{{-- /payment-work-terms fieldset --}}

                        <hr>
                        <h5>Purchase Order Items</h5>
                        @if ($purchaseOrder->status === 'completed')
                            <div class="alert alert-warning py-2 mb-2">
                                <i class="fas fa-lock mr-1"></i>
                                <strong>PO sudah Completed.</strong> Item, harga, PPN, dan PPH tidak dapat diubah.
                            </div>
                        @endif
                        <fieldset {{ $purchaseOrder->status === 'completed' ? 'disabled' : '' }}>
                            <div id="items-container">
                                @foreach ($purchaseOrder->details as $dIdx => $detail)
                                    <div class="item-row border p-3 mb-2">
                                        <input type="hidden"
                                            name="items[{{ $dIdx }}][purchase_request_detail_id]"
                                            value="{{ $detail->purchase_request_detail_id }}">
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
                                                                        data-smallest-uom="{{ $item->smallestUom->code ?? '' }}"
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
                                                                        {{ $detail->uom->name }}
                                                                        ({{ $detail->uom->code }})
                                                                    </option>
                                                                @else
                                                                    <option value="">Select UOM</option>
                                                                @endif
                                                            </select>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label>Isi/Kemasan</label>
                                                            <input type="number"
                                                                name="items[{{ $dIdx }}][conversion_to_smallest]"
                                                                class="form-control conv-input" step="0.000001"
                                                                min="0.000001"
                                                                value="{{ old('items.' . $dIdx . '.conversion_to_smallest', $detail->conversion_to_smallest) }}"
                                                                data-item-master-conversion="{{ $detail->item?->itemUoms?->firstWhere('uom_id', $detail->uom_id)?->conversion_to_smallest ?? '' }}"
                                                                required>
                                                            <small class="text-muted conv-hint">
                                                                1 {{ $detail->uom->code ?? 'UOM' }} = ?
                                                                {{ $detail->item?->smallestUom?->code ?? 'unit' }}
                                                            </small>
                                                            <small class="conv-warning"
                                                                style="display:none;color:#e65c00;font-weight:600;"></small>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label>Qty</label>
                                                            <input type="number"
                                                                name="items[{{ $dIdx }}][quantity]"
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
                                                        <div class="col-md-1">
                                                            <label>Total</label>
                                                            <input type="text" class="form-control total" readonly
                                                                disabled
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
                                                            <input type="number"
                                                                name="items[{{ $dIdx }}][quantity]"
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
                                                            <input type="text" class="form-control total" readonly
                                                                disabled
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
                            <button type="button" class="btn btn-info btn-sm ml-2" id="add-from-pr"
                                style="{{ $purchaseOrder->purchase_request_id ? '' : 'display:none;' }}">
                                <i class="fas fa-list"></i> Add Item from PR
                            </button>
                        </fieldset>
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

    {{-- Modal: pick items from linked PR --}}
    <div class="modal fade" id="prItemsModal" tabindex="-1" role="dialog" aria-labelledby="prItemsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="prItemsModalLabel">Add Items from PR</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2">Select items to append to the current PO. Already-added items are greyed out.</p>
                    <div id="pr-items-list"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
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

        function getConversionForUom(uoms, uomId) {
            const match = uoms.find(uom => String(uom.uom_id) === String(uomId));
            return match ? parseFloat(match.conversion_to_smallest || 1) : 1;
        }

        function getUomCodeFromList(uoms, uomId) {
            const match = uoms.find(uom => String(uom.uom_id) === String(uomId));
            return match ? match.uom.code : '';
        }

        const addItemBtn = document.getElementById('add-item');
        if (addItemBtn) addItemBtn.addEventListener('click', function() {
            const container = document.getElementById('items-container');
            if (!container) return;
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
                    `<option value="${opt.value}" ${opt.dataset.uoms ? `data-uoms='${opt.dataset.uoms}'` : ''} ${opt.dataset.smallestUom ? `data-smallest-uom="${opt.dataset.smallestUom}"` : ''}>${opt.text}</option>`
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
                                    <label>Isi/Kemasan</label>
                                    <input type="number" name="items[${itemIndex}][conversion_to_smallest]"
                                        class="form-control conv-input" step="0.000001" min="0.000001"
                                        placeholder="e.g. 100" required data-item-master-conversion="">
                                    <small class="text-muted conv-hint">1 UOM = ? unit terkecil</small>
                                    <small class="conv-warning" style="display:none;color:#e65c00;font-weight:600;"></small>
                                </div>
                                <div class="col-md-1">
                                    <label>Qty</label>
                                    <input type="number" name="items[${itemIndex}][quantity]"
                                        class="form-control qty" step="0.01" min="0.01" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Unit Price</label>
                                    <input type="number" name="items[${itemIndex}][unit_price]"
                                        class="form-control price" step="0.01" min="0" required>
                                </div>
                                <div class="col-md-1">
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

            if (container) container.appendChild(newItemRow);
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

            document.querySelectorAll('.conv-input').forEach(input => {
                input.removeEventListener('input', handleConversionInput);
                input.addEventListener('input', handleConversionInput);
            });
        }

        function handleItemChange() {
            const row = this.closest('.item-row');
            const uomSelect = row.querySelector('.uom-select');
            const previousUomId = uomSelect.value;
            const priceInput = row.querySelector('.price');
            const totalInput = row.querySelector('.total');
            const convInput = row.querySelector('.conv-input');
            const convHint = row.querySelector('.conv-hint');
            const option = this.options[this.selectedIndex];
            const uoms = JSON.parse(option.dataset.uoms || '[]');
            const smallestUomCode = option.dataset.smallestUom || '';

            uomSelect.innerHTML = '<option value="">Select UOM</option>';
            let matchedPrevious = false;
            uoms.forEach(uom => {
                const opt = document.createElement('option');
                opt.value = uom.uom_id;
                opt.textContent = uom.uom.name + ' (' + uom.uom.code + ')';
                if (String(uom.uom_id) === String(previousUomId)) {
                    opt.selected = true;
                    matchedPrevious = true;
                } else if (uom.is_default && !matchedPrevious) {
                    opt.selected = true;
                }
                uomSelect.appendChild(opt);
            });

            if (uomSelect.value) {
                const price = getPriceForUom(uoms, uomSelect.value);
                priceInput.value = price.toFixed(2);
                const qty = parseFloat(row.querySelector('.qty').value) || 0;
                totalInput.value = (qty * price).toFixed(2);
                const selectedUom = uoms.find(u => String(u.uom_id) === String(uomSelect.value));
                if (selectedUom && convInput) {
                    const masterConv = parseFloat(selectedUom.conversion_to_smallest || 1);
                    convInput.value = masterConv;
                    convInput.dataset.itemMasterConversion = masterConv;
                    checkConversionWarning(convInput);
                    if (convHint) convHint.textContent = '1 ' + selectedUom.uom.code + ' = ? ' + smallestUomCode;
                }
            }
        }

        function handleUomChange() {
            const row = this.closest('.item-row');
            const itemSelect = row.querySelector('.item-select');
            const priceInput = row.querySelector('.price');
            const totalInput = row.querySelector('.total');
            const convInput = row.querySelector('.conv-input');
            const convHint = row.querySelector('.conv-hint');
            const option = itemSelect ? itemSelect.options[itemSelect.selectedIndex] : null;
            const uoms = option ? JSON.parse(option.dataset.uoms || '[]') : [];
            const smallestUomCode = option ? (option.dataset.smallestUom || '') : '';
            const price = getPriceForUom(uoms, this.value);
            priceInput.value = price.toFixed(2);
            const qty = parseFloat(row.querySelector('.qty').value) || 0;
            totalInput.value = (qty * price).toFixed(2);
            const selectedUom = uoms.find(u => String(u.uom_id) === String(this.value));
            if (selectedUom && convInput) {
                const masterConv = parseFloat(selectedUom.conversion_to_smallest || 1);
                convInput.value = masterConv;
                convInput.dataset.itemMasterConversion = masterConv;
                checkConversionWarning(convInput);
                if (convHint) convHint.textContent = '1 ' + selectedUom.uom.code + ' = ? ' + smallestUomCode;
            }
        }

        function handlePriceChange() {
            const row = this.closest('.item-row');
            const qty = parseFloat(row.querySelector('.qty').value) || 0;
            const price = parseFloat(row.querySelector('.price').value) || 0;
            row.querySelector('.total').value = (qty * price).toFixed(2);
        }

        function handleConversionInput() {
            checkConversionWarning(this);
        }

        function checkConversionWarning(convInput) {
            const masterConv = parseFloat(convInput.dataset.itemMasterConversion || 0);
            const entered = parseFloat(convInput.value || 0);
            const warning = convInput.parentElement.querySelector('.conv-warning');
            if (!warning) return;
            if (masterConv > 0 && entered !== masterConv) {
                warning.textContent = '\u26a0 Item master: ' + masterConv + '. Override intentional?';
                warning.style.display = '';
            } else {
                warning.textContent = '';
                warning.style.display = 'none';
            }
        }

        function handleRemoveItem() {
            if (document.querySelectorAll('.item-row').length > 1) {
                this.closest('.item-row').remove();
            } else {
                alert('At least one item is required.');
            }
        }

        attachItemEventListeners();

        // Flag any pre-loaded rows whose saved conversion differs from item master
        document.querySelectorAll('.conv-input').forEach(function(input) {
            checkConversionWarning(input);
        });

        // Warn on submit if any conversion overrides are active
        const conversionForm = document.querySelector('form[action*="purchase_orders"]');
        if (conversionForm) conversionForm.addEventListener('submit', function(e) {
                const overrides = Array.from(document.querySelectorAll('.conv-warning'))
                    .filter(el => el.style.display !== 'none' && el.textContent !== '');
                if (overrides.length > 0) {
                    const msg = overrides.length === 1 ?
                        'One item has a conversion that differs from the item master.\nAre you sure the override is correct?' :
                        overrides.length +
                        ' items have conversions that differ from the item master.\nAre you sure all overrides are correct?';
                    if (!confirm(msg)) {
                        e.preventDefault();
                    }
                }
            });

        // Auto-fill supplier details when supplier is selected
        const supplierSelect = document.getElementById('supplier_id');
        if (supplierSelect) supplierSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (this.value) {
                const nameInput = document.getElementById('supplier_name');
                const phoneInput = document.getElementById('supplier_phone');
                const addressInput = document.getElementById('supplier_address');
                const contactInput = document.getElementById('supplier_contact_person');
                const bankField = document.getElementById('bank_account');
                if (nameInput) nameInput.value = selectedOption.dataset.name || '';
                if (phoneInput) phoneInput.value = selectedOption.dataset.phone || '';
                if (addressInput) addressInput.value = selectedOption.dataset.address || '';
                if (contactInput) contactInput.value = selectedOption.dataset.contact_person || '';
                if (bankField) {
                    const bankName = selectedOption.dataset.bank_name || '';
                    const bankAccNo = selectedOption.dataset.bank_account_no || '';
                    const bankAccName = selectedOption.dataset.bank_account_name || '';
                    if (bankName && bankAccNo) {
                        bankField.value = bankName + ' - ' + bankAccNo + (bankAccName ? ' a.n. ' + bankAccName : '');
                    } else if (bankAccNo) {
                        bankField.value = bankAccNo + (bankAccName ? ' a.n. ' + bankAccName : '');
                    } else {
                        bankField.value = '';
                    }
                }
            }
        });

        // PR change: reload items
        const prSelect = document.getElementById('purchase_request_id');
        const prAckNotice = document.getElementById('pr-ack-notice');
        const itemsData = @json($items);
        const addFromPrBtn = document.getElementById('add-from-pr');

        function togglePrAckNotice() {
            if (!prAckNotice) return;
            const selected = prSelect.options[prSelect.selectedIndex];
            prAckNotice.style.display = (selected && selected.dataset.ack === '1') ? 'block' : 'none';
        }

        function updateAddFromPrButton() {
            if (!addFromPrBtn) return;
            addFromPrBtn.style.display = prSelect.value ? 'inline-block' : 'none';
        }

        togglePrAckNotice();
        updateAddFromPrButton();

        function buildPrItemRow(detail, idx) {
            const newItemRow = document.createElement('div');
            newItemRow.className = 'item-row border p-3 mb-2';
            const isPPJ = poType === 'service_order';

            if (isPPJ) {
                newItemRow.innerHTML = `
                <input type="hidden" name="items[${idx}][purchase_request_detail_id]" value="${detail.id || ''}">
                <div class="row"><div class="service-section" style="width:100%;"><div class="row">
                    <div class="col-md-4">
                        <label>Service Description <span class="text-danger">*</span></label>
                        <input type="text" name="items[${idx}][service_description]" class="form-control service-description" value="${detail.service_description || ''}" required>
                    </div>
                    <div class="col-md-1"><label>Qty</label>
                        <input type="number" name="items[${idx}][quantity]" class="form-control qty" step="0.01" min="0.01" value="${detail.quantity || 0}" required>
                    </div>
                    <div class="col-md-2"><label>Unit Price (Rp)</label>
                        <input type="number" name="items[${idx}][unit_price]" class="form-control price" step="0.01" min="0" value="0" required>
                    </div>
                    <div class="col-md-2"><label>Total</label>
                        <input type="text" class="form-control total" readonly disabled value="0.00">
                    </div>
                    <div class="col-md-1"><label>&nbsp;</label><br>
                        <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                    </div>
                </div></div></div>`;
            } else {
                const itemUoms = detail.item && detail.item.item_uoms ? detail.item.item_uoms : [];
                const conversion = getConversionForUom(itemUoms, detail.uom_id);
                const uomCode = detail.uom ? detail.uom.code : getUomCodeFromList(itemUoms, detail.uom_id);
                const smallestCode = detail.item && detail.item.smallest_uom ? detail.item.smallest_uom.code : '';

                let itemOptions = '<option value="">Select Item</option>';
                itemsData.forEach(item => {
                    const sel = detail.item_id == item.id ? 'selected' : '';
                    const sUom = item.smallest_uom ? item.smallest_uom.code : '';
                    itemOptions += `<option value="${item.id}" data-uoms='${JSON.stringify(item.item_uoms || [])}' data-smallest-uom="${sUom}" ${sel}>${item.name} (${item.code})</option>`;
                });

                newItemRow.innerHTML = `
                <input type="hidden" name="items[${idx}][purchase_request_detail_id]" value="${detail.id || ''}">
                <div class="row"><div class="item-section" style="width:100%;"><div class="row">
                    <div class="col-md-3"><label>Item</label>
                        <select name="items[${idx}][item_id]" class="form-control item-select" required>${itemOptions}</select>
                    </div>
                    <div class="col-md-2"><label>UOM</label>
                        <select name="items[${idx}][uom_id]" class="form-control uom-select" required>
                            <option value="${detail.uom_id || ''}" selected>${detail.uom ? detail.uom.name + ' (' + detail.uom.code + ')' : ''}</option>
                        </select>
                    </div>
                    <div class="col-md-2"><label>Isi/Kemasan</label>
                        <input type="number" name="items[${idx}][conversion_to_smallest]" class="form-control conv-input" step="0.000001" min="0.000001" value="${conversion}" required data-item-master-conversion="${conversion}">
                        <small class="text-muted conv-hint">1 ${uomCode} = ? ${smallestCode}</small>
                        <small class="conv-warning" style="display:none;color:#e65c00;font-weight:600;"></small>
                    </div>
                    <div class="col-md-1"><label>Qty</label>
                        <input type="number" name="items[${idx}][quantity]" class="form-control qty" step="0.01" min="0.01" value="${detail.quantity || 0}" required>
                    </div>
                    <div class="col-md-2"><label>Unit Price</label>
                        <input type="number" name="items[${idx}][unit_price]" class="form-control price" step="0.01" min="0" value="0" required>
                    </div>
                    <div class="col-md-1"><label>Total</label>
                        <input type="text" class="form-control total" readonly disabled value="0.00">
                    </div>
                    <div class="col-md-1"><label>&nbsp;</label><br>
                        <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                    </div>
                </div></div></div>`;
            }
            return newItemRow;
        }

        // "Add from PR" button: open modal showing PR items to pick from
        if (addFromPrBtn) addFromPrBtn.addEventListener('click', function() {
            console.log('Add from PR clicked');
            console.log('prSelect.value:', prSelect.value);
            if (!prSelect.value) {
                console.log('No PR selected');
                return;
            }

            // Fetch PR data via AJAX
            fetch(`{{ route('purchase_requests.json', ['purchaseRequest' => ':id']) }}`.replace(':id', prSelect.value))
                .then(response => response.json())
                .then(prData => {
                    console.log('prData:', prData);
                    if (!prData.details || prData.details.length === 0) {
                        console.log('PR has no details');
                        alert('This PR has no items.');
                        return;
                    }

                    // Collect pr_detail IDs already in the items container
                    const existingPrDetailIds = Array.from(
                        document.querySelectorAll('#items-container input[name*="purchase_request_detail_id"]')
                    ).map(el => String(el.value)).filter(v => v);

                    const listEl = document.getElementById('pr-items-list');
                    if (!listEl) return;
                    listEl.innerHTML = '';

                    prData.details.forEach(detail => {
                        const alreadyAdded = existingPrDetailIds.includes(String(detail.id));
                        const label = poType === 'service_order'
                            ? (detail.service_description || 'Service')
                            : ((detail.item ? detail.item.name : '') + ' — ' + (detail.uom ? detail.uom.name : '') + ' × ' + detail.quantity);

                        const row = document.createElement('div');
                        row.className = 'd-flex align-items-center justify-content-between border-bottom py-2';
                        row.innerHTML = `
                            <span class="${alreadyAdded ? 'text-muted' : ''}">
                                ${alreadyAdded ? '<i class="fas fa-check-circle text-success mr-1"></i>' : ''}
                                ${label}
                                ${alreadyAdded ? '<em class="small">(already added)</em>' : ''}
                            </span>
                            <button type="button" class="btn btn-sm btn-${alreadyAdded ? 'secondary' : 'primary'} ml-3 btn-add-pr-item"
                                ${alreadyAdded ? 'disabled' : ''}>
                                <i class="fas fa-plus"></i> Add
                            </button>`;

                        row.querySelector('.btn-add-pr-item').addEventListener('click', function() {
                            const container = document.getElementById('items-container');
                            if (!container) return;
                            const newRow = buildPrItemRow(detail, itemIndex);
                            container.appendChild(newRow);
                            itemIndex++;
                            attachItemEventListeners();

                            // Mark as added in the modal
                            this.disabled = true;
                            this.classList.replace('btn-primary', 'btn-secondary');
                            const span = row.querySelector('span');
                            span.classList.add('text-muted');
                            span.innerHTML = '<i class="fas fa-check-circle text-success mr-1"></i>' + label + ' <em class="small">(already added)</em>';
                        });

                        listEl.appendChild(row);
                    });

                    $('#prItemsModal').modal('show');
                })
                .catch(error => {
                    console.error('Error fetching PR data:', error);
                    alert('Failed to load PR data. Please try again.');
                });
        });

        if (prSelect) prSelect.addEventListener('change', function() {
            togglePrAckNotice();
            updateAddFromPrButton();

            if (!this.value) return;

            // Fetch PR data via AJAX
            fetch(`{{ route('purchase_requests.json', ['purchaseRequest' => ':id']) }}`.replace(':id', this.value))
                .then(response => response.json())
                .then(prData => {
                    if (!prData || !prData.details || prData.details.length === 0) return;

                    if (!confirm('Loading items from PR will replace the current item list. Continue?')) return;

                    const itemsContainer = document.getElementById('items-container');
                    if (!itemsContainer) return;
                    itemsContainer.innerHTML = '';
                    itemIndex = 0;

                    prData.details.forEach((detail) => {
                        itemsContainer.appendChild(buildPrItemRow(detail, itemIndex));
                        itemIndex++;
                    });

                    attachItemEventListeners();
                })
                .catch(error => {
                    console.error('Error fetching PR data:', error);
                    alert('Failed to load PR data. Please try again.');
                });
        });

        // Misc costs dynamic rows
        let miscIndex = {{ $purchaseOrder->miscCosts->count() > 0 ? $purchaseOrder->miscCosts->count() : 1 }};

        const addMiscBtn = document.getElementById('add-misc');
        if (addMiscBtn) addMiscBtn.addEventListener('click', function() {
            const container = document.getElementById('misc-costs-container');
            if (!container) return;
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

        const miscCostsContainer = document.getElementById('misc-costs-container');
        if (miscCostsContainer) miscCostsContainer.addEventListener('click', function(e) {
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

        // Payment method visibility logic
        const paymentMethodSelect = document.getElementById('payment_method');
        const pembayaranSelect = document.getElementById('pembayaran');
        const pembayaranField = document.getElementById('pembayaran_field');
        const bankAccountRow = document.getElementById('bank_account_row');

        function updateBankAccountRow() {
            if (!bankAccountRow || !pembayaranSelect) return;
            bankAccountRow.style.display = pembayaranSelect.value === 'non_tunai' ? 'flex' : 'none';
        }

        function updatePaymentFields() {
            if (!paymentMethodSelect) return;
            const method = paymentMethodSelect.value;
            if (!method) {
                if (pembayaranField) pembayaranField.style.display = 'none';
                if (bankAccountRow) bankAccountRow.style.display = 'none';
                return;
            }
            if (method === 'credit') {
                if (pembayaranSelect) pembayaranSelect.value = 'non_tunai';
                if (pembayaranField) pembayaranField.style.display = 'none';
                if (bankAccountRow) bankAccountRow.style.display = 'flex';
            } else {
                if (pembayaranField) pembayaranField.style.display = 'block';
                updateBankAccountRow();
            }
        }

        if (paymentMethodSelect) paymentMethodSelect.addEventListener('change', updatePaymentFields);
        if (pembayaranSelect) pembayaranSelect.addEventListener('change', updateBankAccountRow);
        updatePaymentFields();

        // Warn on zero prices before submit
        const formEl = document.querySelector('form');
        if (formEl) formEl.addEventListener('submit', function(e) {
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
