@extends('layouts.admin')

@section('title', $invoice->invoice_number)
@section('page_title', 'Invoice: ' . $invoice->invoice_number)

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $invoice->invoice_number }}</h3>
                    <div class="card-tools">
                        <a href="{{ \URL::temporarySignedRoute('invoices.print', now()->addMinutes(5), $invoice) }}"
                            target="_blank" class="btn btn-default btn-sm">
                            <i class="fas fa-print"></i> Print
                        </a>
                        @if ($invoice->status === 'on_progress')
                            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        @endif
                        <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : 'info' }}">
                            {{ $invoice->status === 'on_progress' ? 'On Progress' : ucfirst($invoice->status) }}
                        </span>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Invoice Details</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Invoice #:</th>
                                    <td>{{ $invoice->invoice_number }}</td>
                                </tr>
                                <tr>
                                    <th>Invoice Date:</th>
                                    <td>{{ $invoice->invoice_date->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Due Date:</th>
                                    <td>{{ $invoice->due_date?->format('M d, Y') ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td><span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : 'info' }}">
                                            {{ $invoice->status === 'on_progress' ? 'On Progress' : ucfirst($invoice->status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6>Customer Details</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Customer:</th>
                                    <td><a
                                            href="{{ route('customers.show', $invoice->customer) }}">{{ $invoice->customer->name }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Work Order:</th>
                                    <td><a
                                            href="{{ route('work_orders.show', $invoice->workOrder) }}">{{ $invoice->workOrder->wo_number }}</a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>
                    <h6>Work Order Items</h6>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->workOrder->items as $item)
                                <tr>
                                    <td>{{ $item->item->name }}</td>
                                    <td>{{ number_format($item->actual_quantity ?? ($item->demand_quantity ?? 0), 2) }}
                                        {{ $item->item->smallestUom->code }}</td>
                                    <td>
                                        @if (!is_null($item->unit_price) && (float) $item->unit_price > 0)
                                            Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">Included in Paket</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!is_null($item->total_price) && (float) $item->total_price > 0)
                                            Rp {{ number_format($item->total_price, 0, ',', '.') }}
                                        @else
                                            <span class="text-muted">Included in Paket</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <hr>
                    <h6>Labor</h6>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Qty</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->workOrder->labors as $labor)
                                <tr>
                                    <td>{{ $labor->description }}</td>
                                    <td>{{ rtrim(rtrim(number_format((float) ($labor->qty ?? 1), 2, '.', ''), '0'), '.') }}
                                    </td>
                                    <td>{{ $labor->remarks ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <small class="text-muted">Labor fee is fixed at Rp 75.000 per paket and already included in
                        total.</small>

                    <hr>
                    <div class="row">
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <th width="50%">Subtotal:</th>
                                    <td><strong>Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Discount:</th>
                                    <td><strong>{{ number_format($invoice->discount_percentage ?? 0, 2) }}% (-Rp
                                            {{ number_format($invoice->discount_amount, 0, ',', '.') }})</strong>
                                    </td>
                                </tr>
                                <tr style="border-top: 2px solid #dee2e6; font-size: 1.3em;">
                                    <th>Grand Total:</th>
                                    <td><strong>Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    @if ($invoice->notes)
                        <hr>
                        <h6>Notes</h6>
                        <p>{{ $invoice->notes }}</p>
                    @endif

                    {{-- COGM / COGS Section: visible to admin, super_admin, finance, director only --}}
                    @if (auth()->user() &&
                            auth()->user()->hasAnyRole(['admin', 'super_admin', 'finance', 'director', 'accounting']))
                        @if ($invoice->cogm !== null)
                            <hr>
                            <div class="card card-warning card-outline">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-calculator mr-1"></i> COGS / COGM</h3>
                                    <small class="text-muted ml-2">(Finance / Director / Accounting view only)</small>
                                    <div class="card-tools">
                                        <a href="{{ route('invoices.cogsReport', $invoice) }}" target="_blank"
                                            class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-print"></i> Print COGS Report
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    {{-- Per-item material COGS breakdown from ALL Bon Outs --}}
                                    @php
                                        // Aggregate materials from ALL completed Bon Outs
                                        $completedBonOuts = $invoice->workOrder->bonOuts->where('status', 'completed');
                                        $aggregatedItems = [];

                                        foreach ($completedBonOuts as $bonOut) {
                                            foreach ($bonOut->items as $item) {
                                                $itemId = $item->item_id;

                                                if (!isset($aggregatedItems[$itemId])) {
                                                    $aggregatedItems[$itemId] = [
                                                        'item' => $item->item,
                                                        'total_qty' => 0,
                                                        'unit_cost' => (float) $item->unit_cost,
                                                        'total_cost' => 0,
                                                    ];
                                                }

                                                $qty = (float) $item->actual_quantity;
                                                $cost = (float) $item->unit_cost;

                                                $aggregatedItems[$itemId]['total_qty'] += $qty;
                                                $aggregatedItems[$itemId]['total_cost'] += $qty * $cost;
                                            }
                                        }

                                        // Check which Work Order items were NOT used in any Bon Out
                                        $woItemIds = $invoice->workOrder->items->pluck('item_id')->toArray();
                                        $bonOutItemIds = array_keys($aggregatedItems);
                                        $unusedItemIds = array_diff($woItemIds, $bonOutItemIds);
                                        $unusedItems = $invoice->workOrder->items->whereIn('item_id', $unusedItemIds);
                                    @endphp

                                    @if (count($aggregatedItems) > 0)
                                        <div class="px-3 pt-3">
                                            <h6 class="mb-2"><i class="fas fa-boxes mr-1"></i> Material COGS Breakdown
                                                <small class="text-muted">(Aggregated from {{ $completedBonOuts->count() }}
                                                    Bon Out(s))</small>
                                            </h6>
                                            @if ($unusedItems->count() > 0)
                                                <div class="alert alert-warning alert-sm mb-2">
                                                    <i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong>
                                                    {{ $unusedItems->count() }} item(s) from Work Order BOM were NOT used
                                                    in any Bon Out:
                                                    <ul class="mb-0 mt-1">
                                                        @foreach ($unusedItems as $unused)
                                                            <li><strong>[{{ $unused->item->code }}]</strong>
                                                                {{ $unused->item->name }}
                                                                <small class="text-muted">(Planned:
                                                                    {{ number_format($unused->demand_quantity, 2) }}
                                                                    {{ $unused->item->smallestUom->code ?? '' }})</small>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                        <table class="table table-sm table-striped mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Item</th>
                                                    <th class="text-right">Qty Used</th>
                                                    <th class="text-right">Unit Cost</th>
                                                    <th class="text-right">Total Cost</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $totalItemCogs = 0;
                                                    $rowNum = 1;
                                                @endphp
                                                @foreach ($aggregatedItems as $itemData)
                                                    @php
                                                        $qty = $itemData['total_qty'];
                                                        $cost = $itemData['unit_cost'];
                                                        $lineCost = $itemData['total_cost'];
                                                        $totalItemCogs += $lineCost;
                                                        $uomCode = $itemData['item']->smallestUom->code ?? '-';
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $rowNum++ }}</td>
                                                        <td>
                                                            <strong>[{{ $itemData['item']->code }}]</strong>
                                                            {{ $itemData['item']->name }}
                                                        </td>
                                                        <td class="text-right">{{ number_format($qty, 2) }}
                                                            {{ $uomCode }}</td>
                                                        <td class="text-right">Rp {{ number_format($cost, 0, ',', '.') }}
                                                        </td>
                                                        <td class="text-right">Rp
                                                            {{ number_format($lineCost, 0, ',', '.') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-secondary font-weight-bold">
                                                    <td colspan="4" class="text-right">Total Material COGS:</td>
                                                    <td class="text-right">Rp
                                                        {{ number_format($totalItemCogs, 0, ',', '.') }}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    @endif

                                    {{-- Summary totals --}}
                                    <table class="table table-sm mb-0 mt-2">
                                        @php
                                            $materialCogs = (float) ($invoice->cogm_material ?? 0);
                                            $grossProfit = ($invoice->grand_total ?? 0) - $materialCogs;
                                            $margin =
                                                ($invoice->grand_total ?? 0) > 0
                                                    ? ($grossProfit / $invoice->grand_total) * 100
                                                    : 0;
                                        @endphp
                                        <tr>
                                            <th width="50%">Materials Cost (COGS):</th>
                                            <td>Rp {{ number_format($materialCogs, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr class="table-warning">
                                            <th><strong>Total COGS (Material Only):</strong></th>
                                            <td><strong>Rp {{ number_format($materialCogs, 0, ',', '.') }}</strong></td>
                                        </tr>
                                        <tr>
                                            <th>Revenue (Grand Total):</th>
                                            <td>Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr class="{{ $grossProfit >= 0 ? 'table-success' : 'table-danger' }}">
                                            <th>Gross Profit:</th>
                                            <td>
                                                <strong>Rp {{ number_format($grossProfit, 0, ',', '.') }}</strong>
                                                <span class="ml-2 text-muted">({{ number_format($margin, 1) }}%)</span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="card-footer">
                    @if (in_array($invoice->status, ['on_progress', 'sent', 'partial']) &&
                            auth()->user()->hasAnyRole(['admin', 'super_admin', 'finance']))
                        <form action="{{ route('invoices.markAsPaid', $invoice) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success">Mark as Paid</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
