@extends('layouts.admin')

@section('title', 'Supplier Details')
@section('page_title', 'Supplier Details')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $supplier->name }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Supplier Name</th>
                            <td>{{ $supplier->name }}</td>
                        </tr>
                        <tr>
                            <th>Contact Person</th>
                            <td>{{ $supplier->contact_person ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $supplier->email ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td>{{ $supplier->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td>{{ $supplier->address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Notes</th>
                            <td>{{ $supplier->notes ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Created At</th>
                            <td>{{ $supplier->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if ($supplier->purchaseOrders && $supplier->purchaseOrders->count() > 0)
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Recent Purchase Orders</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>PO Number</th>
                                    <th>Order Date</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($supplier->purchaseOrders as $po)
                                    <tr>
                                        <td><strong>{{ $po->po_number }}</strong></td>
                                        <td>{{ $po->order_date->format('Y-m-d') }}</td>
                                        <td>Rp {{ number_format($po->total_amount, 0, ',', '.') }}</td>
                                        <td>
                                            @if ($po->status === 'pending')
                                                <span class="badge badge-warning">Pending</span>
                                            @elseif($po->status === 'approved')
                                                <span class="badge badge-success">Approved</span>
                                            @elseif($po->status === 'completed')
                                                <span class="badge badge-info">Completed</span>
                                            @else
                                                <span class="badge badge-secondary">{{ ucfirst($po->status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('purchase_orders.show', $po) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
