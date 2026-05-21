@extends('layouts.admin')

@section('title', $customer->name)
@section('page_title', 'Customer: ' . $customer->name)

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $customer->name }} ({{ $customer->code }})</h3>
                    <div class="card-tools">
                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th width="25%">Code:</th>
                            <td>{{ $customer->code }}</td>
                        </tr>
                        <tr>
                            <th>Name:</th>
                            <td>{{ $customer->name }}</td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td>{{ $customer->phone ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td>{{ $customer->email ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Address:</th>
                            <td>{{ $customer->address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                @if ($customer->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    </table>

                    <hr>
                    <h5>Work Orders ({{ $customer->workOrders->count() }})</h5>
                    @if ($customer->workOrders->isNotEmpty())
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>WO Number</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customer->workOrders as $wo)
                                    <tr>
                                        <td><a href="{{ route('work_orders.show', $wo) }}">{{ $wo->wo_number }}</a></td>
                                        <td>{{ $wo->work_date->format('M d, Y') }}</td>
                                        <td>Rp {{ number_format($wo->grand_total, 0, ',', '.') }}</td>
                                        <td><span class="badge badge-info">{{ ucfirst($wo->status) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted">No work orders yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
