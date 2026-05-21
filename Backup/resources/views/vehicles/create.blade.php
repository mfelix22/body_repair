@extends('layouts.admin')

@section('title', 'Register Vehicle')
@section('page_title', 'Register Vehicle')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Vehicle Details</h3>
                </div>

                <form action="{{ route('vehicles.store') }}" method="POST">
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

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="customer_id">Customer <span class="text-danger">*</span></label>
                                    <select name="customer_id" id="customer_id"
                                        class="form-control select2 @error('customer_id') is-invalid @enderror" required>
                                        <option value="">Select Customer</option>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}"
                                                {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="plate_number">Plate Number <span class="text-danger">*</span></label>
                                    <input type="text" name="plate_number" id="plate_number"
                                        class="form-control @error('plate_number') is-invalid @enderror"
                                        value="{{ old('plate_number') }}" placeholder="e.g., W 1988 MR" required>
                                    @error('plate_number')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="brand">Brand</label>
                                    <input type="text" name="brand" id="brand"
                                        class="form-control @error('brand') is-invalid @enderror"
                                        value="{{ old('brand') }}" placeholder="e.g., Toyota, Mercedes-Benz">
                                    @error('brand')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="model">Model</label>
                                    <input type="text" name="model" id="model"
                                        class="form-control @error('model') is-invalid @enderror"
                                        value="{{ old('model') }}" placeholder="e.g., Avanza, E 350">
                                    @error('model')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="year">Year</label>
                                    <input type="text" name="year" id="year"
                                        class="form-control @error('year') is-invalid @enderror"
                                        value="{{ old('year') }}" placeholder="e.g., 2021">
                                    @error('year')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="color">Color</label>
                                    <input type="text" name="color" id="color"
                                        class="form-control @error('color') is-invalid @enderror"
                                        value="{{ old('color') }}" placeholder="e.g., White, Silver">
                                    @error('color')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="chasis_no">Chasis No</label>
                                    <input type="text" name="chasis_no" id="chasis_no"
                                        class="form-control @error('chasis_no') is-invalid @enderror"
                                        value="{{ old('chasis_no') }}" placeholder="e.g., MHL213085KJ001691">
                                    @error('chasis_no')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Any additional notes...">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Register Vehicle
                        </button>
                        <a href="{{ route('vehicles.index') }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
