@extends('layouts.admin')

@section('title', $salesOrder->so_number)
@section('page_title', 'Sales Order: ' . $salesOrder->so_number)

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $salesOrder->so_number }}</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canPrint('sales_orders'))
                            <a href="{{ \URL::temporarySignedRoute('sales_orders.print', now()->addMinutes(5), $salesOrder) }}"
                                target="_blank" class="btn btn-info btn-sm">
                                <i class="fas fa-print"></i> Print
                            </a>
                        @endif

                        @if ($salesOrder->status === 'draft')
                            @if (\App\Helpers\PermissionHelper::canUpdate('sales_orders'))
                                <a href="{{ route('sales_orders.edit', $salesOrder) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="{{ route('sales_orders.confirm', $salesOrder) }}" method="POST"
                                    class="d-inline" onsubmit="return confirm('Confirm this Sales Order?')">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Confirm
                                    </button>
                                </form>
                                <form action="{{ route('sales_orders.cancel', $salesOrder) }}" method="POST"
                                    class="d-inline" onsubmit="return confirm('Cancel this Sales Order?')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </form>
                            @endif
                        @elseif ($salesOrder->status === 'confirmed')
                            @if (\App\Helpers\PermissionHelper::canUpdate('sales_orders'))
                                <form action="{{ route('sales_orders.cancel', $salesOrder) }}" method="POST"
                                    class="d-inline" onsubmit="return confirm('Cancel this confirmed Sales Order?')">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                </form>
                            @endif
                        @endif

                        @php
                            $badgeColor = match ($salesOrder->status) {
                                'draft' => 'warning',
                                'confirmed' => 'success',
                                'cancelled' => 'secondary',
                            };
                        @endphp
                        <span class="badge badge-{{ $badgeColor }} ml-1" style="font-size:0.85rem;">
                            {{ ucfirst($salesOrder->status) }}
                        </span>
                    </div>
                </div>

                <div class="card-body">

                    <div class="row">
                        <div class="col-md-4">
                            <table class="table table-sm">
                                <tr>
                                    <th>SO Number</th>
                                    <td>{{ $salesOrder->so_number }}</td>
                                </tr>
                                <tr>
                                    <th>Order Date</th>
                                    <td>{{ $salesOrder->order_date->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Customer</th>
                                    <td>
                                        <a href="{{ route('customers.show', $salesOrder->customer) }}">
                                            {{ $salesOrder->customer->name }}
                                        </a>
                                    </td>
                                </tr>
                                @if ($salesOrder->description)
                                    <tr>
                                        <th>Description</th>
                                        <td>{{ $salesOrder->description }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>Created By</th>
                                    <td>{{ $salesOrder->creator->name ?? '-' }}</td>
                                </tr>
                                @if ($salesOrder->notes)
                                    <tr>
                                        <th>Notes</th>
                                        <td>{{ $salesOrder->notes }}</td>
                                    </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-8">
                            <h6 class="font-weight-bold">Items</h6>
                            <table class="table table-bordered table-sm">
                                <thead class="bg-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Item</th>
                                        <th>UOM</th>
                                        <th class="text-right">Qty</th>
                                        <th class="text-right">Unit Price</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($salesOrder->items as $i => $line)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ $line->item->code }} — {{ $line->item->name }}</td>
                                            <td>{{ $line->item->smallestUom->code ?? '-' }}</td>
                                            <td class="text-right">{{ number_format($line->quantity, 2) }}</td>
                                            <td class="text-right">Rp {{ number_format($line->unit_price, 0, ',', '.') }}
                                            </td>
                                            <td class="text-right">Rp {{ number_format($line->total_price, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-success">
                                        <th colspan="5" class="text-right">Total</th>
                                        <th class="text-right">Rp
                                            {{ number_format($salesOrder->material_total, 0, ',', '.') }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <a href="{{ route('sales_orders.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
