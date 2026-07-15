@extends('layouts.admin')
@section('title', 'Bon Out Detail')
@section('page_title', 'Bon Out: ' . $bonOut->bon_out_number)

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bon Out – {{ $bonOut->bon_out_number }}</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canPrint('bon_outs'))
                            <a href="{{ \URL::temporarySignedRoute('bon_outs.print', now()->addMinutes(5), $bonOut) }}"
                                target="_blank" class="btn btn-default btn-sm">
                                <i class="fas fa-print"></i> Print
                            </a>
                        @endif
                        @if (
                            $bonOut->status === 'on_progress' &&
                                auth()->user()->hasAnyRole(['warehouse', 'admin', 'super_admin']))
                            @if ($bonOut->bon_out_type == 3)
                                <form action="{{ route('bon_outs.complete', $bonOut) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Complete this Adjustment Bon Out? Stock will be deducted for all items.')">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Complete &amp; Deduct Stock
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('bon_outs.edit', $bonOut) }}" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="{{ route('bon_outs.complete', $bonOut) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('Complete this Bon Out? Leftover stock will be returned and an invoice will be generated.')">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Complete &amp; Return Leftover
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('bon_outs.cancel', $bonOut) }}" method="POST" class="d-inline"
                                onsubmit="return confirm('Cancel this Bon Out?')">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-sm">
                                    <i class="fas fa-ban"></i> Cancel
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('bon_outs.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="card-body">

                    <div class="row">
                        <div class="col-md-5">
                            <h6>Bon Out Info</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Bon Out #:</th>
                                    <td><strong>{{ $bonOut->bon_out_number }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Type:</th>
                                    <td>
                                        @if ($bonOut->bon_out_type == 1)
                                            <span class="badge badge-info">Workshop Materials</span>
                                        @elseif ($bonOut->bon_out_type == 2)
                                            <span class="badge badge-primary">Regular Purchase</span>
                                        @else
                                            <span class="badge badge-warning">Stock Adjustment</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        @if ($bonOut->status === 'on_progress')
                                            <span class="badge badge-secondary">On Progress</span>
                                        @elseif ($bonOut->status === 'completed')
                                            <span class="badge badge-success">Completed</span>
                                        @else
                                            <span class="badge badge-danger">Cancelled</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Issued Date:</th>
                                    <td>{{ $bonOut->issued_date->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Issued To:</th>
                                    <td>{{ $bonOut->issued_to ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Purpose:</th>
                                    <td>{{ $bonOut->purpose ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Notes:</th>
                                    <td>{{ $bonOut->notes ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-4">
                            <h6>Work Order</h6>
                            @if ($bonOut->workOrder)
                                <table class="table table-sm">
                                    <tr>
                                        <th>WO #:</th>
                                        <td>
                                            <a href="{{ route('work_orders.show', $bonOut->workOrder) }}">
                                                {{ $bonOut->workOrder->wo_number }}
                                            </a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Customer:</th>
                                        <td>{{ $bonOut->workOrder->customer->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Panels:</th>
                                        <td>
                                            @forelse ($bonOut->workOrder->labors->where('is_extra', false) as $wl)
                                                <span class="badge badge-secondary">{{ $wl->labor->labor_code ?? $wl->description }}</span>
                                            @empty
                                                -
                                            @endforelse
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Vehicle:</th>
                                        <td>{{ $bonOut->workOrder->vehicle_plate ?? '-' }}</td>
                                    </tr>
                                </table>
                            @else
                                <p class="text-muted">No Work Order linked.</p>
                            @endif
                        </div>

                        <div class="col-md-3">
                            <h6>Audit</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Created By:</th>
                                    <td>{{ $bonOut->creator->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Created At:</th>
                                    <td>{{ $bonOut->created_at->format('d M Y H:i') }}</td>
                                </tr>
                                @if ($bonOut->status === 'completed')
                                    <tr>
                                        <th>Completed By:</th>
                                        <td>{{ $bonOut->completer->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Completed At:</th>
                                        <td>{{ $bonOut->completed_at?->format('d M Y H:i') ?? '-' }}</td>
                                    </tr>
                                @endif
                            </table>

                            @if ($bonOut->status === 'on_progress')
                                <div class="alert alert-warning py-2">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    @if ($bonOut->bon_out_type == 3)
                                        Stock has <strong>not</strong> been deducted yet.
                                    @else
                                        Leftover stock has <strong>not</strong> been returned yet.
                                    @endif
                                </div>
                            @elseif ($bonOut->status === 'completed')
                                <div class="alert alert-success py-2">
                                    <i class="fas fa-check-circle"></i>
                                    @if ($bonOut->bon_out_type == 3)
                                        Stock adjustment completed. Items deducted from inventory.
                                    @else
                                        Leftover returned to stock. Invoice generated.
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <hr>
                    <h6><i class="fas fa-boxes"></i>
                        {{ $bonOut->bon_out_type == 3 ? 'Items Adjusted Out' : 'Material Usage' }}</h6>
                    @php
                        $hasExtraPricedItems =
                            $bonOut->items->whereNull('work_order_item_id')->where('unit_price', '>', 0)->count() > 0;
                    @endphp
                    @if ($hasExtraPricedItems && $bonOut->workOrder)
                        <div class="alert alert-info py-2">
                            <i class="fas fa-tag"></i>
                            Items marked <span class="badge badge-success">Billed</span> have a selling price and
                            @if ($bonOut->status === 'completed')
                                have been added to <a href="{{ route('work_orders.show', $bonOut->workOrder) }}">WO
                                    {{ $bonOut->workOrder->wo_number }}</a> billing.
                            @else
                                will be added to WO billing when this Bon Out is completed.
                            @endif
                        </div>
                    @endif
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Item</th>
                                    @if ($bonOut->bon_out_type != 3)
                                        <th class="text-right">Demand Qty</th>
                                    @endif
                                    <th class="text-right">{{ $bonOut->bon_out_type == 3 ? 'Quantity' : 'Actual Used' }}
                                    </th>
                                    @if ($bonOut->bon_out_type != 3)
                                        <th class="text-right">Leftover Returned</th>
                                        <th class="text-right">Selling Price</th>
                                    @endif
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bonOut->items as $bi)
                                    @php
                                        $uomCode = $bi->item->smallestUom->code ?? '-';
                                        $demand = (float) $bi->demand_quantity;
                                        $actual = (float) $bi->actual_quantity;
                                        $leftover = max(0, $demand - $actual);
                                        $isExtra = $bi->work_order_item_id === null && $bonOut->bon_out_type != 3;
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <strong>[{{ $bi->item->code }}]</strong> {{ $bi->item->name }}
                                            @if ($isExtra)
                                                <span class="badge badge-secondary ml-1">Extra</span>
                                            @endif
                                        </td>
                                        @if ($bonOut->bon_out_type != 3)
                                            <td class="text-right">
                                                {{ number_format($demand, 2) }} {{ $uomCode }}
                                            </td>
                                        @endif
                                        <td
                                            class="text-right {{ $bonOut->bon_out_type != 3 && $actual < $demand ? 'text-info' : '' }}">
                                            {{ number_format($actual, 2) }} {{ $uomCode }}
                                        </td>
                                        @if ($bonOut->bon_out_type != 3)
                                            <td
                                                class="text-right {{ $leftover > 0 ? 'text-success font-weight-bold' : 'text-muted' }}">
                                                {{ number_format($leftover, 2) }} {{ $uomCode }}
                                            </td>
                                            <td class="text-right">
                                                @if ($isExtra && $bi->unit_price > 0)
                                                    <span class="badge badge-success">Billed</span>
                                                    Rp {{ number_format($bi->unit_price, 0, ',', '.') }}
                                                    <br><small class="text-muted">Total: Rp
                                                        {{ number_format($actual * $bi->unit_price, 0, ',', '.') }}</small>
                                                @elseif ($isExtra)
                                                    <span class="text-muted">Internal use</span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        @endif
                                        <td>{{ $bi->remark ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
