@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
@endpush

@section('title', 'Perbandingan Vendor')
@section('page_title', 'Formulir Perbandingan Vendor')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Formulir Perbandingan Vendor</h3>
                    <div class="card-tools">
                        @if (auth()->user()->hasAnyRole(['purchasing', 'admin', 'super_admin']))
                            <a href="{{ route('vendor_comparisons.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Buat Formulir
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">

                    <table id="vc-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No. Perbandingan</th>
                                <th>No. Permintaan</th>
                                <th>Tanggal</th>
                                <th>Detail Barang/Jasa</th>
                                <th>Jumlah Vendor</th>
                                <th>Vendor Terpilih</th>
                                <th>Status</th>
                                <th>Dibuat Oleh</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($comparisons as $vc)
                                <tr>
                                    <td><strong>{{ $vc->comparison_number }}</strong></td>
                                    <td>{{ $vc->nomor_permintaan ?? ($vc->purchaseRequest->pr_number ?? '-') }}</td>
                                    <td>{{ $vc->tanggal->format('d M Y') }}</td>
                                    <td>{{ Str::limit($vc->detail_barang_jasa, 50) }}</td>
                                    <td>{{ $vc->vendors_count }} vendor</td>
                                    <td>
                                        @if ($vc->selectedVendor)
                                            <span class="badge badge-success">
                                                <i class="fas fa-check-circle"></i>
                                                {{ $vc->selectedVendor->nama_calon_vendor }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match ($vc->status) {
                                                'approved' => 'success',
                                                'submitted' => 'info',
                                                default => 'secondary',
                                            };
                                            $statusLabel = match ($vc->status) {
                                                'approved' => 'Disetujui',
                                                'submitted' => 'Menunggu Persetujuan',
                                                default => 'Draft',
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $badgeClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td>{{ $vc->creator->name ?? '-' }}</td>
                                    <td>
                                        <a href="{{ route('vendor_comparisons.show', $vc) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Belum ada formulir perbandingan
                                        vendor.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('admin/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#vc-table').DataTable({
                responsive: true,
                pageLength: 25,
                order: [
                    [2, 'desc']
                ],
                dom: 'Bfrtip',
                buttons: [{
                        extend: 'excel',
                        className: 'btn btn-sm btn-success',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6, 7]
                        }
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-sm btn-secondary',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4, 5, 6, 7]
                        }
                    },
                ],
                columnDefs: [{
                    orderable: false,
                    targets: -1
                }]
            });
        });
    </script>
@endpush
