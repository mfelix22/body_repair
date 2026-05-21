@extends('layouts.admin')

@section('title', 'Sales Orders')
@section('page_title', 'Sales Orders')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Sales Orders</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canCreate('sales_orders'))
                            <a href="{{ route('sales_orders.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Create Sales Order
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">

                    <table class="table table-bordered table-striped table-hover" id="soTable">
                        <thead class="bg-light">
                            <tr>
                                <th>SO Number</th>
                                <th>Customer</th>
                                <th>Order Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($salesOrders as $so)
                                <tr>
                                    <td>
                                        <a href="{{ route('sales_orders.show', $so) }}">{{ $so->so_number }}</a>
                                    </td>
                                    <td>{{ $so->customer->name }}</td>
                                    <td>{{ $so->order_date->format('d M Y') }}</td>
                                    <td class="text-right">Rp {{ number_format($so->material_total, 0, ',', '.') }}</td>
                                    <td>
                                        @php
                                            $color = match ($so->status) {
                                                'draft' => 'warning',
                                                'confirmed' => 'success',
                                                'cancelled' => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $color }}">{{ ucfirst($so->status) }}</span>
                                    </td>
                                    <td>{{ $so->creator->name ?? '-' }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('sales_orders.show', $so) }}" class="btn btn-info btn-xs">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No sales orders yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $('#soTable').DataTable({
                order: [
                    [2, 'desc']
                ]
            });
        });
    </script>
@endpush
