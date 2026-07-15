@extends('layouts.admin')
@section('title', 'Edit Bon Out')
@section('page_title', 'Edit Bon Out: ' . $bonOut->bon_out_number)

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Bon Out – <strong>{{ $bonOut->bon_out_number }}</strong></h3>
                    <div class="card-tools">
                        <a href="{{ route('bon_outs.show', $bonOut) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <form action="{{ route('bon_outs.update', $bonOut) }}" method="POST" id="editBonOutForm">
                    @csrf
                    @method('PUT')

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

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Edit Mode:</strong> You can adjust actual usage quantities for existing items and add
                            new items. Stock will not be deducted until you click <strong>Complete</strong>.
                        </div>

                        {{-- Bon Out Info --}}
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered">
                                    <tr>
                                        <th width="40%">Bon Out #</th>
                                        <td><strong>{{ $bonOut->bon_out_number }}</strong></td>
                                    </tr>
                                    @if ($bonOut->workOrder)
                                        <tr>
                                            <th>Work Order</th>
                                            <td>{{ $bonOut->workOrder->wo_number }}</td>
                                        </tr>
                                        <tr>
                                            <th>Customer</th>
                                            <td>{{ $bonOut->workOrder->customer->name ?? '-' }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th>Issued Date</th>
                                        <td>{{ $bonOut->issued_date->format('d M Y') }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $bonOut->notes) }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Materials Used --}}
                        <h5><i class="fas fa-boxes"></i> Materials Used</h5>

                        {{-- Existing saved items --}}
                        @foreach ($bonOut->items as $idx => $bi)
                            @php $uomCode = $bi->item->smallestUom->code ?? '-'; @endphp
                            <input type="hidden" name="items[{{ $idx }}][bon_out_item_id]" value="{{ $bi->id }}">
                            <input type="hidden" name="items[{{ $idx }}][item_id]" value="{{ $bi->item_id }}">
                            <input type="hidden" name="items[{{ $idx }}][work_order_item_id]" value="{{ $bi->work_order_item_id }}">
                            <div class="card mb-2 border-left-primary">
                                <div class="card-body p-3">
                                    <div class="row align-items-end">
                                        <div class="col-md-5">
                                            <label class="small mb-1"><strong>Item</strong></label>
                                            <div class="form-control form-control-sm bg-light">
                                                <strong>[{{ $bi->item->code }}]</strong> {{ $bi->item->name }}
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="small mb-1"><strong>Actual Qty</strong></label>
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="items[{{ $idx }}][actual_quantity]"
                                                    class="form-control text-right" step="0.01" min="0"
                                                    value="{{ old("items.{$idx}.actual_quantity", $bi->actual_quantity) }}">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">{{ $uomCode }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="small mb-1"><strong>Stock</strong></label>
                                            <div class="form-control-plaintext text-muted small">
                                                {{ number_format((float) $bi->item->stocks->sum('quantity'), 2) }} {{ $uomCode }}
                                            </div>
                                        </div>
                                        @if ($bonOut->bon_out_type != 3)
                                            <div class="col-md-2">
                                                <label class="small mb-1"><strong>Selling Price</strong></label>
                                                <input type="number" name="items[{{ $idx }}][unit_price]"
                                                    class="form-control form-control-sm" step="0.01" min="0"
                                                    value="{{ old("items.{$idx}.unit_price", $bi->unit_price ?? 0) }}"
                                                    placeholder="0 = internal">
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Dynamically added items --}}
                        <div id="newItemsContainer"></div>

                        @if ($bonOut->bon_out_type != 3)
                            <button type="button" class="btn btn-success btn-sm mt-2" id="addNewItemBtn">
                                <i class="fas fa-plus"></i> Add Material
                            </button>
                        @endif
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="{{ route('bon_outs.show', $bonOut) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const existingItemCount = {{ $bonOut->items->count() }};
        let newItemIndex = existingItemCount;

        @php
            $allItemsJson = $allItems
                ->map(function ($i) {
                    return [
                        'id' => $i->id,
                        'code' => $i->code,
                        'name' => $i->name,
                        'uom' => $i->smallestUom->code ?? '-',
                        'stock' => $i->stocks->sum('quantity'),
                    ];
                })
                ->values();
        @endphp
        const allItems = @json($allItemsJson);

        function buildItemOptions() {
            return allItems.map(i =>
                `<option value="${i.id}" data-uom="${i.uom}" data-stock="${i.stock}">[${i.code}] ${i.name}</option>`
            ).join('');
        }

        document.getElementById('addNewItemBtn')?.addEventListener('click', function() {
            const container = document.getElementById('newItemsContainer');
            const idx = newItemIndex++;
            const div = document.createElement('div');
            div.className = 'card mb-2 border-left-success';
            div.innerHTML = `
            <div class="card-body p-3">
                <div class="row align-items-end">
                    <input type="hidden" name="items[${idx}][bon_out_item_id]" value="">
                    <input type="hidden" name="items[${idx}][work_order_item_id]" value="">
                    <div class="col-md-6">
                        <label class="small mb-1"><strong>Item <span class="text-danger">*</span></strong></label>
                        <select name="items[${idx}][item_id]" class="form-control form-control-sm new-item-select" required>
                            <option value="">-- Pilih Item --</option>
                            ${buildItemOptions()}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small mb-1"><strong>Actual Qty <span class="text-danger">*</span></strong></label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="items[${idx}][actual_quantity]"
                                class="form-control text-right" step="0.01" min="0.01" value="0" required>
                            <div class="input-group-append">
                                <span class="input-group-text uom-label">-</span>
                            </div>
                        </div>
                        <small class="text-muted stock-info"></small>
                    </div>
                    <div class="col-md-1 mt-3">
                        <button type="button" class="btn btn-danger btn-sm remove-new-item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>`;

            container.appendChild(div);

            // Init Select2 on the new select
            $(div).find('.new-item-select').select2({
                theme: 'bootstrap4',
                placeholder: '-- Select Item --',
                allowClear: true,
                width: '100%'
            }).on('select2:select', function(e) {
                const opt = this.options[this.selectedIndex];
                const uom = opt.dataset.uom || '-';
                const stock = parseFloat(opt.dataset.stock || 0).toFixed(2);
                const row = this.closest('.card-body');
                row.querySelector('.uom-label').textContent = uom;
                row.querySelector('.stock-info').textContent = `Stock: ${stock} ${uom}`;
            });

            div.querySelector('.remove-new-item').addEventListener('click', function() {
                div.remove();
            });
        });
    </script>
@endpush
