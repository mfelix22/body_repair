@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@extends('layouts.admin')

@section('title', 'Edit PPB/PPJ')
@section('page_title', 'Edit PPB/PPJ')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $purchaseRequest->pr_number }}</h3>
                </div>

                <form action="{{ route('purchase_requests.update', $purchaseRequest) }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <strong>Error!</strong> Please fix the following:
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                {{ session('error') }}
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label
                                        for="pr_number">{{ $purchaseRequest->type === 'Jasa' ? 'PPJ Number' : 'PPB Number' }}
                                        <span class="text-danger">*</span></label>
                                    <input type="text" name="pr_number" id="pr_number"
                                        class="form-control @error('pr_number') is-invalid @enderror"
                                        value="{{ old('pr_number', $purchaseRequest->pr_number) }}" required>
                                    @error('pr_number')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="request_date">Request Date <span class="text-danger">*</span></label>
                                    <input type="date" name="request_date" id="request_date"
                                        class="form-control @error('request_date') is-invalid @enderror"
                                        value="{{ old('request_date', $purchaseRequest->request_date->format('Y-m-d')) }}"
                                        required>
                                    @error('request_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type">Type <span class="text-danger">*</span></label>
                                    <select name="type" id="type"
                                        class="form-control select2 @error('type') is-invalid @enderror" required>
                                        <option value="">Select Type</option>
                                        <option value="Jasa"
                                            {{ old('type', $purchaseRequest->type) === 'Jasa' ? 'selected' : '' }}>Jasa
                                            (Service) - PPJ</option>
                                        <option value="Barang"
                                            {{ old('type', $purchaseRequest->type) === 'Barang' ? 'selected' : '' }}>Barang
                                            (Items) - PPB</option>
                                    </select>
                                    @error('type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes', $purchaseRequest->notes) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="hidden" name="require_acknowledgement" value="0">
                                    <input type="checkbox" name="require_acknowledgement" id="require_acknowledgement"
                                        class="form-check-input" value="1"
                                        {{ old('require_acknowledgement', $purchaseRequest->require_acknowledgement) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="require_acknowledgement">
                                        Require Direksi/GM Acknowledgement
                                    </label>
                                </div>
                            </div>
                        </div>

                        @if ($purchaseRequest->type === 'Jasa')
                        <div id="ppj-attachment-section">
                            <hr>
                            <h5>Attachments <small class="text-muted">(PPJ only)</small></h5>
                            <div class="form-group">
                                <label for="attachments">Service Banner / Reference Files</label>
                                @if ($purchaseRequest->attachments && $purchaseRequest->attachments->count() > 0)
                                    <div class="mb-2">
                                        <p class="mb-1"><strong>Current Attachments:</strong></p>
                                        @foreach ($purchaseRequest->attachments as $attachment)
                                            <div class="mb-1">
                                                <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-paperclip"></i> {{ $attachment->file_name }}
                                                </a>
                                                <small class="text-muted ml-2">({{ number_format($attachment->file_size / 1024, 2) }} KB)</small>
                                            </div>
                                        @endforeach
                                        <small class="text-muted">Upload new files below to replace all current attachments.</small>
                                    </div>
                                @endif
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" name="attachments[]" id="attachments"
                                            class="custom-file-input @error('attachments') is-invalid @enderror"
                                            accept=".jpg,.jpeg,.png,.pdf" multiple>
                                        <label class="custom-file-label" for="attachments">Choose files (jpg, png, pdf — max 5MB each)</label>
                                    </div>
                                </div>
                                @error('attachments')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Optional. Attach service banners, quotation images, or reference documents for this PPJ. You can select multiple files.</small>
                            </div>
                        </div>
                        @endif

                        <hr>
                        <h5>Items</h5>
                        <div id="items-container">
                            @foreach ($purchaseRequest->details as $index => $detail)
                                <div class="item-row border p-3 mb-2">
                                    @if ($purchaseRequest->type === 'Jasa')
                                        <div class="row">
                                            <div class="col-md-5">
                                                <label>Service Description <span class="text-danger">*</span></label>
                                                <input type="text"
                                                    name="items[{{ $index }}][service_description]"
                                                    class="form-control service-description"
                                                    value="{{ old('items.' . $index . '.service_description', $detail->service_description) }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label>Qty</label>
                                                <input type="number" name="items[{{ $index }}][quantity]"
                                                    class="form-control qty" step="0.01" min="0.01"
                                                    value="{{ $detail->quantity }}">
                                            </div>
                                            <div class="col-md-4">
                                                <label>Notes</label>
                                                <input type="text" name="items[{{ $index }}][notes]"
                                                    class="form-control" value="{{ $detail->notes }}">
                                            </div>
                                            <div class="col-md-1">
                                                <label>&nbsp;</label><br>
                                                <button type="button"
                                                    class="btn btn-danger btn-sm remove-item">Remove</button>
                                            </div>
                                        </div>
                                    @else
                                        @php $isCustom = $detail->is_custom_item ?? false; @endphp
                                        <div class="row mb-2">
                                            <div class="col-md-12">
                                                <label class="d-block mb-1">Item Type <span
                                                        class="text-danger">*</span></label>
                                                <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                                    <label
                                                        class="btn btn-outline-primary btn-sm {{ !$isCustom ? 'active' : '' }}">
                                                        <input type="radio"
                                                            name="items[{{ $index }}][is_custom_item]"
                                                            value="0" {{ !$isCustom ? 'checked' : '' }}> Existing
                                                        Item
                                                    </label>
                                                    <label
                                                        class="btn btn-outline-warning btn-sm {{ $isCustom ? 'active' : '' }}">
                                                        <input type="radio"
                                                            name="items[{{ $index }}][is_custom_item]"
                                                            value="1" {{ $isCustom ? 'checked' : '' }}> New Item (Not
                                                        in Master)
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 existing-item-section"
                                                style="{{ $isCustom ? 'display:none;' : '' }}">
                                                <label>Item <span class="text-danger">*</span></label>
                                                <select name="items[{{ $index }}][item_id]"
                                                    class="form-control item-select select2-item" style="width: 100%;">
                                                    <option value="">Select Item</option>
                                                    @foreach ($items as $item)
                                                        <option value="{{ $item->id }}"
                                                            data-uoms='@json($item->itemUoms)'
                                                            {{ $detail->item_id == $item->id ? 'selected' : '' }}>
                                                            {{ $item->name }} ({{ $item->code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 existing-item-section"
                                                style="{{ $isCustom ? 'display:none;' : '' }}">
                                                <label>Current Stock</label>
                                                <div class="form-control-plaintext text-muted small">
                                                    @if ($detail->item)
                                                        {{ number_format($detail->item->stocks->sum('quantity'), 2) }}
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-3 new-item-section"
                                                style="{{ !$isCustom ? 'display:none;' : '' }}">
                                                <label>Item Name <span class="text-danger">*</span></label>
                                                <input type="text" name="items[{{ $index }}][custom_item_name]"
                                                    class="form-control custom-item-name"
                                                    placeholder="Describe the item you need"
                                                    value="{{ old('items.' . $index . '.custom_item_name', $detail->custom_item_name) }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label>UOM <span class="text-danger">*</span></label>
                                                {{-- Existing UOM select --}}
                                                <select name="{{ !$isCustom ? 'items[' . $index . '][uom_id]' : '' }}"
                                                    class="form-control existing-uom-select"
                                                    style="{{ $isCustom ? 'display:none;' : '' }}"
                                                    {{ $isCustom ? 'disabled' : '' }}>
                                                    <option value="">Select UOM</option>
                                                    @if ($detail->item)
                                                        @foreach ($detail->item->itemUoms as $itemUom)
                                                            <option value="{{ $itemUom->uom_id }}"
                                                                {{ $detail->uom_id == $itemUom->uom_id ? 'selected' : '' }}>
                                                                {{ $itemUom->uom->name }} ({{ $itemUom->uom->code }})
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                {{-- New item UOM select (all UOMs) --}}
                                                <select name="{{ $isCustom ? 'items[' . $index . '][uom_id]' : '' }}"
                                                    class="form-control new-uom-select"
                                                    style="{{ !$isCustom ? 'display:none;' : '' }}"
                                                    {{ !$isCustom ? 'disabled' : '' }}>
                                                    <option value="">Select UOM</option>
                                                    @foreach ($uoms as $uom)
                                                        <option value="{{ $uom->id }}"
                                                            {{ $detail->uom_id == $uom->id ? 'selected' : '' }}>
                                                            {{ $uom->name }} ({{ $uom->code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label>Quantity <span class="text-danger">*</span></label>
                                                <input type="number" name="items[{{ $index }}][quantity]"
                                                    class="form-control" step="0.01" min="0.01"
                                                    value="{{ $detail->quantity }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label>Notes</label>
                                                <input type="text" name="items[{{ $index }}][notes]"
                                                    class="form-control" value="{{ $detail->notes }}">
                                            </div>
                                            <div class="col-md-1">
                                                <label>&nbsp;</label><br>
                                                <button type="button"
                                                    class="btn btn-danger btn-sm remove-item">Remove</button>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-success btn-sm" id="add-item">Add Item</button>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update PR</button>
                        <a href="{{ route('purchase_requests.show', $purchaseRequest) }}"
                            class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        let itemIndex = {{ $purchaseRequest->details->count() }};
        const prType = '{{ $purchaseRequest->type }}';
        const allUoms = @json($uoms);

        // Initialize select2 for item dropdowns
        function initItemSelect2() {
            $('.item-select').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        theme: 'bootstrap4',
                        placeholder: 'Select Item',
                        allowClear: true,
                        width: '100%'
                    }).on('select2:select', function(e) {
                        // Populate UOM and stock when item is selected
                        populateUomAndStock($(this));
                    });
                }
            });
        }

        // Populate UOM and stock display
        function populateUomAndStock($select) {
            const itemRow = $select.closest('.item-row');
            const uomSelect = itemRow.find('.existing-uom-select');
            const stockDisplay = itemRow.find('.stock-display');
            const option = $select.find('option:selected');
            const uoms = JSON.parse(option.attr('data-uoms') || '[]');
            const stock = option.attr('data-stock') || 0;
            const smallestUom = option.attr('data-smallest-uom') || '';

            if (stockDisplay.length) {
                const stockBadgeClass = stock <= 500 ? 'badge-danger' : 'badge-success';
                stockDisplay.html(
                    `<span class="badge ${stockBadgeClass}">${parseFloat(stock).toFixed(2)} ${smallestUom}</span>`);
            }

            uomSelect.html('<option value="">Select UOM</option>');
            uoms.forEach(uom => {
                const optionHtml =
                    `<option value="${uom.uom_id}" ${uom.is_default ? 'selected' : ''}>${uom.uom.name} (${uom.uom.code})</option>`;
                uomSelect.append(optionHtml);
            });
        }

        // Initialize on page load.
        // IMPORTANT: Use $(document).ready (jQuery queue) NOT addEventListener('DOMContentLoaded').
        // The layout (admin.blade.php) runs its own Select2 init in $(document).ready.
        // jQuery queues multiple .ready() calls in registration order — since @stack('scripts')
        // is after the layout's inline script, OUR ready fires after the layout's, guaranteeing
        // we run last and nothing resets our values.
        $(document).ready(function() {
            // The layout's global init only targets '.select2' class elements.
            // Our item-selects use class 'select2-item', so the layout never touched them.
            // Their native .value still reflects the server-rendered 'selected' attribute.
            var savedValues = {};
            $('.item-select').each(function(i) {
                savedValues[i] = this.value;
            });

            initItemSelect2();

            // Restore each pre-selected value after Select2 has wrapped the elements.
            // Set native .value directly then use 'change.select2' (Select2's own namespace)
            // which forces Select2 to re-read the native value and redraw its displayed text,
            // WITHOUT firing our 'select2:select' handler (so UOM/stock display stays intact).
            $('.item-select').each(function(i) {
                if (savedValues[i]) {
                    this.value = savedValues[i];
                    $(this).trigger('change.select2');
                }
            });
        });

        document.getElementById('add-item').addEventListener('click', function() {
            const container = document.getElementById('items-container');
            const newItemRow = document.createElement('div');
            newItemRow.className = 'item-row border p-3 mb-2';

            if (prType === 'Jasa') {
                newItemRow.innerHTML = `
                    <div class="row">
                        <div class="col-md-5">
                            <label>Service Description <span class="text-danger">*</span></label>
                            <input type="text" name="items[${itemIndex}][service_description]"
                                class="form-control service-description"
                                placeholder="e.g., Cat Mobil, Poles ABC">
                        </div>
                        <div class="col-md-2">
                            <label>Qty</label>
                            <input type="number" name="items[${itemIndex}][quantity]"
                                class="form-control qty" step="0.01" min="0.01">
                        </div>
                        <div class="col-md-4">
                            <label>Notes</label>
                            <input type="text" name="items[${itemIndex}][notes]" class="form-control">
                        </div>
                        <div class="col-md-1">
                            <label>&nbsp;</label><br>
                            <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                        </div>
                    </div>
                `;
            } else {
                const itemOptions = Array.from(document.querySelectorAll('.item-select option')).map(opt =>
                    `<option value="${opt.value}" data-uoms='${opt.dataset.uoms || '[]'}'>${opt.text}</option>`
                ).join('');
                const allUomOptions = allUoms.map(u =>
                    `<option value="${u.id}">${u.name} (${u.code})</option>`
                ).join('');

                newItemRow.innerHTML = `
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <label class="d-block mb-1">Item Type <span class="text-danger">*</span></label>
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-outline-primary btn-sm active">
                                    <input type="radio" name="items[${itemIndex}][is_custom_item]" value="0" checked> Existing Item
                                </label>
                                <label class="btn btn-outline-warning btn-sm">
                                    <input type="radio" name="items[${itemIndex}][is_custom_item]" value="1"> New Item (Not in Master)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 existing-item-section">
                            <label>Item <span class="text-danger">*</span></label>
                            <select name="items[${itemIndex}][item_id]" class="form-control item-select select2-item" style="width: 100%;">
                                ${itemOptions}
                            </select>
                        </div>
                        <div class="col-md-2 existing-item-section">
                            <label>Current Stock</label>
                            <div class="form-control-plaintext font-weight-bold stock-display">-</div>
                        </div>
                        <div class="col-md-2 new-item-section" style="display:none;">
                            <label>Item Type <span class="text-danger">*</span></label>
                            <select name="items[${itemIndex}][custom_item_type]" class="form-control custom-item-type">
                                <option value="A">Coating</option>
                                <option value="B">Chemical</option>
                                <option value="C" selected>Consumable</option>
                                <option value="E">Equipment</option>
                                <option value="T">Tools</option>
                                <option value="TE">Tools &amp; Equipment</option>
                                <option value="SP">Sparepart</option>
                                <option value="P">Cat</option>
                                <option value="D">Body</option>
                            </select>
                        </div>
                        <div class="col-md-3 new-item-section" style="display:none;">
                            <label>Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="items[${itemIndex}][custom_item_name]"
                                class="form-control custom-item-name" placeholder="Describe the item you need">
                        </div>
                        <div class="col-md-2">
                            <label>UOM <span class="text-danger">*</span></label>
                            <select name="items[${itemIndex}][uom_id]" class="form-control existing-uom-select">
                                <option value="">Select UOM</option>
                            </select>
                            <select class="form-control new-uom-select" style="display:none;" disabled>
                                <option value="">Select UOM</option>
                                ${allUomOptions}
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="items[${itemIndex}][quantity]" class="form-control" step="0.01" min="0.01">
                        </div>
                        <div class="col-md-2">
                            <label>Notes</label>
                            <input type="text" name="items[${itemIndex}][notes]" class="form-control">
                        </div>
                        <div class="col-md-1">
                            <label>&nbsp;</label><br>
                            <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                        </div>
                    </div>
                `;
            }

            container.appendChild(newItemRow);
            itemIndex++;

            // Initialize Select2 for the newly added item select
            initItemSelect2();
        });

        // Event delegation for all container interactions
        document.getElementById('items-container').addEventListener('change', function(e) {
            // Handle existing/new item toggle
            if (e.target.name && e.target.name.includes('[is_custom_item]')) {
                const row = e.target.closest('.item-row');
                const isCustom = e.target.value === '1';
                const existingSections = row.querySelectorAll('.existing-item-section');
                const newSections = row.querySelectorAll('.new-item-section');
                const existingUomSelect = row.querySelector('.existing-uom-select');
                const newUomSelect = row.querySelector('.new-uom-select');
                const match = e.target.name.match(/items\[(\d+)\]/);
                const idx = match ? match[1] : '';

                if (isCustom) {
                    existingSections.forEach(s => s.style.display = 'none');
                    newSections.forEach(s => s.style.display = '');
                    existingUomSelect.style.display = 'none';
                    existingUomSelect.disabled = true;
                    existingUomSelect.name = '';
                    newUomSelect.style.display = '';
                    newUomSelect.disabled = false;
                    newUomSelect.name = `items[${idx}][uom_id]`;
                } else {
                    existingSections.forEach(s => s.style.display = '');
                    newSections.forEach(s => s.style.display = 'none');
                    newUomSelect.style.display = 'none';
                    newUomSelect.disabled = true;
                    newUomSelect.name = '';
                    existingUomSelect.style.display = '';
                    existingUomSelect.disabled = false;
                    existingUomSelect.name = `items[${idx}][uom_id]`;
                }
            }

            // Note: item selection population now handled by Select2's select2:select event
        });

        document.getElementById('items-container').addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-item')) {
                if (document.querySelectorAll('.item-row').length > 1) {
                    e.target.closest('.item-row').remove();
                } else {
                    alert('At least one item is required.');
                }
            }
        });

        // Form validation before submission
        document.querySelector('form').addEventListener('submit', function(e) {
            let isValid = true;
            const errors = [];
            const itemRows = document.querySelectorAll('.item-row');

            if (itemRows.length === 0) {
                isValid = false;
                errors.push('Please add at least one item.');
            }

            itemRows.forEach((row, index) => {
                if (prType === 'Jasa') {
                    const description = row.querySelector('input[name*="service_description"]');
                    const qty = row.querySelector('input[name*="quantity"]');
                    if (!description || description.value.trim() === '') {
                        isValid = false;
                        errors.push(`Item ${index + 1}: Service Description is required.`);
                    }
                    if (!qty || !qty.value || parseFloat(qty.value) <= 0) {
                        isValid = false;
                        errors.push(`Item ${index + 1}: Quantity must be greater than 0.`);
                    }
                } else if (prType === 'Barang') {
                    const customRadio = row.querySelector('input[name*="is_custom_item"]:checked');
                    const isCustom = customRadio && customRadio.value === '1';
                    const qty = row.querySelector('input[name*="quantity"]');
                    const uomId = row.querySelector('select[name*="uom_id"]:not([disabled])');

                    if (isCustom) {
                        const customName = row.querySelector('.custom-item-name');
                        if (!customName || customName.value.trim() === '') {
                            isValid = false;
                            errors.push(`Item ${index + 1}: Item name is required for new items.`);
                        }
                    } else {
                        const itemId = row.querySelector('select[name*="item_id"]');
                        if (!itemId || itemId.value === '') {
                            isValid = false;
                            errors.push(`Item ${index + 1}: Please select an existing item.`);
                        }
                    }
                    if (!uomId || uomId.value === '') {
                        isValid = false;
                        errors.push(`Item ${index + 1}: Please select a UOM.`);
                    }
                    if (!qty || !qty.value || parseFloat(qty.value) <= 0) {
                        isValid = false;
                        errors.push(`Item ${index + 1}: Quantity must be greater than 0.`);
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
            }
        });

        // Update file input label when files chosen
        $('#attachments').on('change', function() {
            const count = this.files.length;
            const label = count > 0 ? `${count} file(s) selected` : 'Choose files (jpg, png, pdf — max 5MB each)';
            $(this).next('label').text(label);
        });
    </script>
@endpush
