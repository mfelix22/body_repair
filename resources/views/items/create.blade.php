@extends('layouts.admin')

@section('title', 'Create Item')
@section('page_title', 'Create Item')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Item Details</h3>
                </div>

                <form action="{{ route('items.store') }}" method="POST" id="itemForm">
                    @csrf
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="item_type">Item Type <span class="text-danger">*</span></label>
                                    <select name="item_type" id="item_type"
                                        class="form-control select2 @error('item_type') is-invalid @enderror" required>
                                        <option value="">Select Type</option>
                                        <option value="A" {{ old('item_type') == 'A' ? 'selected' : '' }}>A - Coating
                                        </option>
                                        <option value="B" {{ old('item_type') == 'B' ? 'selected' : '' }}>B - Chemical
                                        </option>
                                        <option value="C" {{ old('item_type') == 'C' ? 'selected' : '' }}>C -
                                            Consumable</option>
                                        <option value="E" {{ old('item_type') == 'E' ? 'selected' : '' }}>E - Equipment
                                        </option>
                                        <option value="T" {{ old('item_type') == 'T' ? 'selected' : '' }}>T - Tools
                                        </option>
                                        <option value="TE" {{ old('item_type') == 'TE' ? 'selected' : '' }}>TE - Tools &
                                            Equipment</option>
                                        <option value="SP" {{ old('item_type') == 'SP' ? 'selected' : '' }}>SP -
                                            Sparepart</option>
                                    </select>
                                    <small class="form-text text-muted">Code will be auto-generated (e.g., C-0001)</small>
                                    @error('item_type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="name">Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name"
                                        class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                        required>
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="category">Category</label>
                                    <input type="text" name="category" id="category"
                                        class="form-control @error('category') is-invalid @enderror"
                                        value="{{ old('category') }}">
                                    @error('category')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="smallest_uom_id">Smallest UOM <span class="text-danger">*</span></label>
                                    <select name="smallest_uom_id" id="smallest_uom_id"
                                        class="form-control select2 @error('smallest_uom_id') is-invalid @enderror"
                                        required>
                                        <option value="">Select UOM</option>
                                        @foreach ($uoms as $uom)
                                            <option value="{{ $uom->id }}"
                                                {{ old('smallest_uom_id') == $uom->id ? 'selected' : '' }}>
                                                {{ $uom->name }} ({{ $uom->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('smallest_uom_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="reorder_level">
                                        <i class="fas fa-exclamation-triangle text-warning"></i>
                                        Low Stock Alert Level <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" name="reorder_level" id="reorder_level"
                                        class="form-control @error('reorder_level') is-invalid @enderror"
                                        value="{{ old('reorder_level', 0) }}" step="0.01" min="0" required>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> System will warn you when stock drops below this
                                        level
                                    </small>
                                    @error('reorder_level')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
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
                                                value="{{ old('selling_price', 0) }}" step="1" min="0">
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

                                @if (Auth::user()->hasAnyRole(['super_admin', 'admin', 'accounting']))
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
                                                value="{{ old('initial_cost', 0) }}" step="0.01" min="0">
                                        </div>
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i> Starting average cost for COGS. Updated
                                            automatically on each Bon In completion.
                                        </small>
                                        @error('initial_cost')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endif

                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                                        value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror"
                                rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <hr>
                        <h5>UOMs</h5>
                        <div id="uoms-container">
                            <div class="uom-row border p-3 mb-2">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label>UOM</label>
                                        <select name="uoms[0][uom_id]" class="form-control" required>
                                            <option value="">Select UOM</option>
                                            @foreach ($uoms as $uom)
                                                <option value="{{ $uom->id }}">{{ $uom->name }}
                                                    ({{ $uom->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label>Conversion Factor</label>
                                        <input type="number" name="uoms[0][conversion_to_smallest]" class="form-control"
                                            step="0.01" min="0.01" required>
                                        <small class="text-muted">How many smallest UOM in this UOM</small>
                                    </div>
                                    <div class="col-md-2">
                                        <label>
                                            <input type="checkbox" name="uoms[0][is_default]" value="1"
                                                class="form-check-input">
                                            <i class="fas fa-star text-warning"></i> Default
                                        </label>
                                        <small class="form-text text-muted d-block mt-2">
                                            <i class="fas fa-info-circle"></i> Show first in forms
                                        </small>
                                    </div>
                                    <div class="col-md-1">
                                        <label>&nbsp;</label><br>
                                        <button type="button" class="btn btn-danger btn-sm remove-uom">Remove</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-success btn-sm" id="add-uom">Add UOM</button>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Create Item</button>
                        <a href="{{ route('items.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let uomIndex = 1;

        document.getElementById('add-uom').addEventListener('click', function() {
            const container = document.getElementById('uoms-container');
            const uomOptions = @json(
                $uoms->pluck('name', 'id')->map(function ($name, $id) use ($uoms) {
                    $uom = $uoms->find($id);
                    return $name . ' (' . $uom->code . ')';
                }));

            const newUomRow = document.createElement('div');
            newUomRow.className = 'uom-row border p-3 mb-2';
            newUomRow.innerHTML = `
                <div class="row">
                    <div class="col-md-3">
                        <label>UOM</label>
                        <select name="uoms[${uomIndex}][uom_id]" class="form-control" required>
                            <option value="">Select UOM</option>
                            ${Object.entries(uomOptions).map(([id, name]) => `<option value="${id}">${name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Conversion Factor</label>
                        <input type="number" name="uoms[${uomIndex}][conversion_to_smallest]" class="form-control" step="0.01" min="0.01" required>
                        <small class="text-muted">How many smallest UOM in this UOM</small>
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
