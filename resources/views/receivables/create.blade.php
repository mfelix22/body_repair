@extends('layouts.admin')

@section('title', 'Bon In')
@section('page_title', 'Bon In from PO: ' . $purchaseOrder->po_number)

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create Bon In</h3>
                </div>

                <form action="{{ route('receivables.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">

                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Bon In from PO supports Type 1 and Type 2.
                            Number format: Type 1 = 100001..., Type 2 = 200001...
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h6>Purchase Order Details</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>PO Number:</th>
                                        <td>{{ $purchaseOrder->po_number }}</td>
                                    </tr>
                                    <tr>
                                        <th>Order Date:</th>
                                        <td>{{ $purchaseOrder->order_date->format('M d, Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Supplier:</th>
                                        <td>{{ $purchaseOrder->supplier->name ?? $purchaseOrder->supplier_name }}</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bon_in_type">Bon In Type <span class="text-danger">*</span></label>
                                    <select name="bon_in_type" id="bon_in_type"
                                        class="form-control select2 @error('bon_in_type') is-invalid @enderror" required>
                                        <option value="">Select Type</option>
                                        <option value="1" {{ old('bon_in_type') == 1 ? 'selected' : '' }}>
                                            1 - Restocking for Storage
                                        </option>
                                        <option value="2" {{ old('bon_in_type') == 2 ? 'selected' : '' }}>
                                            2 - Customer Specific Item
                                        </option>
                                    </select>
                                    @error('bon_in_type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Use Type 1 for regular restocking and Type 2 for customer-specific items.
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6>Items to Receive</h6>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>UOM</th>
                                    <th>Remaining Quantity</th>
                                    <th>Quantity Received <span class="text-danger">*</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($purchaseOrder->details as $index => $detail)
                                    @php
                                        $remainingQty = max(
                                            0,
                                            (float) $detail->quantity - (float) ($detail->received_quantity ?? 0),
                                        );
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $detail->item->name }}
                                            <input type="hidden" name="items[{{ $index }}][item_id]"
                                                value="{{ $detail->item_id }}">
                                        </td>
                                        <td>
                                            {{ $detail->uom->name }} ({{ $detail->uom->code }})
                                            <input type="hidden" name="items[{{ $index }}][uom_id]"
                                                value="{{ $detail->uom_id }}">
                                        </td>
                                        <td>
                                            {{ number_format($remainingQty, 2) }}
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $index }}][quantity_received]"
                                                class="form-control @error('items.' . $index . '.quantity_received') is-invalid @enderror"
                                                step="0.01" min="0" max="{{ $remainingQty }}"
                                                value="{{ old('items.' . $index . '.quantity_received', $remainingQty) }}"
                                                required>
                                            @error('items.' . $index . '.quantity_received')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save as On Progress
                        </button>
                        <a href="{{ route('receivables.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
