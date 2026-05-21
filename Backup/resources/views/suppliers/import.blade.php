@extends('layouts.admin')

@section('title', 'Import Suppliers')
@section('page_title', 'Import Suppliers')

@section('content')
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upload CSV / Excel File</h3>
                    <div class="card-tools">
                        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <form action="{{ route('suppliers.import.process') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">

                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif
                        @if (session('import_warnings'))
                            <div class="alert alert-warning">
                                <strong>Warnings:</strong>
                                <ul class="mb-0 mt-1">
                                    @foreach (session('import_warnings') as $w)
                                        <li>{{ $w }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="excel_file">CSV / Excel File <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input @error('excel_file') is-invalid @enderror"
                                    id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                                <label class="custom-file-label" for="excel_file">Choose file</label>
                            </div>
                            <small class="form-text text-muted">Accepted formats: .xlsx, .xls, .csv &mdash; Max 5 MB</small>
                            @error('excel_file')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <h5><i class="icon fas fa-info-circle"></i> Required Column Headers</h5>
                            <p class="mb-1">The first row must contain these headers (order does not matter):</p>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm mb-2">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Column Header</th>
                                            <th>Field</th>
                                            <th>Required?</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>SUPPLIER CODE</code></td>
                                            <td>Supplier Code</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><code>SUPPLIER NAME</code></td>
                                            <td>Name</td>
                                            <td><span class="text-danger">Yes</span></td>
                                        </tr>
                                        <tr>
                                            <td><code>ADDRESS</code></td>
                                            <td>Address</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><code>ECITY</code></td>
                                            <td>City</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><code>KODEPOS</code></td>
                                            <td>Postal Code</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><code>PHONE NUMBER</code></td>
                                            <td>Phone</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><code>SUPPLIER CONTACT</code></td>
                                            <td>Contact Person</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><code>BANK</code></td>
                                            <td>Bank Name</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><code>ACC_NO</code></td>
                                            <td>Bank Account No.</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><code>ACC_NAME</code></td>
                                            <td>Bank Account Name</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><code>NPWP</code></td>
                                            <td>NPWP</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><code>EMAIL</code></td>
                                            <td>Email</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td><code>WEBSITE</code></td>
                                            <td>Website</td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <ul class="mb-0">
                                <li>The header row is detected automatically &mdash; extra columns are ignored.</li>
                                <li>If a supplier with the same <strong>Supplier Code</strong> or <strong>Name</strong>
                                    already exists, it will be <strong>updated</strong>.</li>
                                <li>New suppliers will be <strong>created</strong>.</li>
                            </ul>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-file-import"></i> Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Update custom file label with chosen filename
        document.getElementById('excel_file').addEventListener('change', function() {
            var fileName = this.files[0] ? this.files[0].name : 'Choose file';
            this.nextElementSibling.textContent = fileName;
        });
    </script>
@endpush
