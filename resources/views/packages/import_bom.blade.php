@extends('layouts.admin')

@section('title', 'Import Package BOM from Excel')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">

                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-excel mr-1"></i> Import Package BOM from Excel</h3>
                        <div class="card-tools">
                            <a href="{{ route('packages.index') }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Packages
                            </a>
                        </div>
                    </div>

                    <form action="{{ route('packages.bom_import.process') }}" method="POST" enctype="multipart/form-data">
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

                            <div class="alert alert-warning">
                                <i class="fas fa-lightbulb"></i> <strong>Item names must match exactly!</strong>
                                Having import errors?
                                <a href="{{ route('packages.item_names') }}" target="_blank" class="alert-link">
                                    <strong>Click here to view all item names in database</strong> <i
                                        class="fas fa-external-link-alt"></i>
                                </a>
                                and verify your Excel data matches exactly.
                            </div>

                            <div class="form-group">
                                <label for="file">
                                    <strong>Select Excel File</strong>
                                    <small class="text-muted">(.xlsx, .xls — max 5 MB)</small>
                                </label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input @error('file') is-invalid @enderror"
                                            id="file" name="file" accept=".xlsx,.xls">
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
                                    <li>The file must have a sheet named <strong>Entry</strong> (or the first sheet is
                                        used).</li>
                                    <li>Required columns: <code>Package_Code</code>, <code>Item_Name</code>,
                                        <code>Quantity</code>, <code>UOM</code>.
                                    </li>
                                    <li>Optional column: <code>Notes</code>.</li>
                                    <li>If the same Package + Item combination already exists, it will be
                                        <strong>updated</strong> with the new quantity.
                                    </li>
                                    <li><strong>Package_Code</strong> must match an existing package.
                                        <strong>Item_Name</strong> must match an existing item name (case-insensitive).
                                    </li>
                                    <li>Download the template below — it includes reference sheets with all current packages
                                        and items.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="card-footer d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-upload mr-1"></i> Import BOM
                            </button>
                            <a href="{{ route('packages.bom_import.template') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-download mr-1"></i> Download Template (.xlsx)
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-light card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-table mr-1"></i> Required Excel Format</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Column</th>
                                    <th>Description</th>
                                    <th>Required</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>Package_Code</code></td>
                                    <td>Package code (e.g. COAT-S)</td>
                                    <td><span class="badge badge-danger">Yes</span></td>
                                </tr>
                                <tr>
                                    <td><code>Item_Name</code></td>
                                    <td>Item name (e.g. MENZERNA 400)</td>
                                    <td><span class="badge badge-danger">Yes</span></td>
                                </tr>
                                <tr>
                                    <td><code>Quantity</code></td>
                                    <td>Amount per service</td>
                                    <td><span class="badge badge-danger">Yes</span></td>
                                </tr>
                                <tr>
                                    <td><code>UOM</code></td>
                                    <td>Unit code (e.g. ML, GR, PCS)</td>
                                    <td><span class="badge badge-danger">Yes</span></td>
                                </tr>
                                <tr>
                                    <td><code>Notes</code></td>
                                    <td>Optional remark</td>
                                    <td><span class="badge badge-secondary">No</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Show filename when file chosen
        document.getElementById('file').addEventListener('change', function() {
            const label = this.nextElementSibling;
            label.textContent = this.files[0]?.name ?? 'Choose file…';
        });
    </script>
@endsection
