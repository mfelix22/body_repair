@extends('layouts.admin')

@section('title', 'Bon In')
@section('page_title', 'Bon In')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bon In List</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canCreate('receivables'))
                            <a href="{{ route('receivables.create_standalone') }}" class="btn btn-success btn-sm">
                                <i class="fas fa-plus"></i> Create Standalone (Type 3)
                            </a>
                            <a href="{{ route('purchase_orders.index') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Create from PO (Type 1/2)
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Bon In #</th>
                                <th>PO Number</th>
                                <th>Supplier</th>
                                <th>Received Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($receivables as $receivable)
                                <tr>
                                    <td><strong>{{ $receivable->receive_number }}</strong></td>
                                    <td>
                                        @if ($receivable->purchaseOrder && $receivable->purchaseOrder->id)
                                            {{ $receivable->purchaseOrder->po_number }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($receivable->purchaseOrder && $receivable->purchaseOrder->id)
                                            {{ $receivable->purchaseOrder->supplier->name ?? ($receivable->purchaseOrder->supplier_name ?? '-') }}
                                        @elseif ($receivable->supplier)
                                            {{ $receivable->supplier->name }}
                                        @elseif ($receivable->supplier_name)
                                            {{ $receivable->supplier_name }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $receivable->received_date->format('Y-m-d') }}</td>
                                    <td>
                                        @if ($receivable->status === 'on_progress')
                                            <span class="badge badge-secondary">On Progress</span>
                                        @elseif ($receivable->status === 'partial_received')
                                            <span class="badge badge-warning">Partial Received</span>
                                        @elseif ($receivable->status === 'cancelled')
                                            <span class="badge badge-danger">Cancelled</span>
                                        @else
                                            <span class="badge badge-success">Completed</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('receivables.show', $receivable) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No Bon In found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer clearfix">
                    {{ $receivables->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
@endsection
