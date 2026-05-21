@extends('layouts.admin')

@section('title', 'Import Items from Excel')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">

                {{-- Upload Card --}}
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-excel mr-1"></i> Import Items from Excel</h3>
                        <div class="card-tools">
                            <a href="{{ route('items.index') }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Items
                            </a>
                        </div>
                    </div>

                    <form action="{{ route('items.import.process') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <strong><i class="fas fa-exclamation-triangle"></i> Error:</strong>
                                    <ul class="mb-0 mt-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="file">
                                    <strong>Select Excel File</strong>
                                    <small class="text-muted">(.xlsx, .xls — max 5 MB)</small>
                                </label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input @error('file') is-invalid @enderror"
                                            id="file" name="file" accept=".xlsx,.xls,.csv">
                                        <label class="custom-file-label" for="file">Choose file…</label>
                                    </div>
                                </div>
                                @error('file')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="alert alert-info mb-0">
                                <strong><i class="fas fa-info-circle"></i> How it works:</strong>
                                <ul class="mb-0 mt-1">
                                    <li>The file must contain a sheet named <strong>Entry</strong> (or the first sheet is
                                        used).</li>
                                    <li>Items will be <strong>created</strong> if SKU doesn't exist, or
                                        <strong>updated</strong> if it does.
                                    </li>
                                    <li>UOM codes that don't exist will be created automatically.</li>
                                    <li>Rows with blank SKU <em>and</em> blank Name are silently skipped.</li>
                                    <li><strong>Saldo</strong>: opening stock quantity (in smallest unit).<br>
                                        Set for <em>new items only</em>. Ignored if the item already exists —
                                        use <strong>Bon In (type 3)</strong> for stock adjustments instead.</li>
                                    <li><strong>Opening_Avg_Cost</strong>: opening average cost per smallest unit.<br>
                                        Optional. For existing items, this will only backfill when current avg cost is still
                                        0.</li>
                                </ul>
                            </div>

                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <a href="{{ route('items.import.template') }}" class="btn btn-outline-success">
                                <i class="fas fa-download"></i> Download Template
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Import Now
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            <div class="col-md-4">

                {{-- Column Reference --}}
                <div class="card card-secondary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-table mr-1"></i> Expected Columns</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Column</th>
                                    <th>Maps To</th>
                                    <th>Required</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>ENTRY DATE</code></td>
                                    <td><span class="text-muted">Skipped</span></td>
                                    <td>—</td>
                                </tr>
                                <tr>
                                    <td><code>SKU</code></td>
                                    <td>Item Code</td>
                                    <td><span class="badge badge-danger">Yes</span></td>
                                </tr>
                                <tr>
                                    <td><code>Class</code></td>
                                    <td>Item Type</td>
                                    <td><span class="badge badge-warning">Optional</span></td>
                                </tr>
                                <tr>
                                    <td><code>Nama Barang</code></td>
                                    <td>Item Name</td>
                                    <td><span class="badge badge-danger">Yes</span></td>
                                </tr>
                                <tr>
                                    <td><code>Int_Qty</code></td>
                                    <td>Base qty (e.g. 1000)</td>
                                    <td><span class="badge badge-warning">Optional</span></td>
                                </tr>
                                <tr>
                                    <td><code>Int_Unit</code></td>
                                    <td>Smallest UOM code</td>
                                    <td><span class="badge badge-warning">Optional</span></td>
                                </tr>
                                <tr>
                                    <td><code>Conv_Qty</code></td>
                                    <td>Conv qty (e.g. 1)</td>
                                    <td><span class="badge badge-warning">Optional</span></td>
                                </tr>
                                <tr>
                                    <td><code>Conv_Unit</code></td>
                                    <td>Purchase UOM code</td>
                                    <td><span class="badge badge-warning">Optional</span></td>
                                </tr>
                                <tr class="table-success">
                                    <td><code>Saldo</code></td>
                                    <td>Opening stock qty<br><small class="text-muted">(new items only)</small></td>
                                    <td><span class="badge badge-warning">Optional</span></td>
                                </tr>
                                <tr class="table-warning">
                                    <td><code>Opening_Avg_Cost</code></td>
                                    <td>Opening average cost<br><small class="text-muted">(backfill if avg cost is
                                            0)</small></td>
                                    <td><span class="badge badge-warning">Optional</span></td>
                                </tr>
                                <tr class="table-success">
                                    <td><code>Selling_Price</code></td>
                                    <td>Selling price per smallest unit<br><small class="text-muted">(auto-fills SO unit
                                            price)</small></td>
                                    <td><span class="badge badge-warning">Optional</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Class Code Reference --}}
                <div class="card card-secondary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-tags mr-1"></i> Class Codes</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Class</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>CHEM</code></td>
                                    <td>B</td>
                                    <td>Chemical</td>
                                </tr>
                                <tr>
                                    <td><code>COAT</code></td>
                                    <td>A</td>
                                    <td>Coating</td>
                                </tr>
                                <tr>
                                    <td><code>CONS</code></td>
                                    <td>C</td>
                                    <td>Consumable</td>
                                </tr>
                                <tr>
                                    <td><code>EQUIP</code></td>
                                    <td>E</td>
                                    <td>Equipment</td>
                                </tr>
                                <tr>
                                    <td><code>TOOL</code></td>
                                    <td>T</td>
                                    <td>Tools</td>
                                </tr>
                                <tr>
                                    <td><code>TE</code></td>
                                    <td>TE</td>
                                    <td>Tools &amp; Equipment</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Handle custom file input label update
            $('#file').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName || 'Choose file…');
            });
        });
    </script>
@endpush
