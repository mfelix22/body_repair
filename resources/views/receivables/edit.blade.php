@extends('layouts.admin')

@section('title', 'Edit Bon In')
@section('page_title', 'Edit Bon In: ' . $receivable->receive_number)

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Bon In - {{ $receivable->receive_number }}</h3>
                </div>

                <form action="{{ route('receivables.update', $receivable) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> You can update the received quantities. If you receive less
                            than ordered, status will be "Partial Received". You can edit again to add more received
                            quantities later.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h6>Bon In Details</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Bon In Number:</th>
                                        <td>{{ $receivable->receive_number }}</td>
                                    </tr>
                                    <tr>
                                        <th>Bon In Type:</th>
                                        <td>
                                            @php
                                                $bonInTypeLabels = [
                                                    1 => '1 - Restocking for Storage',
                                                    2 => '2 - Customer Specific Item',
                                                    3 => '3 - Adjustment In',
                                                ];
                                            @endphp
                                            {{ $bonInTypeLabels[$receivable->bon_in_type] ?? $receivable->bon_in_type }}
                                        </td>
                                    </tr>
                                    @if ($receivable->purchaseOrder && $receivable->purchaseOrder->id)
                                        <tr>
                                            <th>PO Number:</th>
                                            <td>
                                                <a href="{{ route('purchase_orders.show', $receivable->purchaseOrder) }}">
                                                    {{ $receivable->purchaseOrder->po_number }}
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Supplier:</th>
                                            <td>{{ $receivable->purchaseOrder->supplier->name ?? $receivable->purchaseOrder->supplier_name }}
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <th>Supplier:</th>
                                            <td>{{ $receivable->supplier->name ?? ($receivable->supplier_name ?? '-') }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="received_date">Received Date <span class="text-danger">*</span></label>
                                    <input type="date" name="received_date" id="received_date"
                                        class="form-control @error('received_date') is-invalid @enderror"
                                        value="{{ old('received_date', $receivable->received_date->format('Y-m-d')) }}"
                                        required>
                                    @error('received_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $receivable->notes) }}</textarea>
                                    @error('notes')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6>Items to Receive</h6>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong> Update the "Quantity
                            Received" values. You can increase quantities as more items arrive from supplier.
                        </div>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>UOM</th>
                                    <th>Quantity Ordered</th>
                                    <th>Quantity Received <span class="text-danger">*</span></th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($receivable->items as $index => $item)
                                    @php
                                        $variance = $item->quantity_received - $item->quantity_ordered;
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $item->item->name }}
                                            <input type="hidden" name="items[{{ $index }}][item_id]"
                                                value="{{ $item->item_id }}">
                                        </td>
                                        <td>
                                            {{ $item->uom->name }} ({{ $item->uom->code }})
                                            <input type="hidden" name="items[{{ $index }}][uom_id]"
                                                value="{{ $item->uom_id }}">
                                        </td>
                                        <td>
                                            {{ number_format($item->quantity_ordered, 2) }}
                                            <input type="hidden" name="items[{{ $index }}][quantity_ordered]"
                                                value="{{ $item->quantity_ordered }}">
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $index }}][quantity_received]"
                                                class="form-control @error('items.' . $index . '.quantity_received') is-invalid @enderror"
                                                step="0.01" min="0" max="{{ $item->quantity_ordered }}"
                                                value="{{ old('items.' . $index . '.quantity_received', $item->quantity_received) }}"
                                                required>
                                            @error('items.' . $index . '.quantity_received')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td>
                                            @if ($variance < 0)
                                                <span class="badge badge-warning">Partial
                                                    ({{ number_format($variance, 2) }})</span>
                                            @elseif($variance > 0)
                                                <span class="badge badge-info">Over
                                                    ({{ number_format($variance, 2) }})</span>
                                            @else
                                                <span class="badge badge-success">Complete</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Bon In
                        </button>
                        <a href="{{ route('receivables.show', $receivable) }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
