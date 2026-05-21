@extends('layouts.admin')

@section('title', 'Import Customers & Vehicles')
@section('page_title', 'Import Customers & Vehicles')

@section('content')
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upload Excel File</h3>
                    <div class="card-tools">
                        <a href="{{ route('customers.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <form action="{{ route('customers.processImport') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
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
                            <label for="excel_file">Excel File <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" 
                                       class="custom-file-input @error('excel_file') is-invalid @enderror" 
                                       id="excel_file" 
                                       name="excel_file" 
                                       accept=".xlsx,.xls,.csv"
                                       required>
                                <label class="custom-file-label" for="excel_file">Choose file</label>
                            </div>
                            <small class="form-text text-muted">
                                Accepted formats: .xlsx, .xls, .csv (Max: 5MB)
                            </small>
                            @error('excel_file')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <h5><i class="icon fas fa-info-circle"></i> Excel Format Instructions:</h5>
                            <p class="mb-2"><strong>The Excel file should contain the following columns (in this order):</strong></p>
                            <ol class="mb-2">
                                <li><strong>Column A:</strong> Book Date (optional)</li>
                                <li><strong>Column B:</strong> Sales/SA (optional)</li>
                                <li><strong>Column C:</strong> Customer Name <span class="text-danger">*</span></li>
                                <li><strong>Column D:</strong> Phone Number</li>
                                <li><strong>Column E:</strong> Address</li>
                                <li><strong>Column F:</strong> Car Model (e.g., "Mercedes Benz E300")</li>
                                <li><strong>Column G:</strong> Chassis Number (No. Rangka)</li>
                                <li><strong>Column H:</strong> Plate Number (No. Polisi)</li>
                            </ol>
                            <p class="mb-2"><strong>Import Behavior:</strong></p>
                            <ul class="mb-0">
                                <li>The first row will be treated as headers and skipped</li>
                                <li>Customer Name is required</li>
                                <li>Year and Color fields will be left empty for warehouse users to update later</li>
                                <li>Duplicate customers (same name + phone) will be skipped</li>
                                <li>Duplicate vehicles (same plate number or chassis number) will be skipped</li>
                                <li>Car Model will be automatically split into Brand and Model</li>
                                <li>Each vehicle will be linked to its customer</li>
                            </ul>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Import Data
                        </button>
                        <a href="{{ route('customers.index') }}" class="btn btn-default">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Ensure selected file name is shown even if bsCustomFileInput is not loaded.
    $(function () {
        if (window.bsCustomFileInput && typeof window.bsCustomFileInput.init === 'function') {
            window.bsCustomFileInput.init();
        }

        $('#excel_file').on('change', function () {
            var fullPath = $(this).val() || '';
            var fileName = fullPath.split('\\').pop().split('/').pop();
            $(this).next('.custom-file-label').text(fileName || 'Choose file');
        });
    });
</script>
@endpush
