@extends('layouts.admin')
@section('title', 'Create Bon Out')
@section('page_title', 'Create Bon Out for WO ' . $workOrder->wo_number)

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bon Out — <strong>{{ $workOrder->wo_number }}</strong></h3>
                    <div class="card-tools">
                        <a href="{{ route('work_orders.show', $workOrder) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to WO
                        </a>
                    </div>
                </div>

                <form action="{{ route('bon_outs.store') }}" method="POST" id="bonOutForm">
                    @csrf
                    <input type="hidden" name="work_order_id" value="{{ $workOrder->id }}">

                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger">{!! session('error') !!}</div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- WO Summary --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered">
                                    <tr>
                                        <th width="40%">WO Number</th>
                                        <td>{{ $workOrder->wo_number }}</td>
                                    </tr>
                                    <tr>
                                        <th>Customer</th>
                                        <td>{{ $workOrder->customer->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Panels</th>
                                        <td>
                                            @forelse ($workOrder->labors->where('is_extra', false) as $wl)
                                                <span class="badge badge-secondary">{{ $wl->labor->labor_code ?? $wl->description }}</span>
                                            @empty
                                                -
                                            @endforelse
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Vehicle</th>
                                        <td>{{ $workOrder->vehicle_plate ?? '-' }} {{ $workOrder->vehicle_merk ?? '' }}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bon_out_type">Bon Out Type <span class="text-danger">*</span></label>
                                    <select name="bon_out_type" id="bon_out_type" class="form-control select2" required>
                                        <option value="1" {{ old('bon_out_type', 1) == 1 ? 'selected' : '' }}>Workshop
                                            Materials (from WO)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3"
                                        placeholder="Day 1 work, Day 2 work, etc...">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Materials Used --}}
                        <h5><i class="fas fa-boxes"></i> Materials Used</h5>
                        <div id="newMaterialsContainer"></div>
                        <button type="button" class="btn btn-success btn-sm mt-2" id="addNewMaterialBtn">
                            <i class="fas fa-plus"></i> Add Material
                        </button>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Bon Out
                        </button>
                        <a href="{{ route('work_orders.show', $workOrder) }}" class="btn btn-secondary">Cancel</a>

                        <div class="float-right">
                            <span class="badge badge-info">Items to save: <span id="itemCount">0</span></span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Hidden template for new material row --}}
    <template id="newMaterialTemplate">
        <div class="card mb-2 new-material-item">
            <div class="card-body p-3">
                <div class="row mb-2">
                    <div class="col-md-11">
                        <label>Material <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm select2-new-material material-select"
                            data-index="__INDEX__" required>
                            <option value="">-- Select Material --</option>
                            @foreach ($allItems as $item)
                                <option value="{{ $item->id }}" data-code="{{ $item->code }}"
                                    data-name="{{ $item->name }}" data-uom="{{ $item->smallestUom->code ?? '-' }}"
                                    data-stock="{{ $item->stocks->sum('quantity') }}">
                                    [{{ $item->code }}] {{ $item->name }} (Stock:
                                    {{ number_format($item->stocks->sum('quantity'), 2) }}
                                    {{ $item->smallestUom->code ?? '-' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-sm btn-danger btn-block remove-material-btn">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label>Available Stock</label>
                        <input type="text" class="form-control form-control-sm available-stock-display" readonly
                            value="-">
                    </div>
                    <div class="col-md-4">
                        <label>Quantity <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control new-qty-input" step="0.01" min="0.01"
                                value="" data-index="__INDEX__" data-field="actual_quantity" required>
                            <div class="input-group-append">
                                <span class="input-group-text uom-display">-</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label>Selling Price (Rp) <small class="text-muted">optional</small></label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                            <input type="number" name="items[__INDEX__][unit_price]" data-index="__INDEX__"
                                data-field="unit_price" class="form-control new-price-input" step="1"
                                min="0" value="" placeholder="0 = internal use only">
                        </div>
                        <small class="text-info"><i class="fas fa-info-circle"></i> If set, item is billed to
                            customer</small>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endsection

@section('scripts')
    <script>
        let newMaterialIndex = 0;

        function updateItemCount() {
            const count = document.querySelectorAll('.new-material-item').length;
            document.getElementById('itemCount').textContent = count;
        }

        // Add new material button
        document.getElementById('addNewMaterialBtn').addEventListener('click', function() {
            const template = document.getElementById('newMaterialTemplate');
            const clone = template.content.cloneNode(true);

            // Replace __INDEX__ with actual index
            clone.querySelectorAll('[data-index="__INDEX__"]').forEach(el => {
                el.setAttribute('data-index', newMaterialIndex);
                if (el.tagName === 'SELECT') {
                    el.setAttribute('name', `items[${newMaterialIndex}][item_id]`);
                    el.classList.remove('select2-new-material');
                    el.classList.add('select2-material-' + newMaterialIndex);
                } else if (el.tagName === 'INPUT') {
                    const field = el.dataset.field || 'actual_quantity';
                    el.setAttribute('name', `items[${newMaterialIndex}][${field}]`);
                }
            });

            document.getElementById('newMaterialsContainer').appendChild(clone);

            // Initialize Select2 for new select
            const $select = $('.select2-material-' + newMaterialIndex);
            $select.select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            // Attach change handler specifically for this Select2 element
            $select.on('select2:select', function(e) {
                const option = e.params.data.element;
                const container = $(this).closest('.new-material-item')[0];
                const stockDisplay = container.querySelector('.available-stock-display');
                const uomDisplay = container.querySelector('.uom-display');
                const qtyInput = container.querySelector('.new-qty-input');

                if (option && option.value) {
                    const stock = option.dataset.stock;
                    const uom = option.dataset.uom;

                    stockDisplay.value = parseFloat(stock).toFixed(2) + ' ' + uom;
                    uomDisplay.textContent = uom;
                    qtyInput.setAttribute('max', stock);
                    qtyInput.dataset.available = stock;
                } else {
                    stockDisplay.value = '-';
                    uomDisplay.textContent = '-';
                    qtyInput.removeAttribute('max');
                }
            });

            newMaterialIndex++;
            updateItemCount();
        });

        // Remove material button
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-material-btn')) {
                e.target.closest('.new-material-item').remove();
                updateItemCount();
            }
        });

        // Form validation
        document.getElementById('bonOutForm').addEventListener('submit', function(e) {
            const count = parseInt(document.getElementById('itemCount').textContent);

            if (count === 0) {
                e.preventDefault();
                alert('Please enter at least one material with quantity greater than zero.');
                return false;
            }
        });

        // Initialize item count
        updateItemCount();
    </script>
@endsection
