@extends('layouts.admin')
@section('title', 'Create Standalone Bon Out')
@section('page_title', 'Create Standalone Bon Out')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Standalone Bon Out</h3>
                    <div class="card-tools">
                        <a href="{{ route('bon_outs.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <form action="{{ route('bon_outs.store') }}" method="POST" id="standalone-form">
                    @csrf
                    {{-- No work_order_id → triggers storeStandalone() --}}

                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
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

                        {{-- Basic Info --}}
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="bon_out_type">Bon Out Type <span class="text-danger">*</span></label>
                                    <select name="bon_out_type" id="bon_out_type" class="form-control" required>
                                        <option value="2" {{ old('bon_out_type', request('bon_out_type', '2')) == '2' ? 'selected' : '' }}>
                                            Regular Purchase (External Sale)</option>
                                        <option value="3" {{ old('bon_out_type', request('bon_out_type', '2')) == '3' ? 'selected' : '' }}>Stock
                                            Adjustment Out</option>
                                    </select>
                                    <small class="text-muted" id="type-hint-2">For customers outside who buy your
                                        materials.</small>
                                    <small class="text-muted d-none" id="type-hint-3">For damaged goods, expired items,
                                        inventory corrections.</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="purpose">Purpose / Reason <span class="text-danger">*</span></label>
                                    <input type="text" name="purpose" id="purpose" class="form-control"
                                        value="{{ old('purpose') }}" placeholder="e.g. External Sale, Damaged Goods"
                                        required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="1" placeholder="Optional notes...">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Items Table --}}
                        <h5>
                            <i class="fas fa-boxes"></i> Items
                            <button type="button" class="btn btn-outline-info btn-sm ml-2" id="open-scanner-btn">
                                <i class="fas fa-qrcode"></i> Scan Barcode / QR
                            </button>
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="items-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="45%">Item <span class="text-danger">*</span></th>
                                        <th width="15%" class="text-right">Current Stock</th>
                                        <th width="20%" class="text-center">Quantity <span class="text-danger">*</span>
                                        </th>
                                        <th width="10%">UOM</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="items-body">
                                    {{-- Initial row --}}
                                    <tr class="item-row" data-index="0">
                                        <td class="row-number">1</td>
                                        <td>
                                            <select name="items[0][item_id]" class="form-control item-select" data-index="0"
                                                required>
                                                <option value="">-- Select Item --</option>
                                                @foreach ($items as $item)
                                                    <option value="{{ $item->id }}"
                                                        data-uom="{{ $item->smallestUom->code ?? '-' }}"
                                                        data-stock="{{ $item->getCurrentStock() }}">
                                                        [{{ $item->code }}] {{ $item->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-right stock-display">-</td>
                                        <td>
                                            <input type="number" name="items[0][quantity]"
                                                class="form-control text-right qty-input" step="0.01" min="0.01"
                                                placeholder="0" required>
                                        </td>
                                        <td class="uom-display">-</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm remove-row" title="Remove">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" id="add-row" class="btn btn-outline-primary btn-sm mb-3">
                            <i class="fas fa-plus"></i> Add Item Row
                        </button>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Bon Out
                        </button>
                        <a href="{{ route('bon_outs.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Barcode / QR Scanner Modal --}}
    <div class="modal fade" id="scannerModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-qrcode"></i> Scan Item Barcode / QR</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="qr-reader" style="width: 100%;"></div>
                    <div id="qr-reader-status" class="text-muted small mt-2 text-center">Point the camera at the item's
                        barcode/QR code.</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endsection

@section('scripts')
    @php
        $itemsJson = $items
            ->map(
                fn($i) => [
                    'id' => $i->id,
                    'code' => $i->code,
                    'name' => $i->name,
                    'uom' => $i->smallestUom->code ?? '-',
                    'stock' => $i->getCurrentStock(),
                ],
            )
            ->values()
            ->all();
    @endphp
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        let rowIndex = 1;

        // Items data for JS
        const itemsData = @json($itemsJson);

        // Find item by scanned code (exact match, then loose contains match)
        function findItemByCode(scannedText) {
            const code = (scannedText || '').trim();
            if (!code) return null;
            let match = itemsData.find(i => i.code.toLowerCase() === code.toLowerCase());
            if (!match) {
                match = itemsData.find(i =>
                    code.toLowerCase().includes(i.code.toLowerCase()) ||
                    i.code.toLowerCase().includes(code.toLowerCase())
                );
            }
            return match || null;
        }

        // Apply a matched item to a row's select2, then focus qty input
        function applyScannedItemToRow(row, item) {
            const select = row.find('.item-select');
            if (!select.find(`option[value="${item.id}"]`).length) {
                select.append(new Option(`[${item.code}] ${item.name}`, item.id, false, false));
            }
            select.val(item.id).trigger('change.select2').trigger('change');
            row.find('.qty-input').focus();
        }

        // Find an existing empty row, or add a new one
        function getTargetRowForScan() {
            let emptyRow = null;
            $('.item-row').each(function() {
                if (!$(this).find('.item-select').val()) {
                    emptyRow = $(this);
                    return false;
                }
            });
            if (emptyRow) return emptyRow;
            $('#add-row').trigger('click');
            return $('.item-row').last();
        }

        let html5QrCode = null;

        function stopScanner() {
            if (html5QrCode) {
                html5QrCode.stop().then(() => html5QrCode.clear()).catch(() => {});
                html5QrCode = null;
            }
        }

        function onScanSuccess(decodedText) {
            const item = findItemByCode(decodedText);
            const statusEl = $('#qr-reader-status');
            if (!item) {
                statusEl.removeClass('text-muted').addClass('text-danger')
                    .text(`No item found for scanned code: "${decodedText}"`);
                return;
            }
            statusEl.removeClass('text-danger').addClass('text-muted')
                .text(`Matched: [${item.code}] ${item.name}`);
            const row = getTargetRowForScan();
            applyScannedItemToRow(row, item);
            stopScanner();
            $('#scannerModal').modal('hide');
        }

        $('#open-scanner-btn').on('click', function() {
            $('#qr-reader-status').removeClass('text-danger').addClass('text-muted')
                .text("Point the camera at the item's barcode/QR code.");
            $('#scannerModal').modal('show');
        });

        $('#scannerModal').on('shown.bs.modal', function() {
            html5QrCode = new Html5Qrcode('qr-reader');
            html5QrCode.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: 220 },
                onScanSuccess
            ).catch(function(err) {
                $('#qr-reader-status').removeClass('text-muted').addClass('text-danger')
                    .text('Unable to access camera: ' + err);
            });
        });

        $('#scannerModal').on('hidden.bs.modal', stopScanner);

        // Show/hide type hint
        $('#bon_out_type').on('change', function() {
            const val = $(this).val();
            $('#type-hint-2').toggleClass('d-none', val !== '2');
            $('#type-hint-3').toggleClass('d-none', val !== '3');
        });

        function initSelect2(selectEl) {
            $(selectEl).select2({
                theme: 'bootstrap4',
                placeholder: '-- Select Item --',
                allowClear: true,
                width: '100%'
            });
        }

        function bindItemChange(selectEl) {
            $(selectEl).on('change', function() {
                const row = $(this).closest('tr');
                const option = this.options[this.selectedIndex];
                if (option && option.value) {
                    row.find('.uom-display').text(option.dataset.uom || '-');
                    const stock = parseFloat(option.dataset.stock) || 0;
                    row.find('.stock-display').text(new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 2
                    }).format(stock));
                } else {
                    row.find('.uom-display').text('-');
                    row.find('.stock-display').text('-');
                }
            });
        }

        // Init first row
        $(function() {
            initSelect2($('.item-select').first());
            bindItemChange($('.item-select').first());
        });

        // Add new row
        $('#add-row').on('click', function() {
            let optionsHtml = '<option value="">-- Select Item --</option>';
            itemsData.forEach(function(item) {
                optionsHtml +=
                    `<option value="${item.id}" data-uom="${item.uom}" data-stock="${item.stock}">[${item.code}] ${item.name}</option>`;
            });

            const row = `
                <tr class="item-row" data-index="${rowIndex}">
                    <td class="row-number">${rowIndex + 1}</td>
                    <td>
                        <select name="items[${rowIndex}][item_id]" class="form-control item-select" data-index="${rowIndex}" required>
                            ${optionsHtml}
                        </select>
                    </td>
                    <td class="text-right stock-display">-</td>
                    <td>
                        <input type="number" name="items[${rowIndex}][quantity]" class="form-control text-right qty-input" step="0.01" min="0.01" placeholder="0" required>
                    </td>
                    <td class="uom-display">-</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-row" title="Remove">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            $('#items-body').append(row);

            const newSelect = $(`select[data-index="${rowIndex}"]`);
            initSelect2(newSelect);
            bindItemChange(newSelect);

            rowIndex++;
            renumberRows();
        });

        // Remove row
        $(document).on('click', '.remove-row', function() {
            if ($('.item-row').length <= 1) {
                alert('At least one item is required.');
                return;
            }
            $(this).closest('tr').remove();
            renumberRows();
        });

        function renumberRows() {
            $('.item-row').each(function(i) {
                $(this).find('.row-number').text(i + 1);
            });
        }
    </script>
@endsection
