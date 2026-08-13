@extends('layouts.admin')

@section('title', 'New Estimasi')
@section('page_title', 'New Estimasi')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">New Estimasi for {{ $workOrder->wo_number }}</h3>
                </div>
                <form action="{{ route('estimasis.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="work_order_id" value="{{ $workOrder->id }}">
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

                        <table class="table table-bordered table-sm mb-4">
                            <tr>
                                <th style="width:200px">Work Order</th>
                                <td>{{ $workOrder->wo_number }}</td>
                            </tr>
                            <tr>
                                <th>Customer</th>
                                <td>{{ optional($workOrder->customer)->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Panel + Labor Total</th>
                                <td>Rp {{ number_format($workOrder->grand_total, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <th>Pergantian Sparepart</th>
                                <td id="sparepart-total-display">Rp {{ number_format($sparepartTotal, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <th>Work Order Total</th>
                                <td><strong id="wo-total-display">Rp {{ number_format($estimasiSubtotal, 0, ',', '.') }}</strong></td>
                            </tr>
                        </table>

                        <h6><i class="fas fa-cogs"></i> Sparepart Dibutuhkan (untuk Asuransi)</h6>
                        <p class="text-muted small mb-2">
                            Masukkan manual sparepart yang dibutuhkan untuk pekerjaan ini. Daftar ini akan
                            dicetak pada Estimasi untuk disediakan oleh pihak Asuransi.
                        </p>
                        <table class="table table-sm table-bordered">
                            <thead class="bg-light">
                                <tr>
                                    <th>Nama Sparepart</th>
                                    <th style="width: 100px;">Qty</th>
                                    <th style="width: 150px;">Harga Satuan</th>
                                    <th style="width: 150px;">Jumlah</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="sparepart-items-container">
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-warning btn-sm mb-3" id="add-sparepart-item">
                            <i class="fas fa-plus"></i> Tambah Sparepart
                        </button>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="discount_percentage_panel">Discount Panel (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="discount_percentage_panel"
                                    id="discount_percentage_panel" class="form-control"
                                    value="{{ old('discount_percentage_panel', 0) }}"
                                    data-subtotal="{{ $workOrder->grand_total }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="discount_percentage_sparepart">Discount Sparepart (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="discount_percentage_sparepart"
                                    id="discount_percentage_sparepart" class="form-control"
                                    value="{{ old('discount_percentage_sparepart', 0) }}"
                                    data-subtotal="{{ $sparepartTotal }}">
                            </div>
                        </div>
                        <small class="form-text text-muted mb-3 d-block">
                            Leave both at 0 for a plain estimate with no discount (no approval required).<br>
                            Overall discount &le; 20% of the Work Order Total requires <strong>Manager</strong> approval only.
                            &gt; 20% requires <strong>Manager + Director</strong> approval.
                        </small>

                        <table class="table table-bordered table-sm" style="max-width:420px;">
                            <tr>
                                <th>Panel Subtotal</th>
                                <td class="text-right" id="preview-panel-subtotal">
                                    Rp {{ number_format($workOrder->grand_total, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <th>Panel Discount</th>
                                <td class="text-right text-danger" id="preview-panel-discount">Rp 0</td>
                            </tr>
                            <tr>
                                <th>Sparepart Subtotal</th>
                                <td class="text-right" id="preview-sparepart-subtotal">
                                    Rp {{ number_format($sparepartTotal, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <th>Sparepart Discount</th>
                                <td class="text-right text-danger" id="preview-sparepart-discount">Rp 0</td>
                            </tr>
                            <tr>
                                <th>Subtotal</th>
                                <td class="text-right" id="preview-subtotal">
                                    Rp {{ number_format($estimasiSubtotal, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <th>Total Discount</th>
                                <td class="text-right text-danger" id="preview-discount">Rp 0</td>
                            </tr>
                            <tr class="font-weight-bold">
                                <th>Total</th>
                                <td class="text-right" id="preview-total">
                                    Rp {{ number_format($estimasiSubtotal, 0, ',', '.') }}</td>
                            </tr>
                        </table>

                        <div class="form-group">
                            <label for="notes">Notes (optional)</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Estimasi
                        </button>
                        <a href="{{ route('work_orders.show', $workOrder) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const panelInput = document.getElementById('discount_percentage_panel');
            const sparepartInput = document.getElementById('discount_percentage_sparepart');
            const panelSubtotal = parseFloat(panelInput.dataset.subtotal) || 0;
            const itemsContainer = document.getElementById('sparepart-items-container');
            const addItemBtn = document.getElementById('add-sparepart-item');
            let itemIndex = 0;

            function fmt(n) {
                return 'Rp ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function clampPct(v) {
                let pct = parseFloat(v) || 0;
                if (pct < 0) pct = 0;
                if (pct > 100) pct = 100;
                return pct;
            }

            function getSparepartSubtotal() {
                let total = 0;
                itemsContainer.querySelectorAll('.sparepart-row').forEach(row => {
                    const qty = parseFloat(row.querySelector('.sparepart-qty').value) || 0;
                    const price = parseFloat(row.querySelector('.sparepart-price').value) || 0;
                    total += qty * price;
                });
                return total;
            }

            function updateRowTotal(row) {
                const qty = parseFloat(row.querySelector('.sparepart-qty').value) || 0;
                const price = parseFloat(row.querySelector('.sparepart-price').value) || 0;
                row.querySelector('.sparepart-row-total').value = fmt(qty * price);
            }

            function update() {
                const sparepartSubtotal = getSparepartSubtotal();
                sparepartInput.dataset.subtotal = sparepartSubtotal;

                const panelPct = clampPct(panelInput.value);
                const sparepartPct = clampPct(sparepartInput.value);

                const panelDiscount = Math.round(panelSubtotal * panelPct / 100);
                const sparepartDiscount = Math.round(sparepartSubtotal * sparepartPct / 100);

                const subtotal = panelSubtotal + sparepartSubtotal;
                const discount = panelDiscount + sparepartDiscount;
                const total = subtotal - discount;

                document.getElementById('sparepart-total-display').textContent = fmt(sparepartSubtotal);
                document.getElementById('wo-total-display').textContent = fmt(subtotal);
                document.getElementById('preview-panel-discount').textContent = fmt(panelDiscount);
                document.getElementById('preview-sparepart-subtotal').textContent = fmt(sparepartSubtotal);
                document.getElementById('preview-sparepart-discount').textContent = fmt(sparepartDiscount);
                document.getElementById('preview-subtotal').textContent = fmt(subtotal);
                document.getElementById('preview-discount').textContent = fmt(discount);
                document.getElementById('preview-total').textContent = fmt(total);
            }

            function addRow() {
                const row = document.createElement('tr');
                row.className = 'sparepart-row';
                row.innerHTML = `
                    <td><input type="text" name="sparepart_items[${itemIndex}][description]" class="form-control form-control-sm sparepart-description" placeholder="e.g. Bumper Depan"></td>
                    <td><input type="number" name="sparepart_items[${itemIndex}][quantity]" class="form-control form-control-sm sparepart-qty text-right" step="0.01" min="0.01" value="1"></td>
                    <td><input type="number" name="sparepart_items[${itemIndex}][unit_price]" class="form-control form-control-sm sparepart-price text-right" step="1" min="0" value="0"></td>
                    <td><input type="text" class="form-control form-control-sm sparepart-row-total text-right" readonly value="Rp 0"></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-xs remove-sparepart-item"><i class="fas fa-trash"></i></button>
                    </td>`;
                itemsContainer.appendChild(row);
                itemIndex++;
            }

            addItemBtn.addEventListener('click', addRow);

            itemsContainer.addEventListener('input', function(e) {
                const row = e.target.closest('.sparepart-row');
                if (!row) return;
                if (e.target.classList.contains('sparepart-qty') || e.target.classList.contains('sparepart-price')) {
                    updateRowTotal(row);
                    update();
                }
            });

            itemsContainer.addEventListener('click', function(e) {
                const btn = e.target.closest('.remove-sparepart-item');
                if (!btn) return;
                btn.closest('.sparepart-row').remove();
                update();
            });

            panelInput.addEventListener('input', update);
            sparepartInput.addEventListener('input', update);

            // Strip empty sparepart rows before submit so validation doesn't
            // block the form when a row was left blank.
            document.querySelector('form').addEventListener('submit', function() {
                itemsContainer.querySelectorAll('.sparepart-row').forEach(function(row) {
                    const desc = row.querySelector('.sparepart-description');
                    if (!desc || !desc.value.trim()) row.remove();
                });
            });

            // Start with one empty row for convenience
            addRow();
            update();
        })();
    </script>
@endpush
