@extends('layouts.admin')
@section('title', 'Labor Master')
@section('page_title', 'Labor Master')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Labor Master List</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canCreate('labors'))
                            <button type="button" class="btn btn-success btn-sm" data-toggle="modal"
                                data-target="#importModal">
                                <i class="fas fa-file-excel"></i> Import Excel
                            </button>
                            <a href="{{ route('labors.create') }}" class="btn btn-primary btn-sm ml-1">
                                <i class="fas fa-plus"></i> Add Labor
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">

                    @if (session('import_errors') && count(session('import_errors')))
                        <div class="alert alert-warning">
                            <strong>Some rows were skipped:</strong>
                            <ul class="mb-0 mt-1">
                                @foreach (session('import_errors') as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <table class="table table-bordered table-hover table-sm" id="laborTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Code</th>
                                <th>Description</th>
                                <th class="text-right">Mult.</th>
                                <th class="text-right">0–300jt</th>
                                <th class="text-right">300–500jt</th>
                                <th class="text-right">500–800jt</th>
                                <th class="text-right">800jt–2M</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:100px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($labors as $labor)
                                <tr>
                                    <td><strong>{{ $labor->labor_code }}</strong></td>
                                    <td>{{ $labor->description }}</td>
                                    <td class="text-right">
                                        {{ $labor->multiplier !== null ? number_format($labor->multiplier, 2) : '-' }}</td>
                                    <td class="text-right">
                                        {{ $labor->price_0_300 !== null ? number_format($labor->price_0_300, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-right">
                                        {{ $labor->price_300_500 !== null ? number_format($labor->price_300_500, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-right">
                                        {{ $labor->price_500_800 !== null ? number_format($labor->price_500_800, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-right">
                                        {{ $labor->price_800_2000 !== null ? number_format($labor->price_800_2000, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-center">
                                        @if ($labor->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if (\App\Helpers\PermissionHelper::canUpdate('labors'))
                                            <a href="{{ route('labors.edit', $labor) }}" class="btn btn-warning btn-xs">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        @if (\App\Helpers\PermissionHelper::canDelete('labors'))
                                            <form action="{{ route('labors.destroy', $labor) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Delete {{ $labor->labor_code }}?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-danger btn-xs">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== IMPORT MODAL ===== --}}
    @if (\App\Helpers\PermissionHelper::canCreate('labors'))
        <div class="modal fade" id="importModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-file-excel text-success"></i> Import Labor from Excel</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <form action="{{ route('labors.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <strong>Expected Excel column order:</strong>
                                    <a href="{{ route('labors.template') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-download"></i> Download Template
                                    </a>
                                </div>
                                <ol class="mb-0 mt-1 pl-3">
                                    <li><strong>A</strong> – cdjob (Job Code)</li>
                                    <li><strong>B</strong> – emjob (Job Name)</li>
                                    <li><strong>C</strong> – hstd (Multiplier, e.g. 0.25)</li>
                                    <li><strong>D</strong> – fstd (ignored)</li>
                                    <li><strong>E</strong> – cdjob_o (ignored)</li>
                                    <li><strong>F</strong> – Price 0–300jt</li>
                                    <li><strong>G</strong> – Price 300–500jt</li>
                                    <li><strong>H</strong> – Price 500–800jt</li>
                                    <li><strong>I</strong> – Price 800jt–2M</li>
                                </ol>
                                <small class="d-block mt-1">Row 1 (headers) is skipped. Existing codes will be
                                    <strong>updated</strong>.</small>
                            </div>
                            <div class="form-group">
                                <label>Excel File (.xlsx / .xls) <span class="text-danger">*</span></label>
                                <input type="file" name="excel_file" accept=".xlsx,.xls" class="form-control-file"
                                    required>
                                @error('excel_file')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-upload"></i> Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        $(function() {
            $('#laborTable').DataTable({
                order: [
                    [0, 'asc']
                ],
                pageLength: 50,
                scrollX: true,
            });
        });
    </script>
@endpush
