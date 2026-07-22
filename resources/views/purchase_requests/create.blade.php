@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush
@extends('layouts.admin')

@section('title', 'Create PPB/PPJ')
@section('page_title', 'Create PPB/PPJ')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">PPB/PPJ Details</h3>
                </div>

                <form action="{{ route('purchase_requests.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
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
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> PPB/PPJ Number will be auto-generated
                                </div>

                                <div class="form-group">
                                    <label for="request_date">Request Date <span class="text-danger">*</span></label>
                                    <input type="date" name="request_date" id="request_date"
                                        class="form-control @error('request_date') is-invalid @enderror"
                                        value="{{ old('request_date', date('Y-m-d')) }}" required>
                                    @error('request_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type">Type <span class="text-danger">*</span></label>
                                    <select name="type" id="type"
                                        class="form-control select2 @error('type') is-invalid @enderror" required>
                                        <option value="">Select Type</option>
                                        <option value="Jasa" {{ old('type') === 'Jasa' ? 'selected' : '' }}>Jasa (Service)
                                            - PPJ
                                        </option>
                                        <option value="Barang" {{ old('type') === 'Barang' ? 'selected' : '' }}>Barang
                                            (Items) - PPB</option>
                                    </select>
                                    @error('type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check" style="padding-top: 30px;">
                                    <input type="hidden" name="require_acknowledgement" value="0">
                                    <input type="checkbox" name="require_acknowledgement" id="require_acknowledgement"
                                        class="form-check-input" value="1"
                                        {{ old('require_acknowledgement', false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="require_acknowledgement">
                                        Require Direksi/GM Acknowledgement
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="ppj-attachment-section" style="display:none;">
                            <hr>
                            <h5>Attachments <small class="text-muted">(PPJ only)</small></h5>
                            <div class="form-group">
                                <label for="attachments">Service Banner / Reference Files</label>
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

                        <hr>
                        <h5>Items</h5>
                        <div id="no-type-msg" class="alert alert-warning" style="display:none;">
                            <i class="fas fa-info-circle"></i> Please select a Type above to add items.
                        </div>
                        {{-- Hidden data source for item options --}}
                        <select id="item-options-source" style="display:none;">
                            <option value="">Select Item</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}" data-uoms='@json($item->itemUoms)'
                                    data-stock="{{ $item->stocks->sum('quantity') }}"
                                    data-smallest-uom="{{ $item->smallestUom->code }}">
                                    {{ $item->code }} - {{ $item->name }} [{{ $item->getItemTypeNameAttribute() }}]
                                </option>
                            @endforeach
                        </select>
                        <div id="items-container"></div>
                        <button type="button" class="btn btn-success btn-sm" id="add-item">Add Item</button>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Create PPB/PPJ</button>
                        <a href="{{ route('purchase_requests.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Move all JavaScript to @section('scripts') to run after jQuery is loaded --}}
@endsection

@section('scripts')
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        let itemIndex = 1;
        const allUoms = @json($uoms);

        // Initialize select2 for item dropdowns
        function initItemSelect2() {
            $('.select2-item').each(function() {
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

            const stockBadgeClass = stock <= 500 ? 'badge-danger' : 'badge-success';
            stockDisplay.html(
                `<span class="badge ${stockBadgeClass}">${parseFloat(stock).toFixed(2)} ${smallestUom}</span>`);

            uomSelect.html('<option value="">Select UOM</option>');
            uoms.forEach(uom => {
                const optionHtml =
                    `<option value="${uom.uom_id}" ${uom.is_default ? 'selected' : ''}>${uom.uom.name} (${uom.uom.code})</option>`;
                uomSelect.append(optionHtml);
            });
        }

        // Format item display in dropdown
        function formatItemOption(data) {
            if (!data.id) return data.text;
            const option = $('[value="' + data.id + '"]');
            const code = option.closest('select').attr('data-code') || '';
            return $('<span>' + data.text + '</span>');
        }

        // Format item display in selection
        function formatItemSelection(data) {
            return data.text;
        }

        function buildBarangRow(index) {
            const itemOptions = Array.from(document.querySelectorAll('#item-options-source option'))
                .map(opt =>
                    `<option value="${opt.value}" data-uoms='${opt.dataset.uoms || '[]'}' data-stock='${opt.dataset.stock || 0}' data-smallest-uom='${opt.dataset.smallestUom || ''}'>${opt.text}</option>`
                )
                .join('');

            const allUomOptions = allUoms.map(u =>
                `<option value="${u.id}">${u.name} (${u.code})</option>`
            ).join('');

            const row = document.createElement('div');
            row.className = 'item-row border p-3 mb-2';
            row.innerHTML = `
                <div class="row mb-2">
                    <div class="col-md-12">
                        <label class="d-block mb-1">Item Type <span class="text-danger">*</span></label>
                        <div class="btn-group btn-group-toggle" data-toggle="buttons">
                            <label class="btn btn-outline-primary btn-sm active">
                                <input type="radio" name="items[${index}][is_custom_item]" value="0" checked> Existing Item
                            </label>
                            <label class="btn btn-outline-warning btn-sm">
                                <input type="radio" name="items[${index}][is_custom_item]" value="1"> New Item (Not in Master)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 existing-item-section">
                        <label>Item <span class="text-danger">*</span></label>
                        <select name="items[${index}][item_id]" class="form-control item-select select2-item" style="width: 100%;">${itemOptions}</select>
                    </div>
                    <div class="col-md-2 existing-item-section">
                        <label>Current Stock</label>
                        <div class="form-control-plaintext font-weight-bold stock-display">-</div>
                    </div>
                    <div class="col-md-2 new-item-section" style="display:none;">
                        <label>Item Type <span class="text-danger">*</span></label>
                        <select name="items[${index}][custom_item_type]" class="form-control custom-item-type">
                            <option value="A">Coating</option>
                            <option value="B">Chemical</option>
                            <option value="C" selected>Consumable</option>
                            <option value="E">Equipment</option>
                            <option value="T">Tools</option>
                            <option value="TE">Tools &amp; Equipment</option>
                            <option value="SP">Sparepart</option>
                        </select>
                    </div>
                    <div class="col-md-3 new-item-section" style="display:none;">
                        <label>Item Name <span class="text-danger">*</span></label>
                        <input type="text" name="items[${index}][custom_item_name]" class="form-control custom-item-name"
                            placeholder="Describe the item you need">
                    </div>
                    <div class="col-md-2">
                        <label>UOM <span class="text-danger">*</span></label>
                        <select name="items[${index}][uom_id]" class="form-control uom-select existing-uom-select">
                            <option value="">Select UOM</option>
                        </select>
                        <select class="form-control new-uom-select" style="display:none;" disabled>
                            <option value="">Select UOM</option>
                            ${allUomOptions}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="items[${index}][quantity]" class="form-control" step="0.01" min="0.01">
                    </div>
                    <div class="col-md-2">
                        <label>Notes</label>
                        <input type="text" name="items[${index}][notes]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label>&nbsp;</label><br>
                        <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                    </div>
                </div>
            `;
            return row;
        }

        function buildJasaRow(index) {
            const row = document.createElement('div');
            row.className = 'item-row border p-3 mb-2';
            row.innerHTML = `
                <div class="row">
                    <div class="col-md-5">
                        <label>Service Description <span class="text-danger">*</span></label>
                        <input type="text" name="items[${index}][service_description]" class="form-control service-description"
                            placeholder="e.g., Cat Mobil, Poles ABC, Ganti Oli">
                    </div>
                    <div class="col-md-2">
                        <label>Qty <span class="text-danger">*</span></label>
                        <input type="number" name="items[${index}][quantity]" class="form-control qty" step="0.01" min="0.01">
                    </div>
                    <div class="col-md-4">
                        <label>Notes</label>
                        <input type="text" name="items[${index}][notes]" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label>&nbsp;</label><br>
                        <button type="button" class="btn btn-danger btn-sm remove-item">Remove</button>
                    </div>
                </div>
            `;
            return row;
        }

        function clearAndAddFirstRow(type) {
            itemIndex = 0;
            const container = document.getElementById('items-container');
            container.innerHTML = '';
            document.getElementById('no-type-msg').style.display = 'none';
            if (type === 'Barang') {
                container.appendChild(buildBarangRow(itemIndex));
                initItemSelect2();
            } else if (type === 'Jasa') {
                initItemSelect2();
                container.appendChild(buildJasaRow(itemIndex));
            }
            itemIndex = 1;
        }

        // Initialize on page load
        $(document).ready(function() {
            const type = $('#type').val();
            toggleAttachmentSection(type);
            if (type) {
                clearAndAddFirstRow(type);
            } else {
                $('#no-type-msg').show();
            }
        });

        function toggleAttachmentSection(type) {
            if (type === 'Jasa') {
                $('#ppj-attachment-section').show();
            } else {
                $('#ppj-attachment-section').hide();
                $('#attachments').val('');
                $('#attachments').next('label').text('Choose files (jpg, png, pdf — max 5MB each)');
            }
        }

        // Update file label when files are chosen
        $('#attachments').on('change', function() {
            const count = this.files.length;
            const label = count > 0 ? `${count} file(s) selected` : 'Choose files (jpg, png, pdf — max 5MB each)';
            $(this).next('label').text(label);
        });

        // When type changes, reset item rows - use jQuery .on() for Select2 compatibility
        $('#type').on('change', function() {
            const type = $(this).val();
            console.log('[TYPE] Type changed:', type);
            toggleAttachmentSection(type);
            if (type) {
                clearAndAddFirstRow(type);
            } else {
                $('#items-container').html('');
                $('#no-type-msg').show();
            }
        });

        $('#add-item').on('click', function() {
            const container = $('#items-container');
            const type = $('#type').val();

            if (!type) {
                alert('Please select a type (Jasa or Barang) first.');
                return;
            }

            if (type === 'Jasa') {
                container.append(buildJasaRow(itemIndex));
            } else {
                container.append(buildBarangRow(itemIndex));
                initItemSelect2();
            }
            itemIndex++;
        });

        // Event delegation for item-type toggle (existing vs new)
        $('#items-container').on('change', 'input[name*="[is_custom_item]"]', function(e) {
            const target = e.target;
            const row = $(target).closest('.item-row')[0];
            const isCustom = target.value === '1';

            const existingSections = row.querySelectorAll('.existing-item-section');
            const newSections = row.querySelectorAll('.new-item-section');
            const existingUomSelect = row.querySelector('.existing-uom-select');
            const newUomSelect = row.querySelector('.new-uom-select');

            if (isCustom) {
                existingSections.forEach(s => s.style.display = 'none');
                newSections.forEach(s => s.style.display = '');
                // Swap UOM selects
                existingUomSelect.style.display = 'none';
                existingUomSelect.disabled = true;
                existingUomSelect.name = '';
                newUomSelect.style.display = '';
                newUomSelect.disabled = false;
                // Extract index from radio name like items[2][is_custom_item]
                const match = target.name.match(/items\[(\d+)\]/);
                if (match) {
                    newUomSelect.name = `items[${match[1]}][uom_id]`;
                }
            } else {
                existingSections.forEach(s => s.style.display = '');
                newSections.forEach(s => s.style.display = 'none');
                // Swap UOM selects back
                newUomSelect.style.display = 'none';
                newUomSelect.disabled = true;
                newUomSelect.name = '';
                existingUomSelect.style.display = '';
                existingUomSelect.disabled = false;
                const match = target.name.match(/items\[(\d+)\]/);
                if (match) {
                    existingUomSelect.name = `items[${match[1]}][uom_id]`;
                }
            }
        });

        // Use event delegation for remove buttons
        $('#items-container').on('click', '.remove-item', function(e) {
            if ($('.item-row').length > 1) {
                $(this).closest('.item-row').remove();
            } else {
                alert('At least one item is required.');
            }
        });

        // Form validation before submission
        $('form').on('submit', function(e) {
            const type = $('#type').val();
            let isValid = true;
            const errors = [];

            if (!type) {
                isValid = false;
                errors.push('Please select a type (Jasa or Barang).');
            }

            const itemRows = $('.item-row').toArray();
            if (itemRows.length === 0) {
                isValid = false;
                errors.push('Please add at least one item.');
            }

            itemRows.forEach((row, index) => {
                const $row = $(row);
                if (type === 'Jasa') {
                    const description = $row.find('input[name*="service_description"]')[0];
                    const qty = $row.find('input[name*="quantity"]')[0];

                    if (!description || description.value.trim() === '') {
                        isValid = false;
                        errors.push(`Item ${index + 1}: Service Description is required.`);
                    }
                    if (!qty || !qty.value || parseFloat(qty.value) <= 0) {
                        isValid = false;
                        errors.push(`Item ${index + 1}: Quantity must be greater than 0.`);
                    }
                } else if (type === 'Barang') {
                    const customRadio = $row.find('input[name*="is_custom_item"]:checked')[0];
                    const isCustom = customRadio && customRadio.value === '1';
                    const qty = $row.find('input[name*="quantity"]')[0];

                    if (isCustom) {
                        const customName = $row.find('.custom-item-name')[0];
                        if (!customName || customName.value.trim() === '') {
                            isValid = false;
                            errors.push(`Item ${index + 1}: Item name is required for new items.`);
                        }
                    } else {
                        const itemId = $row.find('select[name*="item_id"]')[0];
                        if (!itemId || itemId.value === '') {
                            isValid = false;
                            errors.push(`Item ${index + 1}: Please select an existing item.`);
                        }
                    }

                    const uomId = $row.find('select[name*="uom_id"]:not([disabled])')[0];
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

        // Initialize Select2 for the type dropdown
        $('#type').select2({
            theme: 'bootstrap4',
            placeholder: 'Select Type',
            width: '100%'
        });
    </script>
@endsection
