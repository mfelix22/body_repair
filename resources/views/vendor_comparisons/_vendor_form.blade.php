<div class="vendor-card">
    <div class="vendor-header">
        <span><i class="fas fa-store mr-1"></i> Vendor {{ $vendorNum }}</span>
        <button type="button" class="btn btn-danger btn-sm btn-remove-vendor" style="display:none;">
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
                <select class="form-control select2 select2-supplier supplier-autofill" data-index="{{ $index }}">
                    <option value="">-- Pilih Supplier untuk Auto-Isi --</option>
                    @foreach ($vendors as $s)
                        <option data-name="{{ $s->name }}" data-alamat="{{ $s->address }}"
                            data-telepon="{{ $s->phone }}" data-email="{{ $s->email }}"
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
                    <input type="text" name="vendors[{{ $index }}][nama_calon_vendor]"
                        class="form-control vendor-nama" value="{{ old('vendors.' . $index . '.nama_calon_vendor') }}"
                        placeholder="Nama Perusahaan / Vendor" required>
                    @error('vendors.' . $index . '.nama_calon_vendor')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Alamat</label>
                    <input type="text" name="vendors[{{ $index }}][alamat]"
                        class="form-control vendor-alamat" value="{{ old('vendors.' . $index . '.alamat') }}"
                        placeholder="Alamat lengkap">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Telepon / Fax</label>
                    <input type="text" name="vendors[{{ $index }}][telepon_fax]"
                        class="form-control vendor-telepon" value="{{ old('vendors.' . $index . '.telepon_fax') }}"
                        placeholder="e.g., 021-12345678">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="vendors[{{ $index }}][email]" class="form-control vendor-email"
                        value="{{ old('vendors.' . $index . '.email') }}" placeholder="email@vendor.com">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>PIC / Contact Person</label>
                    <input type="text" name="vendors[{{ $index }}][pic_contact_person]"
                        class="form-control vendor-pic" value="{{ old('vendors.' . $index . '.pic_contact_person') }}"
                        placeholder="Nama PIC">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Metode Pembayaran</label>
                    <select name="vendors[{{ $index }}][metode_pembayaran]" class="form-control">
                        <option value="">-- Pilih --</option>
                        <option value="Tunai"
                            {{ old('vendors.' . $index . '.metode_pembayaran') === 'Tunai' ? 'selected' : '' }}>Tunai
                        </option>
                        <option value="Kredit"
                            {{ old('vendors.' . $index . '.metode_pembayaran') === 'Kredit' ? 'selected' : '' }}>
                            Kredit</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Rekening Bank</label>
                    <input type="text" name="vendors[{{ $index }}][rekening_bank]"
                        class="form-control vendor-bank" value="{{ old('vendors.' . $index . '.rekening_bank') }}"
                        placeholder="e.g., BCA - 1234567890 a.n. PT XYZ">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Term of Payment</label>
                    <input type="text" name="vendors[{{ $index }}][term_of_payment]" class="form-control"
                        value="{{ old('vendors.' . $index . '.term_of_payment') }}" placeholder="e.g., 30 hari">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Harga Barang / Jasa (Rp)</label>
                    <input type="number" name="vendors[{{ $index }}][harga_barang_jasa]" class="form-control"
                        value="{{ old('vendors.' . $index . '.harga_barang_jasa') }}" placeholder="0" step="0.01"
                        min="0">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Ketentuan Lain-lain dari Calon Supplier</label>
                    <textarea name="vendors[{{ $index }}][ketentuan_lain]" class="form-control" rows="2"
                        placeholder="Ketentuan lain dari calon supplier...">{{ old('vendors.' . $index . '.ketentuan_lain') }}</textarea>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="vendors[{{ $index }}][include_ppn]"
                            id="include_ppn_{{ $index }}" class="custom-control-input" value="1"
                            {{ old('vendors.' . $index . '.include_ppn', true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="include_ppn_{{ $index }}">
                            Harga sudah termasuk PPN 11%
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Total Termasuk PPN 11%</label>
                    <input type="text" class="form-control total-ppn" readonly placeholder="Rp 0"
                        value="Rp {{ old('vendors.' . $index . '.harga_barang_jasa') && old('vendors.' . $index . '.include_ppn', true) ? number_format((float) old('vendors.' . $index . '.harga_barang_jasa') * 1.11, 2, ',', '.') : '0,00' }}">
                </div>
            </div>
        </div>
    </div>
</div>
