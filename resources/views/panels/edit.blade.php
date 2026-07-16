@extends('layouts.admin')
@section('title', 'Edit Panel')
@section('page_title', 'Edit Panel')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit {{ $panel->panel_code }}</h3>
                </div>
                <form action="{{ route('panels.update', $panel) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="form-group">
                            <label>Code</label>
                            <input type="text" class="form-control" value="{{ $panel->panel_code }}" readonly>
                        </div>

                        <div class="form-group">
                            <label>Description <span class="text-danger">*</span></label>
                            <input type="text" name="description"
                                class="form-control @error('description') is-invalid @enderror"
                                value="{{ old('description', $panel->description) }}" required>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Base Price (Rp) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                    <input type="number" name="price"
                                        class="form-control @error('price') is-invalid @enderror"
                                        value="{{ old('price', $panel->price) }}" min="0" step="1" required>
                                </div>
                                @error('price')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Multiplier</label>
                                <input type="number" name="multiplier"
                                    class="form-control @error('multiplier') is-invalid @enderror"
                                    value="{{ old('multiplier', $panel->multiplier) }}" min="0" step="0.01">
                                @error('multiplier')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label>Price 0–300jt</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                    <input type="number" name="price_0_300"
                                        class="form-control @error('price_0_300') is-invalid @enderror"
                                        value="{{ old('price_0_300', $panel->price_0_300) }}" min="0" step="1">
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Price 300–500jt</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                    <input type="number" name="price_300_500"
                                        class="form-control @error('price_300_500') is-invalid @enderror"
                                        value="{{ old('price_300_500', $panel->price_300_500) }}" min="0" step="1">
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Price 500–800jt</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                    <input type="number" name="price_500_800"
                                        class="form-control @error('price_500_800') is-invalid @enderror"
                                        value="{{ old('price_500_800', $panel->price_500_800) }}" min="0" step="1">
                                </div>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Price 800jt–2M</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                    <input type="number" name="price_800_2000"
                                        class="form-control @error('price_800_2000') is-invalid @enderror"
                                        value="{{ old('price_800_2000', $panel->price_800_2000) }}" min="0" step="1">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                    value="1" {{ old('is_active', $panel->is_active) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                        <a href="{{ route('panels.index') }}" class="btn btn-secondary ml-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
