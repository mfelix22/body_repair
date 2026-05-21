@extends('layouts.admin')

@section('title', 'Work Orders')
@section('page_title', 'Work Orders')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Work Orders</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canCreate('work_orders'))
                            <a href="{{ route('work_orders.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Create Work Order
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">

                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>WO Number</th>
                                <th>Customer</th>
                                <th>Work Date</th>
                                <th>Items</th>
                                <th>Labor</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($wos as $wo)
                                <tr>
                                    <td><strong>{{ $wo->wo_number }}</strong></td>
                                    <td>{{ $wo->customer->name }}</td>
                                    <td>{{ $wo->work_date->format('M d, Y') }}</td>
                                    <td>{{ $wo->items_count }}</td>
                                    <td>{{ $wo->labors_count }}</td>
                                    <td><strong>Rp. {{ number_format($wo->grand_total, 2) }}</strong></td>
                                    <td>
                                        @include('partials.wo_status_badge', ['status' => $wo->status])
                                        @if ($wo->proformaInvoice)
                                            @php
                                                $pfColor = match ($wo->proformaInvoice->status) {
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    'no_discount' => 'secondary',
                                                    default => 'warning',
                                                };
                                                $pfLabel = match ($wo->proformaInvoice->status) {
                                                    'approved' => 'PF: Approved',
                                                    'rejected' => 'PF: Rejected',
                                                    'no_discount' => 'PF: No Disc.',
                                                    default => 'PF: Pending',
                                                };
                                            @endphp
                                            <br><span class="badge badge-{{ $pfColor }}">{{ $pfLabel }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('work_orders.show', $wo) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if ($wo->status === 'on_progress' && \App\Helpers\PermissionHelper::canUpdate('work_orders'))
                                            <a href="{{ route('work_orders.edit', $wo) }}" class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
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
