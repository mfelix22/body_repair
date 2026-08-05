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

                <form id="po-form" action="{{ route('purchase_orders.store') }}" method="POST">
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
                                    <select name="supplier_id" id="supplier_id" class="form-control select2-supplier"
                                        style="width:100%;">
                                        <option value="">Select Supplier (Optional)</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" data-name="{{ $supplier->name }}"
                                                data-phone="{{ $supplier->phone }}" data-address="{{ $supplier->address }}"
                                                data-contact_person="{{ $supplier->contact_person }}"
                                                data-email="{{ $supplier->email }}"
                                                data-bank_name="{{ $supplier->bank_name }}"
                                                data-bank_account_no="{{ $supplier->bank_account_no }}"
                                                data-bank_account_name="{{ $supplier->bank_account_name }}"
                                                data-npwp="{{ $supplier->npwp }}"
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
                                    <label for="supplier_contact_person">Contact Person</label>
                                    <input type="text" name="supplier_contact_person" id="supplier_contact_person"
                                        class="form-control" value="{{ old('supplier_contact_person') }}"
                                        placeholder="e.g., Budi Santoso">
                                </div>

                                <div class="form-group">
                                    <label for="purchase_request_id">Link to PPB / PPJ</label>
                                    <select name="purchase_request_id" id="purchase_request_id" class="form-control">
                                        <option value="">Select PPB / PPJ (Optional)</option>
                                        @foreach ($prs as $pr)
                                            <option value="{{ $pr->id }}" data-pr='@json($pr)'
                                                data-type='{{ $pr->type }}'
                                                data-ack='{{ $pr->require_acknowledgement ? '1' : '0' }}'
                                                {{ old('purchase_request_id', $selectedPrId) == $pr->id ? 'selected' : '' }}>
                                                {{ $pr->pr_number }} ({{ $pr->type }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Auto-fills items from PR
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
                            <textarea name="supplier_address" id="supplier_address" class="form-control" rows="2">{{ old('supplier_address') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group" id="lokasi_pengerjaan_field" style="display: none;">
                                    <label for="lokasi_pengerjaan">Lokasi Pengerjaan (Work Location)</label>
                                    <input type="text" name="lokasi_pengerjaan" id="lokasi_pengerjaan"
                                        class="form-control" placeholder="e.g., Workshop A, Bengkel Utama"
                                        value="{{ old('lokasi_ engerjaan') }}">
                                </div>
                                <div class="form-group" id="lokasi_pengiriman_field" style="display: none;">
                                    <label for="lokasi_pengiriman">Lokasi Pengiriman (Delivery Location)</label>
                                    <input type="text" name="lokasi_pengiriman" id="lokasi_pengiriman"
                                        class="form-control" placeholder="e.g., Warehouse A, Kantor Pusat"
                                        value="{{ old('lokasi_pengiriman', 'Jl. Daan Mogot No.99') }}">
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
                                            class="custom-control-input" {{ old('include_ppn') ? 'checked' : '' }}
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
                        </div>

                        {{-- Payment Method (Step 1: choose Credit / CBD / DP) --}}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="payment_method">Metode Pembayaran <span
                                            class="text-danger">*</span></label>
                                    <select name="payment_method" id="payment_method"
                                        class="form-control @error('payment_method') is-invalid @enderror" required>
                                        <option value="">-- Pilih Metode --</option>
                                        <option value="credit" {{ old('payment_method') === 'credit' ? 'selected' : '' }}>
                                            Credit</option>
                                        <option value="cbd" {{ old('payment_method') === 'cbd' ? 'selected' : '' }}>
                                            CBD (Cash Before Delivery)</option>
                                        <option value="dp" {{ old('payment_method') === 'dp' ? 'selected' : '' }}>
                                            DP (Down Payment)</option>
                                        <option value="cod" {{ old('payment_method') === 'cod' ? 'selected' : '' }}>
                                            COD (Cash On Delivery)</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        Credit → selalu Non-Tunai. CBD / DP → pilih Tunai atau Non-Tunai. COD → selalu Tunai.
                                    </small>
                                    @error('payment_method')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Step 2: Tunai / Non-Tunai (hidden until method chosen) --}}
                            <div class="col-md-4" id="pembayaran_field" style="display:none;">
                                <div class="form-group">
                                    <label for="pembayaran">Pembayaran <span class="text-danger">*</span></label>
                                    <select name="pembayaran" id="pembayaran"
                                        class="form-control @error('pembayaran') is-invalid @enderror" required>
                                        <option value="">-- Pilih --</option>
                                        <option value="tunai" {{ old('pembayaran') === 'tunai' ? 'selected' : '' }}>
                                            Tunai (Cash)</option>
                                        <option value="non_tunai"
                                            {{ old('pembayaran') === 'non_tunai' ? 'selected' : '' }}>Non Tunai (Transfer)
                                        </option>
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
                                        placeholder="e.g., 14 Hari" value="{{ old('jatuh_tempo') }}">
                                </div>
                            </div>
                        </div>

                        {{-- Bank account: shown only when Non-Tunai --}}
                        <div class="row" id="bank_account_row" style="display:none;">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="bank_account">Rekening Bank <span class="text-danger">*</span></label>
                                    <input type="text" name="bank_account" id="bank_account" class="form-control"
                                        placeholder="e.g., Bank BCA - 0882597666 a.n. PT Megah Jaya"
                                        value="{{ old('bank_account') }}">
                                </div>
                            </div>
                        </div>

                        {{-- Syarat Pembayaran: hidden but kept in DOM for future use --}}
                        <div style="display:none;">
                            <textarea name="payment_terms" id="payment_terms">{{ old('payment_terms') }}</textarea>
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
                                            <div class="col-md-5">
                                                <label>Item</label>
                                                <select name="items[0][item_id]" class="form-control item-select"
                                                    required>
                                                    <option value="">Select Item</option>
                                                    @foreach ($items as $item)
                                                        <option value="{{ $item->id }}"
                                                            data-uoms='@json($item->itemUoms)'
                                                            data-smallest-uom="{{ $item->smallestUom->code ?? '' }}">
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
                                                <label>Isi/Kemasan</label>
                                                <input type="number" name="items[0][conversion_to_smallest]"
                                                    class="form-control conv-input" step="0.000001" min="0.000001"
                                                    placeholder="e.g. 100" required data-item-master-conversion="">
                                                <small class="text-muted conv-hint">1 UOM = ? unit terkecil</small>
                                                <small class="conv-warning" style="display:none;color:#e65c00;font-weight:600;"></small>
                                            </div>
                                            <div class="col-md-1">
                                                <label>Qty</label>
                                                <input type="number" name="items[0][quantity]" class="form-control qty"
                                                    step="0.01" min="0.01" required>
                                            </div>
                                            <div class="col-md-2 text-right">
                                                <label>&nbsp;</label><br>
                                                <button type="button"
                                                    class="btn btn-danger btn-sm remove-item">Remove</button>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-3">
                                                <label>Unit Price</label>
                                                <input type="number" name="items[0][unit_price]"
                                                    class="form-control price" step="0.01" min="0" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label>Disc %</label>
                                                <input type="number" name="items[0][discount_percentage]"
                                                    class="form-control discount-pct" step="0.01" min="0"
                                                    placeholder="%">
                                            </div>
                                            <div class="col-md-2">
                                                <label>Disc Rp</label>
                                                <input type="number" name="items[0][discount]"
                                                    class="form-control discount-amt" step="0.01" min="0"
                                                    placeholder="Rp">
                                            </div>
                                            <div class="col-md-3">
                                                <label>Total</label>
                                                <input type="text" class="form-control total" readonly disabled>
                                            </div>
                                            <div class="col-md-2"></div>
                                        </div>
                                    </div>

                                    <!-- For Service Order (Services) -->
                                    <div class="service-section" id="service-section-0"
                                        style="display: none; width: 100%;">
                                        <div class="row">
                                            <div class="col-md-9">
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
                                            <div class="col-md-2 text-right">
                                                <label>&nbsp;</label><br>
                                                <button type="button"
                                                    class="btn btn-danger btn-sm remove-item">Remove</button>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-3">
                                                <label>Unit Price (Rp)</label>
                                                <input type="number" name="items[0][unit_price]"
                                                    class="form-control price" step="0.01" min="0" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label>Disc %</label>
                                                <input type="number" name="items[0][discount_percentage]"
                                                    class="form-control discount-pct" step="0.01" min="0"
                                                    placeholder="%">
                                            </div>
                                            <div class="col-md-2">
                                                <label>Disc Rp</label>
                                                <input type="number" name="items[0][discount]"
                                                    class="form-control discount-amt" step="0.01" min="0"
                                                    placeholder="Rp">
                                            </div>
                                            <div class="col-md-3">
                                                <label>Total</label>
                                                <input type="text" class="form-control total" readonly disabled>
                                            </div>
                                            <div class="col-md-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-success btn-sm" id="add-item">Add Item</button>

                        <div class="row mt-4">
                            <div class="col-md-4 offset-md-8">

                                <table class="table table-sm">
                                    <tr>
                                        <th>Subtotal:</th>
                                        <td class="text-right" id="summary-subtotal">Rp 0</td>
                                    </tr>
                                    <tr id="summary-discount-row" style="display:none;">
                                        <th>Diskon:</th>
                                        <td class="text-right" id="summary-discount" style="color:#c00;">— Rp 0</td>
                                    </tr>
                                    <tr style="border-top: 2px solid #333;">
                                        <th>Total:</th>
                                        <td class="text-right" id="summary-total"><strong>Rp 0</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="button" class="btn btn-info" id="preview-print">
                            <i class="fas fa-eye"></i> Preview Print
                        </button>
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

        function getConversionForUom(uoms, uomId) {
            const match = uoms.find(uom => String(uom.uom_id) === String(uomId));
            return match ? parseFloat(match.conversion_to_smallest || 1) : 1;
        }

        function getUomCodeFromList(uoms, uomId) {
            const match = uoms.find(uom => String(uom.uom_id) === String(uomId));
            return match ? match.uom.code : '';
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
                                <div class="col-md-9">
                                    <label>Service Description <span class="text-danger">*</span></label>
                                    <input type="text" name="items[${itemIndex}][service_description]" class="form-control service-description"
                                        placeholder="e.g., Cat Mobil, Poles ABC, Ganti Oli" required>
                                </div>
                                <div class="col-md-1">
                                    <label>Qty</label>
                                    <input type="number" name="items[${itemIndex}][quantity]" class="form-control qty"
                                        step="0.01" min="0.01" required>
                                </div>
                                <div class="col-md-2 text-right">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <label>Unit Price (Rp)</label>
                                    <input type="number" name="items[${itemIndex}][unit_price]" class="form-control price"
                                        step="0.01" min="0" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Disc %</label>
                                    <input type="number" name="items[${itemIndex}][discount_percentage]"
                                        class="form-control discount-pct" step="0.01" min="0" placeholder="%">
                                </div>
                                <div class="col-md-2">
                                    <label>Disc Rp</label>
                                    <input type="number" name="items[${itemIndex}][discount]"
                                        class="form-control discount-amt" step="0.01" min="0" placeholder="Rp">
                                </div>
                                <div class="col-md-3">
                                    <label>Total</label>
                                    <input type="text" class="form-control total" readonly disabled>
                                </div>
                                <div class="col-md-2"></div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                // Item row
                const itemSelect = document.querySelector('.item-select');
                const itemOptions = Array.from(document.querySelectorAll('.item-select option')).map(opt =>
                    `<option value="${opt.value}" ${opt.dataset.uoms ? `data-uoms='${opt.dataset.uoms}'` : ''} ${opt.dataset.smallestUom ? `data-smallest-uom="${opt.dataset.smallestUom}"` : ''}>${opt.text}</option>`
                ).join('');

                newItemRow.innerHTML = `
                    <div class="row">
                        <div class="item-section" id="item-section-${itemIndex}" style="width: 100%;">
                            <div class="row">
                                <div class="col-md-5">
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
                                    <label>Isi/Kemasan</label>
                                    <input type="number" name="items[${itemIndex}][conversion_to_smallest]" class="form-control conv-input"
                                        step="0.000001" min="0.000001" placeholder="e.g. 100" required data-item-master-conversion="">
                                    <small class="text-muted conv-hint">1 UOM = ? unit terkecil</small>
                                    <small class="conv-warning" style="display:none;color:#e65c00;font-weight:600;"></small>
                                </div>
                                <div class="col-md-1">
                                    <label>Qty</label>
                                    <input type="number" name="items[${itemIndex}][quantity]" class="form-control qty"
                                        step="0.01" min="0.01" required>
                                </div>
                                <div class="col-md-2 text-right">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <label>Unit Price</label>
                                    <input type="number" name="items[${itemIndex}][unit_price]" class="form-control price"
                                        step="0.01" min="0" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Disc %</label>
                                    <input type="number" name="items[${itemIndex}][discount_percentage]"
                                        class="form-control discount-pct" step="0.01" min="0" placeholder="%">
                                </div>
                                <div class="col-md-2">
                                    <label>Disc Rp</label>
                                    <input type="number" name="items[${itemIndex}][discount]"
                                        class="form-control discount-amt" step="0.01" min="0" placeholder="Rp">
                                </div>
                                <div class="col-md-3">
                                    <label>Total</label>
                                    <input type="text" class="form-control total" readonly disabled>
                                </div>
                                <div class="col-md-2"></div>
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

            document.querySelectorAll('.qty, .price, .discount-pct, .discount-amt').forEach(input => {
                input.removeEventListener('input', handleLineChange);
                input.addEventListener('input', handleLineChange);
                input.removeEventListener('blur', handleDiscountBlur);
                input.addEventListener('blur', handleDiscountBlur);
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

        function handleItemChange(e) {
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
                recalculateLine(row);
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

        function handleUomChange(e) {
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
            recalculateLine(row);
            const selectedUom = uoms.find(u => String(u.uom_id) === String(this.value));
            if (selectedUom && convInput) {
                const masterConv = parseFloat(selectedUom.conversion_to_smallest || 1);
                convInput.value = masterConv;
                convInput.dataset.itemMasterConversion = masterConv;
                checkConversionWarning(convInput);
                if (convHint) convHint.textContent = '1 ' + selectedUom.uom.code + ' = ? ' + smallestUomCode;
            }
        }

        function recalculateLine(row) {
            const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
            const price = parseFloat(row.querySelector('.price')?.value) || 0;
            const gross = qty * price;
            const pctInput = row.querySelector('.discount-pct');
            const amtInput = row.querySelector('.discount-amt');
            const totalInput = row.querySelector('.total');

            let pct = parseFloat(pctInput?.value) || 0;
            let amt = parseFloat(amtInput?.value) || 0;

            // Clamp to valid ranges
            pct = Math.max(0, Math.min(100, pct));
            amt = Math.max(0, Math.min(gross, amt));

            if (pct > 0) {
                amt = Math.min(gross, gross * pct / 100);
            } else if (amt > 0) {
                pct = gross > 0 ? (amt / gross * 100) : 0;
            }

            if (pctInput) pctInput.value = pct.toFixed(2);
            if (amtInput) amtInput.value = amt.toFixed(2);
            if (totalInput) {
                totalInput.value = Math.max(0, gross - amt).toFixed(2);
            }
        }

        function handleLineChange(e) {
            const row = this.closest('.item-row');
            const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
            const price = parseFloat(row.querySelector('.price')?.value) || 0;
            const gross = qty * price;
            const pctInput = row.querySelector('.discount-pct');
            const amtInput = row.querySelector('.discount-amt');
            const totalInput = row.querySelector('.total');

            if (this.classList.contains('discount-pct') && pctInput) {
                const pct = Math.max(0, parseFloat(this.value) || 0);
                const usedPct = Math.min(100, pct);
                const amt = Math.min(gross, gross * usedPct / 100);
                amtInput.value = amt.toFixed(2);
                totalInput.value = Math.max(0, gross - amt).toFixed(2);
            } else if (this.classList.contains('discount-amt') && amtInput) {
                const rawAmt = Math.max(0, parseFloat(this.value) || 0);
                const amt = Math.min(gross, rawAmt);
                const pct = gross > 0 ? (amt / gross * 100) : 0;
                pctInput.value = pct.toFixed(2);
                totalInput.value = Math.max(0, gross - amt).toFixed(2);
            } else {
                recalculateLine(row);
                return updateSummary();
            }

            updateSummary();
        }

        function handleDiscountBlur() {
            recalculateLine(this.closest('.item-row'));
            updateSummary();
        }

        function updateSummary() {
            let subtotal = 0;
            let discount = 0;
            document.querySelectorAll('.item-row').forEach(row => {
                const qty = parseFloat(row.querySelector('.qty')?.value) || 0;
                const price = parseFloat(row.querySelector('.price')?.value) || 0;
                const disc = parseFloat(row.querySelector('.discount-amt')?.value) || 0;
                subtotal += qty * price;
                discount += disc;
            });
            const total = Math.max(0, subtotal - discount);

            document.getElementById('summary-subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            document.getElementById('summary-discount').textContent = '— Rp ' + discount.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
            document.getElementById('summary-total').innerHTML = '<strong>Rp ' + total.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + '</strong>';
            document.getElementById('summary-discount-row').style.display = discount > 0 ? 'table-row' : 'none';
        }

        function handleConversionInput() {
            checkConversionWarning(this);
        }

        function checkConversionWarning(convInput) {
            const masterConv = parseFloat(convInput.dataset.itemMasterConversion || 0);
            const entered    = parseFloat(convInput.value || 0);
            const warning    = convInput.parentElement.querySelector('.conv-warning');
            if (!warning) return;
            if (masterConv > 0 && entered !== masterConv) {
                warning.textContent = '⚠ Item master: ' + masterConv + '. Override intentional?';
                warning.style.display = '';
            } else {
                warning.textContent = '';
                warning.style.display = 'none';
            }
        }

        function handleRemoveItem(e) {
            if (document.querySelectorAll('.item-row').length > 1) {
                this.closest('.item-row').remove();
                updateSummary();
            } else {
                alert('At least one item is required.');
            }
        }

        attachItemEventListeners();

        // Warn on submit if any conversion overrides are active
        document.querySelector('form[action="{{ route("purchase_orders.store") }}"]')
            .addEventListener('submit', function(e) {
                const overrides = Array.from(document.querySelectorAll('.conv-warning'))
                    .filter(el => el.style.display !== 'none' && el.textContent !== '');
                if (overrides.length > 0) {
                    const msg = overrides.length === 1
                        ? 'One item has a conversion that differs from the item master.\nAre you sure the override is correct?'
                        : overrides.length + ' items have conversions that differ from the item master.\nAre you sure all overrides are correct?';
                    if (!confirm(msg)) {
                        e.preventDefault();
                    }
                }
            });

        // Supplier autofill clear handler (Select2 fires jQuery change; fill is done in $(document).ready)
        document.getElementById('supplier_id').addEventListener('change', function() {
            if (!this.value) {
                document.getElementById('supplier_name').value = '';
                document.getElementById('supplier_phone').value = '';
                document.getElementById('supplier_address').value = '';
                document.getElementById('supplier_contact_person').value = '';
                const bankField = document.getElementById('bank_account');
                if (bankField) bankField.value = '';
            }
        });

        const prSelect = document.getElementById('purchase_request_id');
        const addItemButton = document.getElementById('add-item');
        const prAckNotice = document.getElementById('pr-ack-notice');

        function togglePrAckNotice() {
            if (!prAckNotice) return;
            const selected = prSelect.options[prSelect.selectedIndex];
            prAckNotice.style.display = (selected && selected.dataset.ack === '1') ? 'block' : 'none';
        }

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
            togglePrAckNotice();
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
                                <div class="col-md-9">
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
                                <div class="col-md-2 text-right">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <label>Unit Price (Rp)</label>
                                    <input type="number" name="items[${index}][unit_price]" class="form-control price"
                                        step="0.01" min="0" value="${detail.unit_price || 0}" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Disc %</label>
                                    <input type="number" name="items[${index}][discount_percentage]" class="form-control discount-pct"
                                        step="0.01" min="0" placeholder="%">
                                </div>
                                <div class="col-md-2">
                                    <label>Disc Rp</label>
                                    <input type="number" name="items[${index}][discount]" class="form-control discount-amt"
                                        step="0.01" min="0" placeholder="Rp">
                                </div>
                                <div class="col-md-3">
                                    <label>Total</label>
                                    <input type="text" class="form-control total" readonly disabled
                                        value="${((detail.quantity || 0) * (detail.unit_price || 0)).toFixed(2)}">
                                </div>
                                <div class="col-md-2"></div>
                            </div>
                        </div>
                    </div>`;
                } else {
                    const itemUoms = detail.item && detail.item.item_uoms ? detail.item.item_uoms : [];
                    const price = getPriceForUom(itemUoms, detail.uom_id);
                    const conversion = getConversionForUom(itemUoms, detail.uom_id);
                    const uomCode = detail.uom ? detail.uom.code : getUomCodeFromList(itemUoms, detail
                        .uom_id);
                    const smallestCode = detail.item && detail.item.smallest_uom ? detail.item.smallest_uom
                        .code : '';

                    let itemOptions = '<option value="">Select Item</option>';
                    let itemMatched = false;
                    itemsData.forEach(item => {
                        const sel = detail.item_id == item.id ? 'selected' : '';
                        if (sel) itemMatched = true;
                        const sUom = item.smallest_uom ? item.smallest_uom.code : '';
                        itemOptions +=
                            `<option value="${item.id}" data-uoms='${JSON.stringify(item.item_uoms || [])}' data-smallest-uom="${sUom}" ${sel}>${item.name} (${item.code})</option>`;
                    });
                    // Fallback: if item not found in active items list (e.g. deactivated),
                    // use the item data already loaded in the PR detail
                    if (!itemMatched && detail.item_id && detail.item) {
                        const fi = detail.item;
                        const fiSmallestUom = fi.smallest_uom ? fi.smallest_uom.code : '';
                        itemOptions = `<option value="">Select Item</option>` +
                            `<option value="${fi.id}" data-uoms='${JSON.stringify(fi.item_uoms || [])}' data-smallest-uom="${fiSmallestUom}" selected>${fi.name} (${fi.code})</option>`;
                        itemsData.forEach(item => {
                            if (item.id != fi.id) {
                                const sUom = item.smallest_uom ? item.smallest_uom.code : '';
                                itemOptions +=
                                    `<option value="${item.id}" data-uoms='${JSON.stringify(item.item_uoms || [])}' data-smallest-uom="${sUom}">${item.name} (${item.code})</option>`;
                            }
                        });
                    }

                    newItemRow.innerHTML = `
                    <div class="row">
                        <div class="item-section" id="item-section-${index}" style="width: 100%;">
                            <input type="hidden" name="items[${index}][purchase_request_detail_id]" value="${detail.id || ''}">
                            <div class="row">
                                <div class="col-md-5">
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
                                    <label>Isi/Kemasan</label>
                                    <input type="number" name="items[${index}][conversion_to_smallest]" class="form-control conv-input"
                                        step="0.000001" min="0.000001" value="${conversion}" required data-item-master-conversion="${conversion}">
                                    <small class="text-muted conv-hint">1 ${uomCode} = ? ${smallestCode}</small>
                                    <small class="conv-warning" style="display:none;color:#e65c00;font-weight:600;"></small>
                                </div>
                                <div class="col-md-1">
                                    <label>Qty</label>
                                    <input type="number" name="items[${index}][quantity]" class="form-control qty"
                                        step="0.01" min="0.01" value="${detail.quantity || 0}" required>
                                </div>
                                <div class="col-md-2 text-right">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-3">
                                    <label>Unit Price</label>
                                    <input type="number" name="items[${index}][unit_price]" class="form-control price"
                                        step="0.01" min="0" value="${price.toFixed(2)}" required>
                                </div>
                                <div class="col-md-2">
                                    <label>Disc %</label>
                                    <input type="number" name="items[${index}][discount_percentage]" class="form-control discount-pct"
                                        step="0.01" min="0" placeholder="%">
                                </div>
                                <div class="col-md-2">
                                    <label>Disc Rp</label>
                                    <input type="number" name="items[${index}][discount]" class="form-control discount-amt"
                                        step="0.01" min="0" placeholder="Rp">
                                </div>
                                <div class="col-md-3">
                                    <label>Total</label>
                                    <input type="text" class="form-control total" readonly disabled
                                        value="${((detail.quantity || 0) * price).toFixed(2)}">
                                </div>
                                <div class="col-md-2"></div>
                            </div>
                        </div>
                    </div>`;
                }

                itemsContainer.appendChild(newItemRow);
                itemIndex++;
            });

            attachItemEventListeners();
            if (typeof initItemSelect2 === 'function') {
                setTimeout(initItemSelect2, 50);
            }
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
        togglePrAckNotice();
        if (prSelect.value) {
            prSelect.dispatchEvent(new Event('change'));
        }
        document.getElementById('po_type').dispatchEvent(new Event('change'));

        // Warn if any unit price is still 0 before submitting
        document.getElementById('po-form').addEventListener('submit', function(e) {
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

        // Payment method logic
        const paymentMethodSelect = document.getElementById('payment_method');
        const pembayaranField = document.getElementById('pembayaran_field');
        const pembayaranSelect = document.getElementById('pembayaran');
        const bankAccountRow = document.getElementById('bank_account_row');

        function updatePaymentFields() {
            const method = paymentMethodSelect.value;

            if (!method) {
                pembayaranField.style.display = 'none';
                bankAccountRow.style.display = 'none';
                pembayaranSelect.required = false;
                return;
            }

            if (method === 'credit') {
                // Always Non-Tunai — lock it
                pembayaranSelect.value = 'non_tunai';
                pembayaranField.style.display = 'none';
                bankAccountRow.style.display = 'flex';
            } else if (method === 'cod') {
                // COD — always Tunai (Cash)
                pembayaranSelect.value = 'tunai';
                pembayaranField.style.display = 'none';
                bankAccountRow.style.display = 'none';
                pembayaranSelect.required = false;
            } else {
                // CBD or DP — user picks Tunai / Non-Tunai
                pembayaranField.style.display = 'block';
                pembayaranSelect.required = true;
                updateBankAccountRow();
            }
        }

        function updateBankAccountRow() {
            bankAccountRow.style.display = pembayaranSelect.value === 'non_tunai' ? 'flex' : 'none';
        }

        paymentMethodSelect.addEventListener('change', updatePaymentFields);
        pembayaranSelect.addEventListener('change', updateBankAccountRow);
        document.getElementById('discount').addEventListener('input', updateSummary);

        // Run on page load (handles old() values on validation fail)
        updatePaymentFields();
        updateSummary();
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

            // Initialize Select2 for supplier dropdown
            $('#supplier_id').select2({
                theme: 'bootstrap4',
                placeholder: 'Select Supplier (Optional)',
                allowClear: true,
                width: '100%'
            });

            // Re-fire autofill when Select2 changes supplier
            $('#supplier_id').on('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (this.value) {
                    document.getElementById('supplier_name').value = selectedOption.dataset.name || '';
                    document.getElementById('supplier_phone').value = selectedOption.dataset.phone || '';
                    document.getElementById('supplier_address').value = selectedOption.dataset.address ||
                        '';
                    document.getElementById('supplier_contact_person').value = selectedOption.dataset
                        .contact_person || '';
                    const bankName = selectedOption.dataset.bank_name || '';
                    const bankAccNo = selectedOption.dataset.bank_account_no || '';
                    const bankAccName = selectedOption.dataset.bank_account_name || '';
                    const bankField = document.getElementById('bank_account');
                    if (bankField) {
                        if (bankName || bankAccNo) {
                            let bankStr = '';
                            if (bankName) bankStr += bankName;
                            if (bankAccNo) bankStr += (bankStr ? ' - ' : '') + bankAccNo;
                            if (bankAccName) bankStr += ' a.n. ' + bankAccName;
                            bankField.value = bankStr;
                        } else {
                            bankField.value = '';
                        }
                    }
                }
            });

            // Re-initialize after new rows are added
            const originalAddItem = document.getElementById('add-item').onclick;
            document.getElementById('add-item').addEventListener('click', function() {
                setTimeout(initItemSelect2, 100);
            });

            // Preview Print button handler
            document.getElementById('preview-print').addEventListener('click', function() {
                const poType = document.getElementById('po_type').value;
                const supplierName = document.getElementById('supplier_name').value;
                const itemRows = document.querySelectorAll('.item-row');

                if (!poType) { alert('Please select PO Type first.'); return; }
                if (!supplierName) { alert('Please enter Supplier Name.'); return; }
                if (itemRows.length === 0) { alert('Please add at least one item.'); return; }

                const button = this;
                const originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating Preview...';

                // Build form data manually to ensure items are captured
                const formData = new FormData();
                const form = document.getElementById('po-form');

                // Add all basic form fields
                for (const element of form.elements) {
                    if (element.name && !element.disabled && !element.name.startsWith('items[')) {
                        if (element.type === 'checkbox' || element.type === 'radio') {
                            if (element.checked) {
                                formData.append(element.name, element.value);
                            }
                        } else if (element.type === 'select-multiple') {
                            for (const option of element.selectedOptions) {
                                formData.append(element.name, option.value);
                            }
                        } else if (element.type !== 'submit' && element.type !== 'button') {
                            formData.append(element.name, element.value);
                        }
                    }
                }

                // Add items manually from the DOM
                itemRows.forEach((row, index) => {
                    const itemId = row.querySelector(`[name*="[item_id]"]`);
                    const uomId = row.querySelector(`[name*="[uom_id]"]`);
                    const serviceDesc = row.querySelector(`[name*="[service_description]"]`);
                    const quantity = row.querySelector(`[name*="[quantity]"]`);
                    const unitPrice = row.querySelector(`[name*="[unit_price]"]`);
                    const conversion = row.querySelector(`[name*="[conversion_to_smallest]"]`);
                    const remarks = row.querySelector(`[name*="[remarks]"]`);
                    const prDetailId = row.querySelector(`[name*="[purchase_request_detail_id]"]`);

                    if (itemId) formData.append(`items[${index}][item_id]`, itemId.value);
                    if (uomId) formData.append(`items[${index}][uom_id]`, uomId.value);
                    if (serviceDesc) formData.append(`items[${index}][service_description]`, serviceDesc.value);
                    if (quantity) formData.append(`items[${index}][quantity]`, quantity.value);
                    if (unitPrice) formData.append(`items[${index}][unit_price]`, unitPrice.value);
                    if (conversion) formData.append(`items[${index}][conversion_to_smallest]`, conversion.value);
                    if (remarks) formData.append(`items[${index}][remarks]`, remarks.value);
                    if (prDetailId) formData.append(`items[${index}][purchase_request_detail_id]`, prDetailId.value);
                });

                fetch('{{ route("purchase_orders.preview") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/pdf',
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(text);
                        });
                    }
                    return response.blob();
                })
                .then(blob => {
                    const url = URL.createObjectURL(blob);
                    window.open(url, '_blank');
                })
                .catch(error => {
                    alert('Error generating preview: ' + error.message);
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            });
        });
    </script>
@endsection
