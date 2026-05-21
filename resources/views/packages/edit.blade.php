@extends('layouts.admin')

@section('title', 'Edit Product')
@section('page_title', 'Edit Product: ' . $package->name)

@section('content')
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Product Information</h3>
                </div>

                <form action="{{ route('packages.update', $package) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category">Category <span class="text-danger">*</span></label>
                                    <input type="text" name="category" id="category"
                                        class="form-control @error('category') is-invalid @enderror"
                                        value="{{ old('category', $package->category) }}">
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="code">Code <span class="text-danger">*</span></label>
                                    <input type="text" name="code" id="code"
                                        class="form-control @error('code') is-invalid @enderror"
                                        value="{{ old('code', $package->code) }}" style="text-transform: uppercase;">
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="is_active">Status</label>
                                    <select name="is_active" id="is_active" class="form-control">
                                        <option value="1"
                                            {{ old('is_active', $package->is_active) == '1' ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="0"
                                            {{ old('is_active', $package->is_active) == '0' ? 'selected' : '' }}>Inactive
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="name">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $package->name) }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="2">{{ old('description', $package->description) }}</textarea>
                        </div>

                        <hr>

                        <h5>Product Sizes & Prices</h5>
                        <div id="sizes-container">
                            @foreach ($package->sizes as $index => $size)
                                <div class="size-row row mb-2" data-id="{{ $size->id }}">
                                    <input type="hidden" name="sizes[{{ $index }}][id]"
                                        value="{{ $size->id }}">
                                    <div class="col-md-5">
                                        <input type="text" name="sizes[{{ $index }}][size_name]"
                                            class="form-control"
                                            value="{{ old('sizes.' . $index . '.size_name', $size->size_name) }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="number" name="sizes[{{ $index }}][price]" class="form-control"
                                            value="{{ old('sizes.' . $index . '.price', $size->price) }}" min="0"
                                            step="1000" required>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="sizes[{{ $index }}][is_active]"
                                            class="form-control form-control-sm">
                                            <option value="1"
                                                {{ old('sizes.' . $index . '.is_active', $size->is_active) == '1' ? 'selected' : '' }}>
                                                Active</option>
                                            <option value="0"
                                                {{ old('sizes.' . $index . '.is_active', $size->is_active) == '0' ? 'selected' : '' }}>
                                                Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-sm remove-size">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" id="add-size" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Add Size
                        </button>

                        <hr>

                        <h5><i class="fas fa-boxes"></i> Bill of Materials (BOM)
                            <small class="text-muted" style="font-size:13px;">— default material list for this package
                                (quantities are base estimates; staff adjusts at WO creation)</small>
                        </h5>
                        <div id="bom-container">
                            @foreach ($package->bomItems as $bi)
                                <div class="bom-row card mb-2 border-left-info">
                                    <div class="card-body py-2">
                                        <div class="row align-items-center">
                                            <div class="col-md-5">
                                                <div class="form-group mb-0">
                                                    <label class="small">Item</label>
                                                    <select name="bom[{{ $loop->index }}][item_id]"
                                                        class="form-control form-control-sm">
                                                        <option value="">Select Item</option>
                                                        @foreach ($items as $item)
                                                            <option value="{{ $item->id }}"
                                                                data-uom="{{ $item->smallestUom->code ?? '-' }}"
                                                                {{ $bi->item_id == $item->id ? 'selected' : '' }}>
                                                                [{{ $item->code }}] {{ $item->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mb-0">
                                                    <label class="small">UOM</label>
                                                    <select name="bom[{{ $loop->index }}][uom_id]"
                                                        class="form-control form-control-sm">
                                                        <option value="">Select UOM</option>
                                                        @foreach ($uoms as $uom)
                                                            <option value="{{ $uom->id }}"
                                                                {{ $bi->uom_id == $uom->id ? 'selected' : '' }}>
                                                                {{ $uom->name }} ({{ $uom->code }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group mb-0">
                                                    <label class="small">Qty</label>
                                                    <input type="number" name="bom[{{ $loop->index }}][quantity]"
                                                        class="form-control form-control-sm" value="{{ $bi->quantity }}"
                                                        step="0.001" min="0.001">
                                                </div>
                                            </div>
                                            <div class="col-md-1 mt-3">
                                                <button type="button" class="btn btn-danger btn-sm remove-bom">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" id="add-bom" class="btn btn-info btn-sm">
                            <i class="fas fa-plus"></i> Add BOM Item
                        </button>
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('packages.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Package
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let sizeIndex = {{ $package->sizes->count() }};

        document.getElementById('add-size').addEventListener('click', function() {
            const container = document.getElementById('sizes-container');
            const newRow = document.createElement('div');
            newRow.className = 'size-row row mb-2';
            newRow.innerHTML = `
            <div class="col-md-5">
                <input type="text" name="sizes[${sizeIndex}][size_name]" class="form-control"
                    placeholder="e.g., Size S, All, 2 Row" required>
            </div>
            <div class="col-md-4">
                <input type="number" name="sizes[${sizeIndex}][price]" class="form-control"
                    placeholder="Price" min="0" step="1000" required>
            </div>
            <div class="col-md-2">
                <select name="sizes[${sizeIndex}][is_active]" class="form-control form-control-sm">
                    <option value="1" selected>Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm remove-size">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
            container.appendChild(newRow);
            sizeIndex++;
            updateRemoveButtons();
        });

        document.getElementById('sizes-container').addEventListener('click', function(e) {
            if (e.target.closest('.remove-size')) {
                e.target.closest('.size-row').remove();
                updateRemoveButtons();
            }
        });

        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.size-row');
            rows.forEach((row, index) => {
                const btn = row.querySelector('.remove-size');
                btn.disabled = rows.length === 1;
            });
        }

        updateRemoveButtons();

        // ===== BOM ROWS =====
        let bomIndex = {{ $package->bomItems->count() }};

        const itemOptions = `{!! collect($items)->map(
                fn($i) => '<option value="' .
                    $i->id .
                    '" data-uom="' .
                    ($i->smallestUom->code ?? '-') .
                    '">[' .
                    e($i->code) .
                    '] ' .
                    e($i->name) .
                    '</option>',
            )->implode('') !!}`;
        const uomOptions = `{!! collect($uoms)->map(fn($u) => '<option value="' . $u->id . '">' . e($u->name) . ' (' . e($u->code) . ')</option>')->implode('') !!}`;

        document.getElementById('add-bom').addEventListener('click', function() {
            const container = document.getElementById('bom-container');
            const row = document.createElement('div');
            row.className = 'bom-row card mb-2 border-left-info';
            row.innerHTML = `
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-md-5">
                        <div class="form-group mb-0">
                            <label class="small">Item</label>
                            <select name="bom[${bomIndex}][item_id]" class="form-control form-control-sm">
                                <option value="">Select Item</option>${itemOptions}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label class="small">UOM</label>
                            <select name="bom[${bomIndex}][uom_id]" class="form-control form-control-sm">
                                <option value="">Select UOM</option>${uomOptions}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group mb-0">
                            <label class="small">Qty</label>
                            <input type="number" name="bom[${bomIndex}][quantity]" class="form-control form-control-sm"
                                step="0.001" min="0.001" placeholder="0.000">
                        </div>
                    </div>
                    <div class="col-md-1 mt-3">
                        <button type="button" class="btn btn-danger btn-sm remove-bom">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>`;
            container.appendChild(row);
            bomIndex++;
        });

        document.getElementById('bom-container').addEventListener('click', function(e) {
            if (e.target.closest('.remove-bom')) {
                e.target.closest('.bom-row').remove();
            }
        });
    </script>
@endsection
