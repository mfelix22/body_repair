@extends('layouts.admin')

@push('styles')
    <style>
        .vendor-comparison-card {
            border: 2px solid #dee2e6;
            border-radius: 6px;
            margin-bottom: 1rem;
            transition: border-color 0.2s;
        }

        .vendor-comparison-card.selected-vendor {
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
        }

        .vendor-comparison-card .vendor-card-header {
            padding: 10px 15px;
            border-bottom: 1px solid #dee2e6;
            font-weight: bold;
            background: #f8f9fa;
            border-radius: 4px 4px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .vendor-comparison-card.selected-vendor .vendor-card-header {
            background: #d4edda;
        }

        .vendor-comparison-card .vendor-card-body {
            padding: 15px;
        }

        .info-row {
            display: flex;
            margin-bottom: 6px;
        }

        .info-label {
            font-weight: 600;
            min-width: 180px;
            color: #495057;
        }

        .info-value {
            flex: 1;
        }

        .status-badge {
            font-size: 0.9rem;
        }
    </style>
@endpush

@section('title', 'Detail - ' . $vendorComparison->comparison_number)
@section('page_title', 'Formulir Perbandingan Vendor')

@section('content')
    <div class="row">
        <div class="col-12">

            {{-- Alerts --}}

            {{-- Header Card --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-balance-scale mr-2"></i>
                        {{ $vendorComparison->comparison_number }}
                    </h3>
                    <div class="card-tools">
                        @php $status = $vendorComparison->status; @endphp
                        <span
                            class="badge status-badge badge-{{ $status === 'approved' ? 'success' : ($status === 'submitted' ? 'info' : 'secondary') }} mr-2">
                            {{ $status === 'approved' ? 'Disetujui' : ($status === 'submitted' ? 'Menunggu Persetujuan' : 'Draft') }}
                        </span>

                        @if (
                            $status === 'draft' &&
                                auth()->user()->hasAnyRole(['purchasing', 'admin', 'super_admin']))
                            <a href="{{ route('vendor_comparisons.edit', $vendorComparison) }}"
                                class="btn btn-warning btn-sm mr-1">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form action="{{ route('vendor_comparisons.submit', $vendorComparison) }}" method="POST"
                                class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-info btn-sm"
                                    onclick="return confirm('Submit formulir ini untuk persetujuan direktur?')">
                                    <i class="fas fa-paper-plane"></i> Submit untuk Persetujuan
                                </button>
                            </form>
                        @endif

                        @if (\App\Helpers\PermissionHelper::canPrint('purchase_orders'))
                            <a href="{{ route('vendor_comparisons.print', $vendorComparison) }}" target="_blank"
                                class="btn btn-secondary btn-sm ml-1">
                                <i class="fas fa-print"></i> Cetak / PDF
                            </a>
                        @endif
                        <a href="{{ route('vendor_comparisons.index') }}" class="btn btn-default btn-sm ml-1">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="180">No. Perbandingan</th>
                                    <td>{{ $vendorComparison->comparison_number }}</td>
                                </tr>
                                <tr>
                                    <th>No. Permintaan</th>
                                    <td>
                                        {{ $vendorComparison->nomor_permintaan ?? '-' }}
                                        @if ($vendorComparison->purchaseRequest)
                                            <a href="{{ route('purchase_requests.show', $vendorComparison->purchaseRequest) }}"
                                                class="badge badge-light ml-1">
                                                {{ $vendorComparison->purchaseRequest->pr_number }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tanggal</th>
                                    <td>{{ $vendorComparison->tanggal->format('d F Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Detail Barang/Jasa</th>
                                    <td>{{ $vendorComparison->detail_barang_jasa }}</td>
                                </tr>
                                @if ($vendorComparison->notes)
                                    <tr>
                                        <th>Catatan</th>
                                        <td>{{ $vendorComparison->notes }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="180">Dibuat Oleh</th>
                                    <td>{{ $vendorComparison->creator->name ?? '-' }}</td>
                                </tr>
                                @if ($vendorComparison->approver)
                                    <tr>
                                        <th>Disetujui Oleh</th>
                                        <td>{{ $vendorComparison->approver->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Tanggal Persetujuan</th>
                                        <td>{{ $vendorComparison->approved_at->format('d F Y H:i') }}</td>
                                    </tr>
                                @endif
                                @if ($vendorComparison->selectedVendor)
                                    <tr>
                                        <th>Vendor Terpilih</th>
                                        <td>
                                            <span class="badge badge-success badge-lg py-1 px-2">
                                                <i class="fas fa-check-circle"></i>
                                                {{ $vendorComparison->selectedVendor->nama_calon_vendor }}
                                            </span>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Vendor Selection Form (Director only, when submitted) --}}
            @if (
                $vendorComparison->status === 'submitted' &&
                    auth()->user()->hasAnyRole(['director', 'admin', 'super_admin']))
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-gavel mr-2"></i> Pilih Vendor yang Disetujui</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Pilih salah satu vendor berikut sebagai vendor yang disetujui untuk
                            permintaan ini:</p>
                        <form action="{{ route('vendor_comparisons.select_vendor', $vendorComparison) }}" method="POST">
                            @csrf
                            <div class="row">
                                @foreach ($vendorComparison->vendors as $v)
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100 border">
                                            <div class="card-body">
                                                <div class="custom-control custom-radio mb-2">
                                                    <input type="radio" name="selected_vendor_id"
                                                        id="vendor_{{ $v->id }}" value="{{ $v->id }}"
                                                        class="custom-control-input" required>
                                                    <label class="custom-control-label font-weight-bold"
                                                        for="vendor_{{ $v->id }}">
                                                        Vendor {{ $v->vendor_order }}: {{ $v->nama_calon_vendor }}
                                                    </label>
                                                </div>
                                                <hr class="mt-1 mb-2">
                                                <small class="d-block"><strong>Harga:</strong>
                                                    {{ $v->harga_barang_jasa ? 'Rp ' . number_format($v->harga_barang_jasa, 0, ',', '.') : '-' }}
                                                </small>
                                                <small class="d-block"><strong>Pembayaran:</strong>
                                                    {{ $v->metode_pembayaran ?? '-' }}</small>
                                                <small class="d-block"><strong>Term:</strong>
                                                    {{ $v->term_of_payment ?? '-' }}</small>
                                                <small class="d-block"><strong>PIC:</strong>
                                                    {{ $v->pic_contact_person ?? '-' }}</small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="submit" class="btn btn-success"
                                onclick="return confirm('Konfirmasi pilihan vendor ini?')">
                                <i class="fas fa-check-circle"></i> Konfirmasi Pilihan Vendor
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Vendor Detail Cards --}}
            <h5 class="mb-3"><i class="fas fa-building mr-1"></i> Data Vendor</h5>

            @foreach ($vendorComparison->vendors as $vendor)
                @php $isSelected = $vendorComparison->selected_vendor_id === $vendor->id; @endphp
                <div class="vendor-comparison-card {{ $isSelected ? 'selected-vendor' : '' }}">
                    <div class="vendor-card-header">
                        <span>
                            <i class="fas fa-store mr-1"></i>
                            Vendor {{ $vendor->vendor_order }}: <strong>{{ $vendor->nama_calon_vendor }}</strong>
                        </span>
                        @if ($isSelected)
                            <span class="badge badge-success">
                                <i class="fas fa-trophy"></i> Vendor Terpilih
                            </span>
                        @endif
                    </div>
                    <div class="vendor-card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-row">
                                    <span class="info-label">Nama Calon Vendor</span>
                                    <span class="info-value">{{ $vendor->nama_calon_vendor }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Alamat</span>
                                    <span class="info-value">{{ $vendor->alamat ?: '-' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Telepon / Fax</span>
                                    <span class="info-value">{{ $vendor->telepon_fax ?: '-' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Email</span>
                                    <span class="info-value">{{ $vendor->email ?: '-' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">PIC / Contact Person</span>
                                    <span class="info-value">{{ $vendor->pic_contact_person ?: '-' }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-row">
                                    <span class="info-label">Metode Pembayaran</span>
                                    <span class="info-value">{{ $vendor->metode_pembayaran ?: '-' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Rekening Bank</span>
                                    <span class="info-value">{{ $vendor->rekening_bank ?: '-' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Term of Payment</span>
                                    <span class="info-value">{{ $vendor->term_of_payment ?: '-' }}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Harga Barang / Jasa</span>
                                    <span class="info-value">
                                        @if ($vendor->harga_barang_jasa)
                                            <strong>Rp
                                                {{ number_format($vendor->harga_barang_jasa, 0, ',', '.') }}</strong>
                                        @else
                                            -
                                        @endif
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Ketentuan Lain</span>
                                    <span class="info-value">{{ $vendor->ketentuan_lain ?: '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

        </div>
    </div>
@endsection
