@extends('layouts.admin')

@section('title', 'Edit Vehicle – ' . $vehicle->plate_number)
@section('page_title', 'Edit Vehicle')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Vehicle: {{ $vehicle->plate_number }}</h3>
                </div>

                <form action="{{ route('vehicles.update', $vehicle) }}" method="POST">
                    @csrf
                    @method('PUT')
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
                                                {{ old('customer_id', $vehicle->customer_id) == $customer->id ? 'selected' : '' }}>
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
                                        value="{{ old('plate_number', $vehicle->plate_number) }}" required>
                                    @error('plate_number')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="brand">Brand</label>
                                    <input type="text" name="brand" id="brand" class="form-control"
                                        value="{{ old('brand', $vehicle->brand) }}">
                                </div>

                                <div class="form-group">
                                    <label for="model">Model</label>
                                    <input type="text" name="model" id="model" class="form-control"
                                        value="{{ old('model', $vehicle->model) }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="year">Year</label>
                                    <input type="text" name="year" id="year" class="form-control"
                                        value="{{ old('year', $vehicle->year) }}">
                                </div>

                                <div class="form-group">
                                    <label for="color">Color</label>
                                    <input type="text" name="color" id="color" class="form-control"
                                        value="{{ old('color', $vehicle->color) }}">
                                </div>

                                <div class="form-group">
                                    <label for="chasis_no">Chasis No</label>
                                    <input type="text" name="chasis_no" id="chasis_no" class="form-control"
                                        value="{{ old('chasis_no', $vehicle->chasis_no) }}">
                                </div>

                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $vehicle->notes) }}</textarea>
                                </div>

                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input"
                                        value="1" {{ old('is_active', $vehicle->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Vehicle
                        </button>
                        <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
