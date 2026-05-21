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

@section('title', 'Edit - ' . $vendorComparison->comparison_number)
@section('page_title', 'Edit Formulir Perbandingan Vendor')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-balance-scale mr-2"></i>
                        Edit Formulir Perbandingan Vendor — {{ $vendorComparison->comparison_number }}
                    </h3>
                </div>

                <form action="{{ route('vendor_comparisons.update', $vendorComparison) }}" method="POST" id="vc-form">
                    @csrf
                    @method('PUT')
                    <div class="card-body">

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
                                                {{ old('purchase_request_id', $vendorComparison->purchase_request_id) == $pr->id ? 'selected' : '' }}>
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
                                        value="{{ old('tanggal', $vendorComparison->tanggal->format('Y-m-d')) }}" required>
                                    @error('tanggal')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="detail_barang_jasa">Detail Barang / Jasa <span class="text-danger">*</span></label>
                            <textarea name="detail_barang_jasa" id="detail_barang_jasa" rows="2"
                                class="form-control @error('detail_barang_jasa') is-invalid @enderror"
                                placeholder="Jelaskan barang atau jasa yang akan dibeli..." required>{{ old('detail_barang_jasa', $vendorComparison->detail_barang_jasa) }}</textarea>
                            @error('detail_barang_jasa')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><i class="fas fa-building mr-1"></i> Data Calon Vendor</h5>
                            <button type="button" id="btn-add-vendor" class="btn btn-success btn-sm"
                                {{ $vendorComparison->vendors->count() >= 3 ? 'disabled' : '' }}>
                                <i class="fas fa-plus"></i> Tambah Vendor
                            </button>
                        </div>

                        <div id="vendor-list">
                            @foreach ($vendorComparison->vendors->sortBy('vendor_order') as $i => $vendor)
                                <div class="vendor-wrapper" data-index="{{ $i }}">
                                    <div class="vendor-card">
                                        <div class="vendor-header">
                                            <span><i class="fas fa-store mr-1"></i> Vendor {{ $i + 1 }}</span>
                                            <button type="button" class="btn btn-danger btn-sm btn-remove-vendor"
                                                {{ $i === 0 ? 'style=display:none;' : '' }}>
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </div>
                                        <div class="vendor-body">
                                            <div class="row mb-2">
                                                <div class="col-md-6">
                                                    <label class="text-muted small">
                                                        <i class="fas fa-magic"></i>
                                                        Auto-isi dari Supplier Master <em>(opsional — ketik manual di
                                                            bawah)</em>
                                                    </label>
                                                    <select class="form-control supplier-autofill"
                                                        data-index="{{ $i }}">
                                                        <option value="">-- Pilih Supplier untuk Auto-Isi --</option>
                                                        @foreach ($suppliers as $s)
                                                            <option data-name="{{ $s->name }}"
                                                                data-alamat="{{ $s->address }}"
                                                                data-telepon="{{ $s->phone }}"
                                                                data-email="{{ $s->email }}"
                                                                data-pic="{{ $s->contact_person }}"
                                                                data-bank="{{ implode(' - ', array_filter([$s->bank_name, $s->bank_account_no, $s->bank_account_name ? 'a.n. ' . $s->bank_account_name : ''])) }}"
                                                                value="{{ $s->id }}">{{ $s->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Nama Calon Vendor <span class="text-danger">*</span></label>
                                                        <input type="text"
                                                            name="vendors[{{ $i }}][nama_calon_vendor]"
                                                            class="form-control vendor-nama"
                                                            value="{{ old('vendors.' . $i . '.nama_calon_vendor', $vendor->nama_calon_vendor) }}"
                                                            placeholder="Nama Perusahaan / Vendor" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Alamat</label>
                                                        <input type="text" name="vendors[{{ $i }}][alamat]"
                                                            class="form-control vendor-alamat"
                                                            value="{{ old('vendors.' . $i . '.alamat', $vendor->alamat) }}"
                                                            placeholder="Alamat lengkap">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Telepon / Fax</label>
                                                        <input type="text"
                                                            name="vendors[{{ $i }}][telepon_fax]"
                                                            class="form-control vendor-telepon"
                                                            value="{{ old('vendors.' . $i . '.telepon_fax', $vendor->telepon_fax) }}"
                                                            placeholder="e.g., 021-12345678">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Email</label>
                                                        <input type="email" name="vendors[{{ $i }}][email]"
                                                            class="form-control vendor-email"
                                                            value="{{ old('vendors.' . $i . '.email', $vendor->email) }}"
                                                            placeholder="email@vendor.com">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>PIC / Contact Person</label>
                                                        <input type="text"
                                                            name="vendors[{{ $i }}][pic_contact_person]"
                                                            class="form-control vendor-pic"
                                                            value="{{ old('vendors.' . $i . '.pic_contact_person', $vendor->pic_contact_person) }}"
                                                            placeholder="Nama PIC">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Metode Pembayaran</label>
                                                        <select name="vendors[{{ $i }}][metode_pembayaran]"
                                                            class="form-control">
                                                            <option value="">-- Pilih --</option>
                                                            <option value="Tunai"
                                                                {{ old('vendors.' . $i . '.metode_pembayaran', $vendor->metode_pembayaran) === 'Tunai' ? 'selected' : '' }}>
                                                                Tunai</option>
                                                            <option value="Kredit"
                                                                {{ old('vendors.' . $i . '.metode_pembayaran', $vendor->metode_pembayaran) === 'Kredit' ? 'selected' : '' }}>
                                                                Kredit</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Rekening Bank</label>
                                                        <input type="text"
                                                            name="vendors[{{ $i }}][rekening_bank]"
                                                            class="form-control vendor-bank"
                                                            value="{{ old('vendors.' . $i . '.rekening_bank', $vendor->rekening_bank) }}"
                                                            placeholder="e.g., BCA - 1234567890 a.n. PT XYZ">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Term of Payment</label>
                                                        <input type="text"
                                                            name="vendors[{{ $i }}][term_of_payment]"
                                                            class="form-control"
                                                            value="{{ old('vendors.' . $i . '.term_of_payment', $vendor->term_of_payment) }}"
                                                            placeholder="e.g., 30 hari">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Harga Barang / Jasa (Rp)</label>
                                                        <input type="number"
                                                            name="vendors[{{ $i }}][harga_barang_jasa]"
                                                            class="form-control"
                                                            value="{{ old('vendors.' . $i . '.harga_barang_jasa', $vendor->harga_barang_jasa) }}"
                                                            placeholder="0" step="0.01" min="0">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Ketentuan Lain-lain dari Calon Supplier</label>
                                                        <textarea name="vendors[{{ $i }}][ketentuan_lain]" class="form-control" rows="2"
                                                            placeholder="Ketentuan lain dari calon supplier...">{{ old('vendors.' . $i . '.ketentuan_lain', $vendor->ketentuan_lain) }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <hr>
                        <div class="form-group">
                            <label for="notes">Catatan / Note</label>
                            <textarea name="notes" id="notes" rows="2" class="form-control" placeholder="Catatan tambahan...">{{ old('notes', $vendorComparison->notes) }}</textarea>
                        </div>

                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                        <a href="{{ route('vendor_comparisons.show', $vendorComparison) }}" class="btn btn-secondary">
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
        let vendorCount = {{ $vendorComparison->vendors->count() }};
        const MAX_VENDORS = 3;

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
                                <select class="form-control supplier-autofill" data-index="${index}">
                                    <option value="">-- Pilih Supplier untuk Auto-Isi --</option>
                                    ${suppliersData.map(s =>
                                        `<option data-name="${s.name}" data-alamat="${s.address || ''}" data-telepon="${s.phone || ''}" data-email="${s.email || ''}" data-pic="${s.contact_person || ''}" data-bank="${[s.bank_name, s.bank_account_no, s.bank_account_name ? 'a.n. '+s.bank_account_name : ''].filter(Boolean).join(' - ')}" value="${s.id}">${s.name}</option>`
                                    ).join('')}
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nama Calon Vendor <span class="text-danger">*</span></label>
                                    <input type="text" name="vendors[${index}][nama_calon_vendor]" class="form-control vendor-nama" placeholder="Nama Perusahaan / Vendor" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Alamat</label>
                                    <input type="text" name="vendors[${index}][alamat]" class="form-control vendor-alamat" placeholder="Alamat lengkap">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Telepon / Fax</label>
                                    <input type="text" name="vendors[${index}][telepon_fax]" class="form-control vendor-telepon" placeholder="e.g., 021-12345678">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="vendors[${index}][email]" class="form-control vendor-email" placeholder="email@vendor.com">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>PIC / Contact Person</label>
                                    <input type="text" name="vendors[${index}][pic_contact_person]" class="form-control vendor-pic" placeholder="Nama PIC">
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
                                    <input type="text" name="vendors[${index}][rekening_bank]" class="form-control vendor-bank" placeholder="e.g., BCA - 1234567890 a.n. PT XYZ">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Term of Payment</label>
                                    <input type="text" name="vendors[${index}][term_of_payment]" class="form-control" placeholder="e.g., 30 hari">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Harga Barang / Jasa (Rp)</label>
                                    <input type="number" name="vendors[${index}][harga_barang_jasa]" class="form-control" placeholder="0" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Ketentuan Lain-lain dari Calon Supplier</label>
                                    <textarea name="vendors[${index}][ketentuan_lain]" class="form-control" rows="2" placeholder="Ketentuan lain dari calon supplier..."></textarea>
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
                const removeBtn = wrapper.querySelector('.btn-remove-vendor');
                if (removeBtn) removeBtn.style.display = i === 0 ? 'none' : '';
            });
        }

        function attachSupplierAutofill() {
            document.querySelectorAll('.supplier-autofill').forEach(select => {
                select.removeEventListener('change', supplierAutofillHandler);
                select.addEventListener('change', supplierAutofillHandler);
            });
        }

        function supplierAutofillHandler() {
            const opt = this.options[this.selectedIndex];
            const wrapper = this.closest('.vendor-wrapper');
            if (!this.value) return;
            wrapper.querySelector('.vendor-nama').value = opt.dataset.name || '';
            wrapper.querySelector('.vendor-alamat').value = opt.dataset.alamat || '';
            wrapper.querySelector('.vendor-telepon').value = opt.dataset.telepon || '';
            wrapper.querySelector('.vendor-email').value = opt.dataset.email || '';
            wrapper.querySelector('.vendor-pic').value = opt.dataset.pic || '';
            wrapper.querySelector('.vendor-bank').value = opt.dataset.bank || '';
        }

        document.getElementById('btn-add-vendor').addEventListener('click', function() {
            const currentCount = document.querySelectorAll('#vendor-list .vendor-wrapper').length;
            if (currentCount >= MAX_VENDORS) {
                alert('Maksimal 3 vendor per formulir.');
                return;
            }
            const idx = Date.now();
            const num = currentCount + 1;
            const div = document.createElement('div');
            div.innerHTML = buildVendorForm(idx, num);
            document.getElementById('vendor-list').appendChild(div.firstElementChild);

            if (currentCount + 1 >= MAX_VENDORS) {
                this.disabled = true;
                this.classList.add('disabled');
            }

            attachSupplierAutofill();
            attachRemoveButtons();
        });

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
            const addBtn = document.getElementById('btn-add-vendor');
            addBtn.disabled = false;
            addBtn.classList.remove('disabled');
        }

        // Initialize
        attachSupplierAutofill();
        attachRemoveButtons();

        $('#purchase_request_id').select2({
            theme: 'bootstrap4',
            placeholder: '-- Pilih PPB/PPJ --',
            allowClear: true
        });
    </script>
@endpush
