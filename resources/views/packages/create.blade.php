@extends('layouts.admin')

@section('title', 'Create Product')
@section('page_title', 'Create New Product')

@section('content')
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Product Information</h3>
                </div>

                <form action="{{ route('packages.store') }}" method="POST">
                    @csrf
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
                                        value="{{ old('category') }}"
                                        placeholder="e.g., PRODUCT COATING, PRODUCT ALA-CARTE">
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="code">Code <span class="text-danger">*</span></label>
                                    <input type="text" name="code" id="code"
                                        class="form-control @error('code') is-invalid @enderror" value="{{ old('code') }}"
                                        placeholder="e.g., CLS, SPT" style="text-transform: uppercase;">
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="is_active">Status</label>
                                    <select name="is_active" id="is_active" class="form-control">
                                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active
                                        </option>
                                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="name">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name"
                                class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}"
                                placeholder="e.g., Classic Product">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="2"
                                placeholder="Optional product description">{{ old('description') }}</textarea>
                        </div>

                        <hr>

                        <h5>Product Sizes & Prices</h5>
                        <div id="sizes-container">
                            <div class="size-row row mb-2">
                                <div class="col-md-5">
                                    <input type="text" name="sizes[0][size_name]" class="form-control"
                                        placeholder="e.g., Size S, All, 2 Row" value="{{ old('sizes.0.size_name') }}"
                                        required>
                                </div>
                                <div class="col-md-5">
                                    <input type="number" name="sizes[0][price]" class="form-control" placeholder="Price"
                                        value="{{ old('sizes.0.price') }}" min="0" step="1000" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-block remove-size" disabled>
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
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
                            {{-- BOM items will be added here dynamically --}}
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
                            <i class="fas fa-save"></i> Create Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let sizeIndex = 1;

        document.addEventListener('DOMContentLoaded', function() {
            const addButton = document.getElementById('add-size');
            const sizesContainer = document.getElementById('sizes-container');

            if (addButton) {
                addButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    const newRow = document.createElement('div');
                    newRow.className = 'size-row row mb-2';
                    newRow.innerHTML = `
                        <div class="col-md-5">
                            <input type="text" name="sizes[${sizeIndex}][size_name]" class="form-control"
                                placeholder="e.g., Size S, All, 2 Row" required>
                        </div>
                        <div class="col-md-5">
                            <input type="number" name="sizes[${sizeIndex}][price]" class="form-control"
                                placeholder="Price" min="0" step="1000" required>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-danger btn-block remove-size">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    sizesContainer.appendChild(newRow);
                    sizeIndex++;
                    updateRemoveButtons();
                });
            }

            if (sizesContainer) {
                sizesContainer.addEventListener('click', function(e) {
                    if (e.target.closest('.remove-size')) {
                        e.preventDefault();
                        e.target.closest('.size-row').remove();
                        updateRemoveButtons();
                    }
                });
            }

            function updateRemoveButtons() {
                const rows = document.querySelectorAll('.size-row');
                rows.forEach((row, index) => {
                    const btn = row.querySelector('.remove-size');
                    if (btn) {
                        btn.disabled = rows.length === 1;
                    }
                });
            }

            // Initial update
            updateRemoveButtons();
        });

        // ===== BOM ROWS =====
        let bomIndex = 0;

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
