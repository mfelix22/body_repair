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
                                            @forelse ($workOrder->panelLabors->where('is_extra', false) as $wl)
                                                <span class="badge badge-secondary">{{ $wl->panel?->panel_code ?? $wl->description }}</span>
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

                        {{-- Section-based Materials --}}
                        @php
                            $sections = [
                                'A' => 'DEMPUL',
                                'B' => 'CAT',
                                'C' => 'VERNIS',
                                'D' => 'POLES dan KEBERSIHAN AKHIR',
                                'E' => 'SPAREPART',
                            ];
                        @endphp

                        @foreach ($sections as $sectionKey => $sectionLabel)
                        <div class="card mb-3 section-card" id="section-card-{{ $sectionKey }}">
                            <div class="card-header py-2" style="background:{{ $sectionKey === 'E' ? '#fff3cd' : '#f8f9fa' }};">
                                <strong>{{ $sectionKey }}. &nbsp; {{ $sectionLabel }}</strong>
                                @if ($sectionKey === 'E')
                                    <small class="text-muted ml-2">— Sparepart used to replace parts (e.g. bumper). Always billed to the customer.</small>
                                @endif
                                <button type="button" class="btn btn-success btn-xs float-right add-section-btn"
                                    data-section="{{ $sectionKey }}">
                                    <i class="fas fa-plus"></i> Add Material
                                </button>
                            </div>
                            <div class="card-body p-2">
                                <div class="section-items-container" id="section-items-{{ $sectionKey }}">
                                    <p class="text-muted text-center small py-2 empty-section-msg">No materials added yet.</p>
                                </div>
                                <div class="d-flex justify-content-end mt-1">
                                    <span class="text-muted small mr-2">Subtotal Section {{ $sectionKey }}:</span>
                                    <strong class="section-subtotal" id="subtotal-{{ $sectionKey }}">Rp 0</strong>
                                </div>
                            </div>
                        </div>
                        @endforeach
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

    {{-- Hidden item row template --}}
    <template id="materialRowTemplate">
        <div class="border rounded p-2 mb-2 material-row bg-white">
            <input type="hidden" name="items[__INDEX__][bon_out_section]" value="__SECTION__">
            <div class="row align-items-end">
                <div class="col-md-5">
                    <label class="small mb-1">Material <span class="text-danger">*</span></label>
                    <select class="form-control form-control-sm select2-material material-select"
                        name="items[__INDEX__][item_id]" data-index="__INDEX__" required>
                        <option value="">-- Select Material --</option>
                        @foreach ($allItems as $item)
                            <option value="{{ $item->id }}"
                                data-type="{{ $item->item_type }}"
                                data-uom="{{ $item->smallestUom->code ?? '-' }}"
                                data-stock="{{ $item->stocks->sum('quantity') }}">
                                [{{ $item->code }}] {{ $item->name }}
                                (Stock: {{ number_format($item->stocks->sum('quantity'), 2) }} {{ $item->smallestUom->code ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small mb-1">Stock Available</label>
                    <input type="text" class="form-control form-control-sm stock-display" readonly value="-">
                </div>
                <div class="col-md-2">
                    <label class="small mb-1">Qty Used <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control qty-input"
                            name="items[__INDEX__][actual_quantity]" step="0.01" min="0.01" required>
                        <div class="input-group-append">
                            <span class="input-group-text uom-label">-</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="small mb-1 price-label">Selling Price <small class="text-muted price-optional-hint">(optional)</small></label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                        <input type="number" class="form-control price-input"
                            name="items[__INDEX__][unit_price]" step="1" min="0" placeholder="0">
                    </div>
                </div>
                <div class="col-md-1 text-right">
                    <label class="small mb-1">&nbsp;</label>
                    <button type="button" class="btn btn-sm btn-danger btn-block remove-row-btn">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </template>
@endsection

@section('scripts')
    <script>
        let materialIndex = 0;

        const fmtRp = v => 'Rp ' + parseFloat(v || 0).toLocaleString('id-ID');

        function updateSectionSubtotal(section) {
            let total = 0;
            document.querySelectorAll(`#section-items-${section} .material-row`).forEach(row => {
                const qty   = parseFloat(row.querySelector('.qty-input')?.value   || 0);
                const price = parseFloat(row.querySelector('.price-input')?.value || 0);
                total += qty * price;
            });
            document.getElementById(`subtotal-${section}`).textContent = fmtRp(total);
        }

        function updateItemCount() {
            const count = document.querySelectorAll('.material-row').length;
            document.getElementById('itemCount').textContent = count;
        }

        function addMaterialRow(section) {
            const template = document.getElementById('materialRowTemplate');
            const html = template.innerHTML
                .replace(/__INDEX__/g, materialIndex)
                .replace(/__SECTION__/g, section);

            const wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            const row = wrapper.firstElementChild;

            const container = document.getElementById(`section-items-${section}`);
            // Hide empty message
            const emptyMsg = container.querySelector('.empty-section-msg');
            if (emptyMsg) emptyMsg.style.display = 'none';

            // Section E (Sparepart) — restrict material options to Sparepart items only,
            // and require a selling price since these are always billed to the customer.
            if (section === 'E') {
                row.querySelectorAll('.material-select option').forEach(opt => {
                    if (opt.value !== '' && opt.dataset.type !== 'SP') {
                        opt.remove();
                    }
                });
                const priceInput = row.querySelector('.price-input');
                priceInput.setAttribute('required', 'required');
                priceInput.setAttribute('min', '1');
                const hint = row.querySelector('.price-optional-hint');
                if (hint) hint.textContent = '(required — billed to customer)';
                const priceLabel = row.querySelector('.price-label');
                if (priceLabel) {
                    const star = document.createElement('span');
                    star.className = 'text-danger';
                    star.textContent = ' *';
                    priceLabel.appendChild(star);
                }
            }

            container.appendChild(row);

            // Init Select2
            const $sel = $(row).find('.select2-material');
            $sel.select2({ theme: 'bootstrap4', width: '100%' });
            $sel.on('select2:select', function(e) {
                const opt = e.params.data.element;
                const uom   = opt?.dataset?.uom   || '-';
                const stock = opt?.dataset?.stock  || '0';
                row.querySelector('.stock-display').value = parseFloat(stock).toFixed(2) + ' ' + uom;
                row.querySelector('.uom-label').textContent = uom;
                row.querySelector('.qty-input').setAttribute('max', stock);
            });

            // Recalculate on qty/price change
            row.addEventListener('input', () => updateSectionSubtotal(section));

            materialIndex++;
            updateItemCount();
        }

        // Section "Add Material" buttons
        document.querySelectorAll('.add-section-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                addMaterialRow(this.dataset.section);
            });
        });

        // Remove row
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.remove-row-btn');
            if (!btn) return;
            const row     = btn.closest('.material-row');
            const section = row.querySelector('input[name*="bon_out_section"]')?.value;
            row.remove();
            if (section) updateSectionSubtotal(section);
            updateItemCount();
        });

        // Form validation
        document.getElementById('bonOutForm').addEventListener('submit', function(e) {
            if (document.querySelectorAll('.material-row').length === 0) {
                e.preventDefault();
                alert('Please add at least one material in any section.');
            }
        });
    </script>
@endsection
