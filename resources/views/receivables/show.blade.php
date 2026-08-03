@extends('layouts.admin')

@section('title', 'Bon In: ' . $receivable->receive_number)
@section('page_title', 'Bon In: ' . $receivable->receive_number)

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bon In - {{ $receivable->receive_number }}</h3>
                    <div class="card-tools">
                        @if (in_array($receivable->status, ['on_progress', 'partial_received']))
                            @if (auth()->user()->hasAnyRole(['warehouse', 'admin', 'super_admin']))
                                @php
                                    // Check if all items are fully received for button styling
                                    $allItemsFullyReceived = true;
                                    foreach ($receivable->items as $item) {
                                        if ($item->quantity_received < $item->quantity_ordered) {
                                            $allItemsFullyReceived = false;
                                            break;
                                        }
                                    }
                                @endphp

                                <a href="{{ route('receivables.edit', $receivable) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="{{ route('receivables.complete', $receivable) }}" method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Complete this Bon In and update stock? This cannot be undone.')">
                                    @csrf
                                    <button type="submit"
                                        class="btn btn-success btn-sm {{ $allItemsFullyReceived ? 'btn-pulse' : '' }}"
                                        style="{{ $allItemsFullyReceived ? 'font-weight: bold; box-shadow: 0 0 10px rgba(40, 167, 69, 0.6);' : '' }}">
                                        <i class="fas fa-check"></i> Complete & Update Stock
                                    </button>
                                </form>
                                <form action="{{ route('receivables.cancel', $receivable) }}" method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Are you sure you want to cancel this Bon In?')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-ban"></i> Cancel
                                    </button>
                                </form>
                            @endif
                        @else
                            @if ($receivable->status === 'cancelled')
                                <span class="badge badge-danger">Cancelled</span>
                            @else
                                {{-- 'printed' is a legacy status; both completed and printed display as Completed --}}
                                <span class="badge badge-success">Completed</span>
                                @if ($receivable->printed_at)
                                    <small class="text-muted ml-1"><i class="fas fa-print"></i> Printed
                                        {{ $receivable->printed_at->format('d/m/Y H:i') }}</small>
                                @endif
                            @endif
                        @endif
                        @if (in_array($receivable->status, ['completed', 'printed']))
                            @if (\App\Helpers\PermissionHelper::canPrint('receivables'))
                                <a href="{{ \URL::temporarySignedRoute('receivables.print', now()->addMinutes(5), $receivable) }}"
                                    class="btn btn-secondary btn-sm" target="_blank">
                                    <i class="fas fa-print"></i>
                                    {{ $receivable->printed_at ? 'Reprint Bon In' : 'Print Bon In' }}
                                </a>
                            @endif
                        @endif

                        <a href="{{ route('receivables.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="card-body">

                    @php
                        // Check if all items are fully received
                        $allItemsFullyReceived = true;
                        foreach ($receivable->items as $item) {
                            if ($item->quantity_received < $item->quantity_ordered) {
                                $allItemsFullyReceived = false;
                                break;
                            }
                        }
                    @endphp

                    @if (in_array($receivable->status, ['on_progress', 'partial_received']) && $allItemsFullyReceived)
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-check-circle"></i> All Items Fully Received!</h5>
                            All items have been received in full. You can now click <strong>"Complete & Update
                                Stock"</strong> to finalize this Bon In and update inventory.
                        </div>
                    @endif

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
                                <tr>
                                    <th>PO Number:</th>
                                    <td>
                                        @if ($receivable->purchaseOrder && $receivable->purchaseOrder->id)
                                            <a href="{{ route('purchase_orders.show', $receivable->purchaseOrder) }}">
                                                {{ $receivable->purchaseOrder->po_number }}
                                            </a>
                                        @else
                                            <span class="text-muted">No PO (Standalone Bon In)</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Received Date:</th>
                                    <td>{{ $receivable->received_date->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <th>PPN Included:</th>
                                    <td>
                                        @if ($receivable->purchaseOrder && $receivable->purchaseOrder->id)
                                            @if ($receivable->purchaseOrder->include_ppn)
                                                <span class="badge badge-info">Yes</span>
                                            @else
                                                <span class="badge badge-secondary">No</span>
                                            @endif
                                        @else
                                            <span class="text-muted">No PO</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        @if ($receivable->status === 'on_progress')
                                            <span class="badge badge-secondary">On Progress</span>
                                            @if ($allItemsFullyReceived)
                                                <span class="badge badge-success ml-1"><i class="fas fa-check"></i> Ready to
                                                    Complete</span>
                                            @endif
                                        @elseif ($receivable->status === 'partial_received')
                                            <span class="badge badge-warning">Partial Received</span>
                                            @if ($allItemsFullyReceived)
                                                <span class="badge badge-success ml-1"><i class="fas fa-check"></i> Ready to
                                                    Complete</span>
                                            @endif
                                        @elseif ($receivable->status === 'cancelled')
                                            <span class="badge badge-danger">Cancelled</span>
                                        @else
                                            <span class="badge badge-success">Completed</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6>Supplier Details</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Name:</th>
                                    <td>
                                        @if ($receivable->purchaseOrder && $receivable->purchaseOrder->id)
                                            {{ $receivable->purchaseOrder->supplier->name ?? $receivable->purchaseOrder->supplier_name }}
                                        @elseif ($receivable->supplier)
                                            {{ $receivable->supplier->name }}
                                        @elseif ($receivable->supplier_name)
                                            {{ $receivable->supplier_name }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td>
                                        @if ($receivable->purchaseOrder && $receivable->purchaseOrder->id)
                                            {{ $receivable->purchaseOrder->supplier->phone ?? ($receivable->purchaseOrder->supplier_phone ?? '-') }}
                                        @elseif ($receivable->supplier)
                                            {{ $receivable->supplier->phone ?? '-' }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Notes:</th>
                                    <td>{{ $receivable->notes ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>
                    <h6>Received Items</h6>
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>UOM</th>
                                <th>Quantity Ordered</th>
                                <th>Quantity Received</th>
                                <th>Isi/Kemasan</th>
                                <th>Variance</th>
                                <th>In Smallest UOM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($receivable->items as $receivableItem)
                                @php
                                    $variance = $receivableItem->quantity_received - $receivableItem->quantity_ordered;
                                    $itemUom = $receivableItem->item
                                        ->itemUoms()
                                        ->where('uom_id', $receivableItem->uom_id)
                                        ->first();
                                    $conversionFactor = $receivableItem->conversion_to_smallest
                                        ? (float) $receivableItem->conversion_to_smallest
                                        : ($itemUom ? (float) $itemUom->conversion_to_smallest : 1);
                                    $quantityInSmallest = $receivableItem->quantity_received * $conversionFactor;
                                @endphp
                                <tr>
                                    <td>{{ $receivableItem->item->name }}</td>
                                    <td>{{ $receivableItem->uom->name }} ({{ $receivableItem->uom->code }})</td>
                                    <td>{{ number_format($receivableItem->quantity_ordered, 2) }}</td>
                                    <td><strong>{{ number_format($receivableItem->quantity_received, 2) }}</strong></td>
                                    <td>{{ number_format($conversionFactor, 4) }}</td>
                                    <td>
                                        @if ($variance > 0)
                                            <span class="text-success">+{{ number_format($variance, 2) }}</span>
                                        @elseif($variance < 0)
                                            <span class="text-danger">{{ number_format($variance, 2) }}</span>
                                        @else
                                            <span class="text-muted">0.00</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ number_format($quantityInSmallest, 2) }}
                                        {{ $receivableItem->item->smallestUom->code }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 0 10px rgba(40, 167, 69, 0.6);
            }

            50% {
                transform: scale(1.05);
                box-shadow: 0 0 20px rgba(40, 167, 69, 0.9);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 0 10px rgba(40, 167, 69, 0.6);
            }
        }

        .btn-pulse {
            animation: pulse 2s infinite;
        }
    </style>
@endpush
