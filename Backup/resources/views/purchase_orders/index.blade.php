@extends('layouts.admin')

@section('title', 'Purchase Orders & Service Orders')
@section('page_title', 'Purchase Orders & Service Orders')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Purchase Orders & Service Orders</h3>
                    <div class="card-tools">
                        @if (auth()->user()->hasAnyRole(['purchasing', 'admin', 'super_admin']))
                            <a href="{{ route('purchase_orders.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Create Order
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    {{-- Tabs Navigation --}}
                    <ul class="nav nav-tabs" id="poTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="ppb-tab" data-toggle="tab" href="#ppb" role="tab">
                                <i class="fas fa-shopping-cart"></i> Purchase Orders (PPB)
                                <span class="badge badge-primary ml-1">{{ $purchaseOrders->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="ppj-tab" data-toggle="tab" href="#ppj" role="tab">
                                <i class="fas fa-wrench"></i> Service Orders (PPJ)
                                <span class="badge badge-warning ml-1">{{ $serviceOrders->count() }}</span>
                            </a>
                        </li>
                    </ul>

                    {{-- Tabs Content --}}
                    <div class="tab-content" id="poTabsContent">
                        {{-- PPB Tab --}}
                        <div class="tab-pane fade show active" id="ppb" role="tabpanel">
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>PO Number</th>
                                            <th>Supplier</th>
                                            <th>Order Date</th>
                                            <th>Items</th>
                                            <th>Total Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($purchaseOrders as $po)
                                            <tr>
                                                <td><strong>{{ $po->po_number }}</strong></td>
                                                <td>{{ $po->supplier_name }}</td>
                                                <td>{{ $po->order_date->format('M d, Y') }}</td>
                                                <td>{{ $po->details_count }} items</td>
                                                <td><strong>Rp {{ number_format($po->total_amount, 0, ',', '.') }}</strong>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge badge-{{ $po->status === 'received' ? 'success' : ($po->status === 'approved' ? 'info' : ($po->status === 'closed_shortage' ? 'dark' : ($po->status === 'cancelled' ? 'danger' : 'secondary'))) }}">
                                                        {{ $po->status === 'on_progress' ? 'On Progress' : ($po->status === 'closed_shortage' ? 'Closed with Shortage' : ucfirst($po->status)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('purchase_orders.show', $po) }}"
                                                        class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">No Purchase Orders found
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- PPJ Tab --}}
                        <div class="tab-pane fade" id="ppj" role="tabpanel">
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>SO Number</th>
                                            <th>Supplier</th>
                                            <th>Order Date</th>
                                            <th>Services</th>
                                            <th>Total Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($serviceOrders as $so)
                                            <tr>
                                                <td><strong>{{ $so->po_number }}</strong></td>
                                                <td>{{ $so->supplier_name }}</td>
                                                <td>{{ $so->order_date->format('M d, Y') }}</td>
                                                <td>{{ $so->details_count }} services</td>
                                                <td><strong>Rp {{ number_format($so->total_amount, 0, ',', '.') }}</strong>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge badge-{{ $so->status === 'received' ? 'success' : ($so->status === 'approved' ? 'info' : ($so->status === 'closed_shortage' ? 'dark' : ($so->status === 'cancelled' ? 'danger' : 'secondary'))) }}">
                                                        {{ $so->status === 'on_progress' ? 'On Progress' : ($so->status === 'closed_shortage' ? 'Closed with Shortage' : ucfirst($so->status)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('purchase_orders.show', $so) }}"
                                                        class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">No Service Orders found
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
