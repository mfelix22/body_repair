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

                        {{-- Materials Used (grouped by section) --}}
                        <h5><i class="fas fa-boxes"></i> Materials Used</h5>
                        @php
                            $sections = [
                                'A' => 'DEMPUL',
                                'B' => 'CAT',
                                'C' => 'VERNIS',
                                'D' => 'POLES dan KEBERSIHAN AKHIR',
                            ];
                            $groupedExisting = $bonOut->items->groupBy(fn($i) => $i->bon_out_section ?? 'Unsorted');
                            $existingCount   = $bonOut->items->count();
                        @endphp

                        @foreach ($sections as $sectionKey => $sectionLabel)
                        <div class="card mb-3 section-card" id="section-card-{{ $sectionKey }}">
                            <div class="card-header py-2" style="background:#f8f9fa;">
                                <strong>{{ $sectionKey }}. &nbsp; {{ $sectionLabel }}</strong>
                                @if ($bonOut->bon_out_type != 3)
                                <button type="button" class="btn btn-success btn-xs float-right add-section-btn"
                                    data-section="{{ $sectionKey }}">
                                    <i class="fas fa-plus"></i> Add Material
                                </button>
                                @endif
                            </div>
                            <div class="card-body p-2">
                                <div class="section-items-container" id="section-items-{{ $sectionKey }}">

                                    {{-- Existing saved items for this section --}}
                                    @forelse ($groupedExisting[$sectionKey] ?? [] as $bi)
                                        @php
                                            $idx     = $loop->parent->index ?? $bi->id;
                                            $uomCode = $bi->item->smallestUom->code ?? '-';
                                        @endphp
                                        <div class="border rounded p-2 mb-2 existing-item-row bg-white">
                                            <input type="hidden" name="items[{{ $bi->id }}][bon_out_item_id]"     value="{{ $bi->id }}">
                                            <input type="hidden" name="items[{{ $bi->id }}][item_id]"             value="{{ $bi->item_id }}">
                                            <input type="hidden" name="items[{{ $bi->id }}][work_order_item_id]"  value="{{ $bi->work_order_item_id }}">
                                            <input type="hidden" name="items[{{ $bi->id }}][bon_out_section]"     value="{{ $sectionKey }}" class="section-hidden">
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
                                                        <input type="number" name="items[{{ $bi->id }}][actual_quantity]"
                                                            class="form-control text-right qty-input" step="0.01" min="0"
                                                            value="{{ old("items.{$bi->id}.actual_quantity", $bi->actual_quantity) }}">
                                                        <div class="input-group-append">
                                                            <span class="input-group-text">{{ $uomCode }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="small mb-1"><strong>Stock</strong></label>
                                                    <div class="form-control-plaintext text-muted small pt-1">
                                                        {{ number_format((float) $bi->item->stocks->sum('quantity'), 2) }} {{ $uomCode }}
                                                    </div>
                                                </div>
                                                @if ($bonOut->bon_out_type != 3)
                                                <div class="col-md-2">
                                                    <label class="small mb-1"><strong>Selling Price</strong></label>
                                                    <input type="number" name="items[{{ $bi->id }}][unit_price]"
                                                        class="form-control form-control-sm price-input" step="0.01" min="0"
                                                        value="{{ old("items.{$bi->id}.unit_price", $bi->unit_price ?? 0) }}"
                                                        placeholder="0 = internal">
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-muted text-center small py-2 empty-section-msg" id="empty-msg-{{ $sectionKey }}">No materials in this section.</p>
                                    @endforelse

                                    {{-- Container for dynamically added rows --}}
                                    <div class="new-items-subcontainer" id="new-items-{{ $sectionKey }}"></div>

                                </div>
                                <div class="d-flex justify-content-end mt-1">
                                    <span class="text-muted small mr-2">Subtotal Section {{ $sectionKey }}:</span>
                                    <strong class="section-subtotal" id="subtotal-{{ $sectionKey }}">Rp 0</strong>
                                </div>
                            </div>
                        </div>
                        @endforeach

                        {{-- Unsorted items (legacy items with no section) --}}
                        @if (($groupedExisting['Unsorted'] ?? collect())->isNotEmpty())
                        <div class="card mb-3 border-warning">
                            <div class="card-header py-2" style="background:#fff3cd;">
                                <strong>Unsorted (no section assigned)</strong>
                                <small class="text-muted ml-2">— please reassign these items</small>
                            </div>
                            <div class="card-body p-2">
                                @foreach ($groupedExisting['Unsorted'] as $bi)
                                    @php $uomCode = $bi->item->smallestUom->code ?? '-'; @endphp
                                    <div class="border rounded p-2 mb-2 existing-item-row bg-white">
                                        <input type="hidden" name="items[{{ $bi->id }}][bon_out_item_id]"    value="{{ $bi->id }}">
                                        <input type="hidden" name="items[{{ $bi->id }}][item_id]"            value="{{ $bi->item_id }}">
                                        <input type="hidden" name="items[{{ $bi->id }}][work_order_item_id]" value="{{ $bi->work_order_item_id }}">
                                        <div class="row align-items-end">
                                            <div class="col-md-4">
                                                <label class="small mb-1"><strong>Item</strong></label>
                                                <div class="form-control form-control-sm bg-light">
                                                    <strong>[{{ $bi->item->code }}]</strong> {{ $bi->item->name }}
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small mb-1"><strong>Section</strong></label>
                                                <select name="items[{{ $bi->id }}][bon_out_section]" class="form-control form-control-sm">
                                                    <option value="">— Assign Section —</option>
                                                    @foreach ($sections as $sk => $sl)
                                                        <option value="{{ $sk }}">{{ $sk }}. {{ $sl }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small mb-1"><strong>Actual Qty</strong></label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" name="items[{{ $bi->id }}][actual_quantity]"
                                                        class="form-control text-right" step="0.01" min="0"
                                                        value="{{ old("items.{$bi->id}.actual_quantity", $bi->actual_quantity) }}">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">{{ $uomCode }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            @if ($bonOut->bon_out_type != 3)
                                            <div class="col-md-2">
                                                <label class="small mb-1"><strong>Selling Price</strong></label>
                                                <input type="number" name="items[{{ $bi->id }}][unit_price]"
                                                    class="form-control form-control-sm" step="0.01" min="0"
                                                    value="{{ old("items.{$bi->id}.unit_price", $bi->unit_price ?? 0) }}"
                                                    placeholder="0 = internal">
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
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
        // New item index starts after all existing items to avoid key collisions
        let newItemIndex = 9000;

        @php
            $allItemsJson = $allItems
                ->map(fn($i) => [
                    'id'    => $i->id,
                    'code'  => $i->code,
                    'name'  => $i->name,
                    'uom'   => $i->smallestUom->code ?? '-',
                    'stock' => $i->stocks->sum('quantity'),
                ])
                ->values();
        @endphp
        const allItems = @json($allItemsJson);

        const fmtRp = v => 'Rp ' + parseFloat(v || 0).toLocaleString('id-ID');

        function updateSectionSubtotal(section) {
            let total = 0;
            // Include existing rows
            document.querySelectorAll(`#section-items-${section} .qty-input, #section-items-${section} .price-input`);
            document.querySelectorAll(`#section-items-${section} .existing-item-row, #section-items-${section} .new-material-row`).forEach(row => {
                const qty   = parseFloat(row.querySelector('.qty-input')?.value   || 0);
                const price = parseFloat(row.querySelector('.price-input')?.value || 0);
                total += qty * price;
            });
            document.getElementById(`subtotal-${section}`).textContent = fmtRp(total);
        }

        function buildItemOptions() {
            return allItems.map(i =>
                `<option value="${i.id}" data-uom="${i.uom}" data-stock="${i.stock}">[${i.code}] ${i.name} (Stock: ${parseFloat(i.stock).toFixed(2)} ${i.uom})</option>`
            ).join('');
        }

        function addMaterialRow(section) {
            const idx       = newItemIndex++;
            const container = document.getElementById(`new-items-${section}`);

            // Hide the "empty" placeholder if present
            const emptyMsg = document.getElementById(`empty-msg-${section}`);
            if (emptyMsg) emptyMsg.style.display = 'none';

            const div = document.createElement('div');
            div.className = 'border rounded p-2 mb-2 new-material-row bg-white';
            div.innerHTML = `
                <input type="hidden" name="items[${idx}][bon_out_item_id]"    value="">
                <input type="hidden" name="items[${idx}][work_order_item_id]" value="">
                <input type="hidden" name="items[${idx}][bon_out_section]"    value="${section}">
                <div class="row align-items-end">
                    <div class="col-md-5">
                        <label class="small mb-1"><strong>Material <span class="text-danger">*</span></strong></label>
                        <select name="items[${idx}][item_id]" class="form-control form-control-sm new-item-select" required>
                            <option value="">-- Select Material --</option>
                            ${buildItemOptions()}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="small mb-1"><strong>Stock</strong></label>
                        <input type="text" class="form-control form-control-sm stock-display" readonly value="-">
                    </div>
                    <div class="col-md-2">
                        <label class="small mb-1"><strong>Qty <span class="text-danger">*</span></strong></label>
                        <div class="input-group input-group-sm">
                            <input type="number" name="items[${idx}][actual_quantity]"
                                class="form-control text-right qty-input" step="0.01" min="0.01" value="" required>
                            <div class="input-group-append">
                                <span class="input-group-text uom-label">-</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="small mb-1"><strong>Selling Price</strong></label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                            <input type="number" name="items[${idx}][unit_price]"
                                class="form-control price-input" step="1" min="0" placeholder="0">
                        </div>
                    </div>
                    <div class="col-md-1 text-right">
                        <label class="small mb-1">&nbsp;</label>
                        <button type="button" class="btn btn-sm btn-danger btn-block remove-new-item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>`;

            container.appendChild(div);

            // Init Select2
            $(div).find('.new-item-select').select2({
                theme: 'bootstrap4',
                placeholder: '-- Select Material --',
                allowClear: true,
                width: '100%'
            }).on('select2:select', function(e) {
                const opt   = this.options[this.selectedIndex];
                const uom   = opt?.dataset?.uom   || '-';
                const stock = parseFloat(opt?.dataset?.stock || 0).toFixed(2);
                div.querySelector('.stock-display').value = stock + ' ' + uom;
                div.querySelector('.uom-label').textContent = uom;
                div.querySelector('.qty-input').setAttribute('max', opt?.dataset?.stock || '');
                updateSectionSubtotal(section);
            });

            div.addEventListener('input', () => updateSectionSubtotal(section));
        }

        // Section "Add Material" buttons
        document.querySelectorAll('.add-section-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                addMaterialRow(this.dataset.section);
            });
        });

        // Remove new item rows
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.remove-new-item');
            if (!btn) return;
            const row     = btn.closest('.new-material-row');
            const section = row?.querySelector('input[name*="bon_out_section"]')?.value;
            row?.remove();
            if (section) updateSectionSubtotal(section);
        });

        // Live subtotal update for existing items when qty/price changes
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('qty-input') || e.target.classList.contains('price-input')) {
                const sectionCard = e.target.closest('.section-card');
                if (sectionCard) {
                    const sKey = sectionCard.id.replace('section-card-', '');
                    updateSectionSubtotal(sKey);
                }
            }
        });
    </script>
@endpush
