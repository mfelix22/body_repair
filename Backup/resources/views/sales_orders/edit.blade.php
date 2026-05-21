@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@section('title', 'Edit ' . $salesOrder->so_number)
@section('page_title', 'Edit Sales Order')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit {{ $salesOrder->so_number }}</h3>
                </div>

                <form action="{{ route('sales_orders.update', $salesOrder) }}" method="POST" id="soForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row">
                            {{-- Left column --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>SO Number</label>
                                    <input type="text" class="form-control" value="{{ $salesOrder->so_number }}"
                                        readonly>
                                </div>

                                <div class="form-group">
                                    <label for="customer_id">Customer <span class="text-danger">*</span></label>
                                    <select name="customer_id" id="customer_id"
                                        class="form-control select2 @error('customer_id') is-invalid @enderror" required>
                                        <option value="">— Select Customer —</option>
                                        @foreach ($customers as $c)
                                            <option value="{{ $c->id }}"
                                                {{ old('customer_id', $salesOrder->customer_id) == $c->id ? 'selected' : '' }}>
                                                {{ $c->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="order_date">Order Date <span class="text-danger">*</span></label>
                                    <input type="date" name="order_date" id="order_date"
                                        class="form-control @error('order_date') is-invalid @enderror"
                                        value="{{ old('order_date', $salesOrder->order_date->format('Y-m-d')) }}" required>
                                    @error('order_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <input type="text" name="description" id="description"
                                        class="form-control @error('description') is-invalid @enderror"
                                        placeholder="Short description (optional)"
                                        value="{{ old('description', $salesOrder->description) }}">
                                    @error('description')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $salesOrder->notes) }}</textarea>
                                </div>
                            </div>

                            {{-- Right column — items --}}
                            <div class="col-md-8">
                                <h6><i class="fas fa-boxes"></i> Spare Parts / Items</h6>
                                @error('items')
                                    <div class="alert alert-danger py-2">{{ $message }}</div>
                                @enderror

                                <table class="table table-sm table-bordered" id="itemsTable">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Item</th>
                                            <th style="width:110px">Qty</th>
                                            <th style="width:150px">Unit Price (Rp)</th>
                                            <th style="width:150px">Total (Rp)</th>
                                            <th style="width:36px"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBody">
                                        @php
                                            $existingItems = old('items')
                                                ? collect(old('items'))->map(fn($i) => (object) $i)
                                                : $salesOrder->items->map(
                                                    fn($line) => (object) [
                                                        'item_id' => $line->item_id,
                                                        'quantity' => $line->quantity,
                                                        'unit_price' => $line->unit_price,
                                                    ],
                                                );
                                        @endphp
                                        @foreach ($existingItems as $idx => $row)
                                            <tr class="item-row">
                                                <td>
                                                    <select name="items[{{ $idx }}][item_id]"
                                                        class="form-control form-control-sm select2-item @error('items.' . $idx . '.item_id') is-invalid @enderror"
                                                        required>
                                                        <option value="">— Select Item —</option>
                                                        @foreach ($items as $item)
                                                            <option value="{{ $item->id }}"
                                                                {{ $row->item_id == $item->id ? 'selected' : '' }}>
                                                                {{ $item->code }} — {{ $item->name }}
                                                                ({{ $item->smallestUom->code ?? '' }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" name="items[{{ $idx }}][quantity]"
                                                        class="form-control form-control-sm qty-input"
                                                        value="{{ $row->quantity }}" min="0.01" step="0.01"
                                                        required>
                                                </td>
                                                <td>
                                                    <input type="number" name="items[{{ $idx }}][unit_price]"
                                                        class="form-control form-control-sm price-input"
                                                        value="{{ $row->unit_price }}" min="0" step="1"
                                                        required>
                                                </td>
                                                <td class="text-right row-total align-middle">
                                                    {{ number_format((float) $row->quantity * (float) $row->unit_price, 0, ',', '.') }}
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-danger btn-xs remove-row">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5">
                                                <button type="button" class="btn btn-secondary btn-sm" id="addItemRow">
                                                    <i class="fas fa-plus"></i> Add Item
                                                </button>
                                            </td>
                                        </tr>
                                        <tr class="table-success">
                                            <td colspan="3" class="text-right font-weight-bold">Grand Total</td>
                                            <td class="text-right font-weight-bold" id="grandTotal">0</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="{{ route('sales_orders.show', $salesOrder) }}" class="btn btn-secondary ml-2">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @php
        $allItemsData = $items
            ->map(
                fn($i) => [
                    'id' => $i->id,
                    'text' => $i->code . ' — ' . $i->name . ' (' . ($i->smallestUom->code ?? '') . ')',
                    'selling_price' => (float) ($i->selling_price ?? 0),
                ],
            )
            ->values()
            ->all();
        $canEditPriceData = Auth::user()->hasAnyRole(['super_admin', 'admin', 'accounting']);
    @endphp
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        const allItems = @json($allItemsData);

        const canEditPrice = @json($canEditPriceData);

        let rowIndex = {{ $existingItems->count() }};

        function fmt(n) {
            return Math.round(n).toLocaleString('id-ID');
        }

        function initRow($row) {
            $row.find('.select2-item').select2({
                theme: 'bootstrap4',
                placeholder: '— Select Item —',
                data: [{
                    id: '',
                    text: '— Select Item —'
                }].concat(allItems),
                width: '100%',
            });

            // Auto-fill price when item selected
            $row.find('.select2-item').on('change', function() {
                const itemId = $(this).val();
                const item = allItems.find(i => String(i.id) === String(itemId));
                if (item && !canEditPrice) {
                    $row.find('.price-input').val(item.selling_price);
                    recalcRow($row);
                } else if (item && canEditPrice && !$row.find('.price-input').val()) {
                    $row.find('.price-input').val(item.selling_price);
                    recalcRow($row);
                }
            });
        }

        function recalcRow($row) {
            const qty = parseFloat($row.find('.qty-input').val()) || 0;
            const price = parseFloat($row.find('.price-input').val()) || 0;
            $row.find('.row-total').text(fmt(qty * price));
            recalcGrand();
        }

        function recalcGrand() {
            let grand = 0;
            $('#itemsBody .item-row').each(function() {
                const qty = parseFloat($(this).find('.qty-input').val()) || 0;
                const price = parseFloat($(this).find('.price-input').val()) || 0;
                grand += qty * price;
            });
            $('#grandTotal').text(fmt(grand));
        }

        function reindex() {
            $('#itemsBody .item-row').each(function(i) {
                $(this).find('[name]').each(function() {
                    this.name = this.name.replace(/items\[\d+\]/, 'items[' + i + ']');
                });
            });
        }

        const priceAttrs = canEditPrice ?
            'class="form-control form-control-sm price-input" min="0" step="1" required' :
            'class="form-control form-control-sm price-input" min="0" step="1" required readonly';

        $(function() {
            $('#customer_id').select2({
                theme: 'bootstrap4',
                width: '100%'
            });

            $('#itemsBody .item-row').each(function() {
                if (!canEditPrice) {
                    $(this).find('.price-input').attr('readonly', true);
                }
                initRow($(this));
            });
            recalcGrand();

            $('#addItemRow').on('click', function() {
                const idx = rowIndex++;
                const $row = $(`<tr class="item-row">
                    <td>
                        <select name="items[${idx}][item_id]"
                            class="form-control form-control-sm select2-item" required>
                        </select>
                    </td>
                    <td><input type="number" name="items[${idx}][quantity]"
                        class="form-control form-control-sm qty-input" min="0.01" step="0.01" required></td>
                    <td><input type="number" name="items[${idx}][unit_price]"
                        ${priceAttrs}></td>
                    <td class="text-right row-total align-middle">0</td>
                    <td><button type="button" class="btn btn-danger btn-xs remove-row">
                        <i class="fas fa-times"></i></button></td>
                </tr>`);
                $('#itemsBody').append($row);
                initRow($row);
            });

            $('#itemsBody').on('click', '.remove-row', function() {
                if ($('#itemsBody .item-row').length > 1) {
                    $(this).closest('tr').remove();
                    reindex();
                    recalcGrand();
                }
            });

            $('#itemsBody').on('input change', '.qty-input, .price-input', function() {
                recalcRow($(this).closest('tr'));
            });
        });
    </script>
@endpush
