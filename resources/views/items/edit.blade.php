@extends('layouts.admin')

@section('title', 'Edit Item')
@section('page_title', 'Edit Item')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit {{ $item->name }}</h3>
                </div>

                @if (!$item->is_complete)
                    <div class="alert alert-warning m-3 mb-0" role="alert">
                        <h5><i class="icon fas fa-exclamation-triangle"></i> Incomplete Item - Action Required!</h5>
                        <p class="mb-1">
                            This item was created from a Purchase Request (PPB) and needs to be completed before it can be
                            used in Bon In.
                        </p>
                        <p class="mb-0">
                            <strong>Please ensure:</strong> Item Code, Category, UOM, and all required fields are properly
                            filled.
                        </p>
                    </div>
                @endif

                <form action="{{ route('items.update', $item) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="item_type">Item Type</label>
                                    <input type="text" id="item_type" class="form-control"
                                        value="{{ $item->item_type }} - {{ $item->item_type_name }}" readonly>
                                    <small class="form-text text-muted">Item type cannot be changed after creation</small>
                                </div>

                                <div class="form-group">
                                    <label for="code">Code</label>
                                    <input type="text" id="code" class="form-control" value="{{ $item->code }}"
                                        readonly>
                                    <small class="form-text text-muted">Auto-generated based on item type</small>
                                </div>

                                <div class="form-group">
                                    <label for="name">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        value="{{ old('name', $item->name) }}" required>
                                </div>

                                <div class="form-group">
                                    <label for="category">Category</label>
                                    <input type="text" name="category" id="category" class="form-control"
                                        value="{{ old('category', $item->category) }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="smallest_uom_id">Smallest UOM <span class="text-danger">*</span></label>
                                    <select name="smallest_uom_id" id="smallest_uom_id" class="form-control select2"
                                        required>
                                        @foreach ($uoms as $uom)
                                            <option value="{{ $uom->id }}"
                                                {{ old('smallest_uom_id', $item->smallest_uom_id) == $uom->id ? 'selected' : '' }}>
                                                {{ $uom->name }} ({{ $uom->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="reorder_level">
                                        <i class="fas fa-exclamation-triangle text-warning"></i>
                                        Low Stock Alert Level <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" name="reorder_level" id="reorder_level" class="form-control"
                                        value="{{ number_format(old('reorder_level', $item->reorder_level), 2, '.', '') }}"
                                        step="0.01" min="0" required>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> System will warn you when stock drops below this
                                        level
                                    </small>
                                </div>

                                @if (Auth::user()->hasAnyRole(['super_admin', 'admin', 'accounting']))
                                    <div class="form-group">
                                        <label for="selling_price">
                                            <i class="fas fa-tag text-success"></i>
                                            Selling Price (per Smallest UOM)
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" name="selling_price" id="selling_price"
                                                class="form-control @error('selling_price') is-invalid @enderror"
                                                value="{{ old('selling_price', number_format($item->selling_price ?? 0, 0, '.', '')) }}"
                                                step="1" min="0">
                                        </div>
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i> Default unit price auto-filled in Sales
                                            Orders.
                                        </small>
                                        @error('selling_price')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endif

                                @if ($item->is_manual_entry && Auth::user()->hasAnyRole(['super_admin', 'admin', 'accounting']))
                                    <div class="form-group">
                                        <label for="initial_cost">
                                            <i class="fas fa-tag text-secondary"></i>
                                            Initial Cost (per Smallest UOM)
                                        </label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" name="initial_cost" id="initial_cost"
                                                class="form-control @error('initial_cost') is-invalid @enderror"
                                                value="{{ old('initial_cost', number_format(optional($item->stock)->avg_cost ?? 0, 2, '.', '')) }}"
                                                step="0.01" min="0">
                                        </div>
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i> Required for manual/dilution items to set
                                            COGS baseline.
                                        </small>
                                        @error('initial_cost')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endif

                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                                        value="1" {{ old('is_active', $item->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="3">{{ old('description', $item->description) }}</textarea>
                        </div>

                        <hr>
                        <h5>UOMs</h5>
                        <div id="uoms-container">
                            @foreach ($item->itemUoms as $index => $itemUom)
                                <div class="uom-row border p-3 mb-2">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label>UOM</label>
                                            <select name="uoms[{{ $index }}][uom_id]" class="form-control"
                                                required>
                                                @foreach ($uoms as $uom)
                                                    <option value="{{ $uom->id }}"
                                                        {{ $itemUom->uom_id == $uom->id ? 'selected' : '' }}>
                                                        {{ $uom->name }} ({{ $uom->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Conversion Factor</label>
                                            <input type="number"
                                                name="uoms[{{ $index }}][conversion_to_smallest]"
                                                class="form-control" step="0.01" min="0.01"
                                                value="{{ number_format($itemUom->conversion_to_smallest, 2, '.', '') }}"
                                                required>
                                        </div>
                                        <div class="col-md-2">
                                            <label>
                                                <input type="checkbox" name="uoms[{{ $index }}][is_default]"
                                                    value="1" class="form-check-input"
                                                    {{ $itemUom->is_default ? 'checked' : '' }}>
                                                <i class="fas fa-star text-warning"></i> Default
                                            </label>
                                            <small class="form-text text-muted d-block mt-2">
                                                <i class="fas fa-info-circle"></i> Show first in forms
                                            </small>
                                        </div>
                                        <div class="col-md-1">
                                            <label>&nbsp;</label><br>
                                            <button type="button"
                                                class="btn btn-danger btn-sm remove-uom">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-success btn-sm" id="add-uom">Add UOM</button>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Update Item</button>
                        <a href="{{ route('items.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let uomIndex = {{ $item->itemUoms->count() }};

        document.getElementById('add-uom').addEventListener('click', function() {
            const container = document.getElementById('uoms-container');
            const uomOptions = `<select name="uoms[${uomIndex}][uom_id]" class="form-control" required>
                                @foreach ($uoms as $uom)
                                    <option value="{{ $uom->id }}">{{ $uom->name }} ({{ $uom->code }})</option>
                                @endforeach
                            </select>`;

            const newUomRow = document.createElement('div');
            newUomRow.className = 'uom-row border p-3 mb-2';
            newUomRow.innerHTML = `
                <div class="row">
                    <div class="col-md-3">
                        <label>UOM</label>
                        ${uomOptions}
                    </div>
                    <div class="col-md-4">
                        <label>Conversion Factor</label>
                        <input type="number" name="uoms[${uomIndex}][conversion_to_smallest]" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    <div class="col-md-2">
                        <label>Default</label><br>
                        <input type="checkbox" name="uoms[${uomIndex}][is_default]" value="1" class="form-check-input">
                    </div>
                    <div class="col-md-1">
                        <label>&nbsp;</label><br>
                        <button type="button" class="btn btn-danger btn-sm remove-uom">Remove</button>
                    </div>
                </div>
            `;
            container.appendChild(newUomRow);
            uomIndex++;
        });

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-uom')) {
                if (document.querySelectorAll('.uom-row').length > 1) {
                    e.target.closest('.uom-row').remove();
                } else {
                    alert('At least one UOM is required.');
                }
            }
        });
    </script>
@endsection
