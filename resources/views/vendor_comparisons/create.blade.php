@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <style>
        .vendor-card {
            border: 2px solid #dee2e6;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .vendor-card .vendor-header {
            background: #f8f9fa;
            padding: 10px 15px;
            border-bottom: 1px solid #dee2e6;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .vendor-card .vendor-body {
            padding: 15px;
        }

        #vendor-list>.vendor-wrapper:first-child .btn-remove-vendor {
            display: none;
        }
    </style>
@endpush

@section('title', 'Buat Formulir Perbandingan Vendor')
@section('page_title', 'Buat Formulir Perbandingan Vendor')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-balance-scale mr-2"></i>
                        Formulir Perbandingan Vendor (FK-PCH)
                    </h3>
                </div>

                <form action="{{ route('vendor_comparisons.store') }}" method="POST" id="vc-form">
                    @csrf
                    <div class="card-body">

                        {{-- General Info --}}
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle"></i>
                            Nomor formulir akan di-generate secara otomatis. Isi minimal 1 vendor, maksimal 3 vendor.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="purchase_request_id">Link ke PPB / PPJ <small
                                            class="text-muted">(Opsional)</small></label>
                                    <select name="purchase_request_id" id="purchase_request_id"
                                        class="form-control select2">
                                        <option value="">-- Pilih PPB/PPJ --</option>
                                        @foreach ($prs as $pr)
                                            <option value="{{ $pr->id }}"
                                                {{ old('purchase_request_id', $selectedPrId) == $pr->id ? 'selected' : '' }}>
                                                {{ $pr->pr_number }} ({{ $pr->type }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggal">Tanggal <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal" id="tanggal"
                                        class="form-control @error('tanggal') is-invalid @enderror"
                                        value="{{ old('tanggal', date('Y-m-d')) }}" required>
                                    @error('tanggal')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Items selection from PR --}}
                        <div id="pr-items-section" style="display:none" class="mb-3">
                            <label><strong>Pilih Item yang Akan Dibandingkan:</strong> <span
                                    class="text-danger">*</span></label>
                            <div id="pr-items-list" class="border rounded p-3 bg-light">
                                <span class="text-muted">Pilih PPB/PPJ terlebih dahulu.</span>
                            </div>
                            <small class="text-muted">Centang item yang ingin dimasukkan dalam perbandingan vendor.</small>
                        </div>

                        <div class="form-group" id="detail-barang-group">
                            <label for="detail_barang_jasa">Detail Barang / Jasa <span class="text-danger">*</span></label>
                            <textarea name="detail_barang_jasa" id="detail_barang_jasa" rows="2"
                                class="form-control @error('detail_barang_jasa') is-invalid @enderror"
                                placeholder="Jelaskan barang atau jasa yang akan dibeli...">{{ old('detail_barang_jasa') }}</textarea>
                            @error('detail_barang_jasa')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="fas fa-building mr-1"></i> Data Calon Vendor</h5>
                            <button type="button" id="btn-add-vendor" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Tambah Vendor
                            </button>
                        </div>

                        <div id="vendor-list">
                            {{-- Vendor 1 (always present) --}}
                            <div class="vendor-wrapper" data-index="0">
                                @include('vendor_comparisons._vendor_form', [
                                    'index' => 0,
                                    'vendors' => $suppliers,
                                    'vendorNum' => 1,
                                ])
                            </div>
                        </div>

                        <hr>
                        <div class="form-group">
                            <label for="notes">Catatan / Note</label>
                            <textarea name="notes" id="notes" rows="2" class="form-control" placeholder="Catatan tambahan...">{{ old('notes') }}</textarea>
                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Formulir
                        </button>
                        <a href="{{ route('vendor_comparisons.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        let vendorCount = 1;
        const MAX_VENDORS = 3;

        // Pre-build supplier data for JS use
        const suppliersData = @json($suppliers);

        function buildVendorForm(index, num) {
            return `
            <div class="vendor-wrapper" data-index="${index}">
                <div class="vendor-card">
                    <div class="vendor-header">
                        <span><i class="fas fa-store mr-1"></i> Vendor ${num}</span>
                        <button type="button" class="btn btn-danger btn-sm btn-remove-vendor">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                    <div class="vendor-body">
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <label class="text-muted small">
                                    <i class="fas fa-magic"></i>
                                    Auto-isi dari Supplier Master <em>(opsional — ketik manual di bawah)</em>
                                </label>
                                <select class="form-control select2 select2-supplier supplier-autofill" data-index="${index}">
                                    <option value="">-- Pilih Supplier untuk Auto-Isi --</option>
                                    ${suppliersData.map(s =>
                                        `<option
                                                                        data-name="${s.name}"
                                                                        data-alamat="${s.address || ''}"
                                                                        data-telepon="${s.phone || ''}"
                                                                        data-email="${s.email || ''}"
                                                                        data-pic="${s.contact_person || ''}"
                                                                        data-bank="${[s.bank_name, s.bank_account_no, s.bank_account_name ? 'a.n. '+s.bank_account_name : ''].filter(Boolean).join(' - ')}"
                                                                        value="${s.id}">${s.name}</option>`
                                    ).join('')}
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nama Calon Vendor <span class="text-danger">*</span></label>
                                    <input type="text" name="vendors[${index}][nama_calon_vendor]"
                                        class="form-control vendor-nama" placeholder="Nama Perusahaan / Vendor" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Alamat</label>
                                    <input type="text" name="vendors[${index}][alamat]"
                                        class="form-control vendor-alamat" placeholder="Alamat lengkap">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Telepon / Fax</label>
                                    <input type="text" name="vendors[${index}][telepon_fax]"
                                        class="form-control vendor-telepon" placeholder="e.g., 021-12345678">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="vendors[${index}][email]"
                                        class="form-control vendor-email" placeholder="email@vendor.com">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>PIC / Contact Person</label>
                                    <input type="text" name="vendors[${index}][pic_contact_person]"
                                        class="form-control vendor-pic" placeholder="Nama PIC">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Metode Pembayaran</label>
                                    <select name="vendors[${index}][metode_pembayaran]" class="form-control">
                                        <option value="">-- Pilih --</option>
                                        <option value="Tunai">Tunai</option>
                                        <option value="Kredit">Kredit</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Rekening Bank</label>
                                    <input type="text" name="vendors[${index}][rekening_bank]"
                                        class="form-control vendor-bank" placeholder="e.g., BCA - 1234567890 a.n. PT XYZ">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Term of Payment</label>
                                    <input type="text" name="vendors[${index}][term_of_payment]"
                                        class="form-control" placeholder="e.g., 30 hari">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Harga Barang / Jasa (Rp)</label>
                                    <input type="number" name="vendors[${index}][harga_barang_jasa]"
                                        class="form-control" placeholder="0" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Ketentuan Lain-lain dari Calon Supplier</label>
                                    <textarea name="vendors[${index}][ketentuan_lain]"
                                        class="form-control" rows="2"
                                        placeholder="Ketentuan lain dari calon supplier..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="vendors[${index}][include_ppn]"
                                            id="include_ppn_${index}" class="custom-control-input" value="1" checked>
                                        <label class="custom-control-label" for="include_ppn_${index}">
                                            Harga sudah termasuk PPN 11%
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Total Termasuk PPN 11%</label>
                                    <input type="text" class="form-control total-ppn" readonly placeholder="Rp 0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        function updateVendorHeaders() {
            document.querySelectorAll('#vendor-list .vendor-wrapper').forEach((wrapper, i) => {
                const header = wrapper.querySelector('.vendor-header span');
                if (header) header.innerHTML = `<i class="fas fa-store mr-1"></i> Vendor ${i + 1}`;
            });
        }

        function supplierAutofillHandler() {
            const $select = $(this);
            const opt = $select.find('option:selected');
            const wrapper = $select.closest('.vendor-wrapper')[0];
            if (!$select.val()) return;
            wrapper.querySelector('.vendor-nama').value = opt.data('name') || '';
            wrapper.querySelector('.vendor-alamat').value = opt.data('alamat') || '';
            wrapper.querySelector('.vendor-telepon').value = opt.data('telepon') || '';
            wrapper.querySelector('.vendor-email').value = opt.data('email') || '';
            wrapper.querySelector('.vendor-pic').value = opt.data('pic') || '';
            wrapper.querySelector('.vendor-bank').value = opt.data('bank') || '';
        }

        function formatRupiah(amount) {
            return 'Rp ' + amount.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function updatePpnTotal(wrapper) {
            const hargaInput = wrapper.querySelector('[name*="harga_barang_jasa"]');
            const includeCheckbox = wrapper.querySelector('[name*="include_ppn"]');
            const totalInput = wrapper.querySelector('.total-ppn');
            if (!hargaInput || !totalInput) return;
            const harga = parseFloat(hargaInput.value) || 0;
            const total = includeCheckbox && includeCheckbox.checked ? harga * 1.11 : harga;
            totalInput.value = harga ? formatRupiah(total) : '';
        }

        document.getElementById('vendor-list').addEventListener('input', function(e) {
            if (e.target.name && e.target.name.includes('harga_barang_jasa')) {
                updatePpnTotal(e.target.closest('.vendor-wrapper'));
            }
        });

        document.getElementById('vendor-list').addEventListener('change', function(e) {
            if (e.target.name && e.target.name.includes('include_ppn')) {
                updatePpnTotal(e.target.closest('.vendor-wrapper'));
            }
        });

        document.getElementById('btn-add-vendor').addEventListener('click', function() {
            const currentCount = document.querySelectorAll('#vendor-list .vendor-wrapper').length;
            if (currentCount >= MAX_VENDORS) {
                alert('Maksimal 3 vendor per formulir.');
                return;
            }
            const idx = Date.now(); // unique index
            const num = currentCount + 1;
            const div = document.createElement('div');
            div.innerHTML = buildVendorForm(idx, num);
            document.getElementById('vendor-list').appendChild(div.firstElementChild);

            if (currentCount + 1 >= MAX_VENDORS) {
                this.disabled = true;
                this.classList.add('disabled');
            }

            initSupplierSelect2(div.firstElementChild);
            attachRemoveButtons();
        });

        function initSupplierSelect2(context = document) {
            $(context).find('.select2-supplier').each(function() {
                const $select = $(this);
                if ($select.data('select2')) return;
                $select.select2({
                    theme: 'bootstrap4',
                    placeholder: '-- Pilih Supplier untuk Auto-Isi --',
                    allowClear: true,
                    width: '100%'
                }).on('change.select2autofill', supplierAutofillHandler);
            });
        }

        function attachRemoveButtons() {
            document.querySelectorAll('.btn-remove-vendor').forEach(btn => {
                btn.removeEventListener('click', removeVendorHandler);
                btn.addEventListener('click', removeVendorHandler);
            });
        }

        function removeVendorHandler() {
            const wrappers = document.querySelectorAll('#vendor-list .vendor-wrapper');
            if (wrappers.length <= 1) {
                alert('Minimal 1 vendor diperlukan.');
                return;
            }
            this.closest('.vendor-wrapper').remove();
            updateVendorHeaders();
            // Re-enable add button
            const addBtn = document.getElementById('btn-add-vendor');
            addBtn.disabled = false;
            addBtn.classList.remove('disabled');
        }

        // PR items data passed from controller
        const prsItems = @json($prsJson);

        function renderPrItems(prId) {
            const section = document.getElementById('pr-items-section');
            const list = document.getElementById('pr-items-list');
            const detailGroup = document.getElementById('detail-barang-group');
            const detailTextarea = document.getElementById('detail_barang_jasa');

            if (!prId || !prsItems[prId] || prsItems[prId].length === 0) {
                section.style.display = 'none';
                detailGroup.style.display = '';
                detailTextarea.required = true;
                list.innerHTML = '<span class="text-muted">Tidak ada item pada PPB/PPJ ini.</span>';
                return;
            }

            const items = prsItems[prId];
            section.style.display = '';
            detailGroup.style.display = 'none';
            detailTextarea.required = false;

            let html = '';
            items.forEach(function(item) {
                const label = item.code ?
                    `${item.code} — ${item.name} (${item.qty} ${item.uom})` :
                    `${item.name} (${item.qty} ${item.uom})`;
                html += `
                <div class="form-check mb-1">
                    <input class="form-check-input pr-item-check" type="checkbox"
                        id="pr_item_${item.id}" value="${label}" checked>
                    <label class="form-check-label" for="pr_item_${item.id}">${label}</label>
                </div>`;
            });
            list.innerHTML = html;
        }

        // Populate items on PR change (use jQuery for Select2 compatibility)
        $('#purchase_request_id').on('change', function() {
            renderPrItems(this.value);
        });

        // On form submit: build detail_barang_jasa from checked items if PR is selected
        document.getElementById('vc-form').addEventListener('submit', function(e) {
            const prId = document.getElementById('purchase_request_id').value;
            const detailTextarea = document.getElementById('detail_barang_jasa');

            if (prId && prsItems[prId] && prsItems[prId].length > 0) {
                const checked = Array.from(document.querySelectorAll('.pr-item-check:checked'))
                    .map(cb => cb.value);
                if (checked.length === 0) {
                    e.preventDefault();
                    alert('Pilih minimal 1 item dari PPB/PPJ untuk perbandingan vendor.');
                    return;
                }
                detailTextarea.value = checked.join('\n');
            } else {
                if (!detailTextarea.value.trim()) {
                    e.preventDefault();
                    alert('Detail Barang / Jasa harus diisi.');
                    return;
                }
            }
        });

        // Initialize on page load (if PR pre-selected via query string)
        (function() {
            const prSelect = document.getElementById('purchase_request_id');
            if (prSelect.value) {
                renderPrItems(prSelect.value);
            }
        })();

        // Initialize vendor form handlers
        attachRemoveButtons();

        // Calculate initial PPn totals
        document.querySelectorAll('#vendor-list .vendor-wrapper').forEach(updatePpnTotal);

        // Initialize select2 dropdowns
        initSupplierSelect2();
        $('#purchase_request_id').select2({
            theme: 'bootstrap4',
            placeholder: '-- Pilih PPB/PPJ --',
            allowClear: true,
            width: '100%'
        });
    </script>
@endpush
