@extends('layouts.admin')

@section('title', 'UOM Conversions')
@section('page_title', 'UOM Conversions')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Conversion List</h3>
                </div>

                <div class="card-body">

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>From UOM</th>
                                <th>To UOM</th>
                                <th>Conversion Factor</th>
                                <th>Example</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($conversions as $conversion)
                                <tr>
                                    <td>{{ $conversion->fromUom->name }} ({{ $conversion->fromUom->code }})</td>
                                    <td>{{ $conversion->toUom->name }} ({{ $conversion->toUom->code }})</td>
                                    <td>{{ number_format($conversion->conversion_factor, 2) }}</td>
                                    <td>
                                        <small class="text-muted">
                                            1 {{ $conversion->fromUom->code }} =
                                            {{ number_format($conversion->conversion_factor, 2) }}
                                            {{ $conversion->toUom->code }}
                                        </small>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-warning btn-sm"
                                            onclick="editConversion({{ $conversion->id }}, {{ $conversion->from_uom_id }}, {{ $conversion->to_uom_id }}, {{ $conversion->conversion_factor }})">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form action="{{ route('uoms.conversions.destroy', $conversion) }}" method="POST"
                                            style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Delete this conversion?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title" id="form-title">Add Conversion</h3>
                </div>

                <form action="{{ route('uoms.conversions.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="from_uom_id">From UOM</label>
                            <select name="from_uom_id" id="from_uom_id"
                                class="form-control @error('from_uom_id') is-invalid @enderror" required>
                                <option value="">Select UOM</option>
                                @foreach ($uoms as $uom)
                                    <option value="{{ $uom->id }}"
                                        {{ old('from_uom_id') == $uom->id ? 'selected' : '' }}>
                                        {{ $uom->name }} ({{ $uom->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('from_uom_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="to_uom_id">To UOM</label>
                            <select name="to_uom_id" id="to_uom_id"
                                class="form-control @error('to_uom_id') is-invalid @enderror" required>
                                <option value="">Select UOM</option>
                                @foreach ($uoms as $uom)
                                    <option value="{{ $uom->id }}"
                                        {{ old('to_uom_id') == $uom->id ? 'selected' : '' }}>
                                        {{ $uom->name }} ({{ $uom->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('to_uom_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="conversion_factor">Conversion Factor</label>
                            <input type="number" name="conversion_factor" id="conversion_factor"
                                class="form-control @error('conversion_factor') is-invalid @enderror"
                                value="{{ old('conversion_factor') }}" step="0.000001" required>
                            <small class="form-text text-muted">How many "To UOM" in one "From UOM"</small>
                            @error('conversion_factor')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" id="submit-btn">Add Conversion</button>
                        <button type="button" class="btn btn-secondary" id="clear-btn" style="display: none;"
                            onclick="clearForm()">Clear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editConversion(id, fromUomId, toUomId, factor) {
            document.getElementById('from_uom_id').value = fromUomId;
            document.getElementById('to_uom_id').value = toUomId;
            document.getElementById('conversion_factor').value = factor;
            document.getElementById('form-title').textContent = 'Edit Conversion';
            document.getElementById('submit-btn').textContent = 'Update Conversion';
            document.getElementById('clear-btn').style.display = 'inline-block';
            document.querySelector('form').scrollIntoView({
                behavior: 'smooth'
            });
        }

        function clearForm() {
            document.getElementById('from_uom_id').value = '';
            document.getElementById('to_uom_id').value = '';
            document.getElementById('conversion_factor').value = '';
            document.getElementById('form-title').textContent = 'Add Conversion';
            document.getElementById('submit-btn').textContent = 'Add Conversion';
            document.getElementById('clear-btn').style.display = 'none';
        }
    </script>
@endsection
