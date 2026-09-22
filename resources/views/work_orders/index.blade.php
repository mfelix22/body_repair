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
                    <form method="GET" action="{{ route('work_orders.index') }}" class="form-inline mb-3">
                        <div class="form-group mr-2 mb-2">
                            <label for="month" class="mr-1 font-weight-bold">Month</label>
                            <select name="month" id="month" class="form-control form-control-sm">
                                <option value="">-- All Months --</option>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ (int) $month === $m ? 'selected' : '' }}>
                                        {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group mr-2 mb-2">
                            <label for="year" class="mr-1 font-weight-bold">Year</label>
                            <select name="year" id="year" class="form-control form-control-sm">
                                <option value="">-- All Years --</option>
                                @foreach ($allYears as $y)
                                    <option value="{{ $y }}" {{ (int) $year === (int) $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mr-2 mb-2">
                            <label for="status" class="mr-1 font-weight-bold">Status</label>
                            <select name="status" id="status" class="form-control form-control-sm">
                                <option value="">-- All Statuses --</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <button type="submit" class="btn btn-info btn-sm mr-1 d-inline-flex align-items-center">
                                <i class="fas fa-filter mr-1"></i>Filter
                            </button>
                            @if ($month || $year || $status)
                                <a href="{{ route('work_orders.index') }}" class="btn btn-secondary btn-sm mr-1 d-inline-flex align-items-center">
                                    <i class="fas fa-times mr-1"></i>Clear
                                </a>
                            @endif
                            <a href="{{ route('work_orders.export_excel', request()->query()) }}" class="btn btn-success btn-sm d-inline-flex align-items-center">
                                <i class="fas fa-file-excel mr-1"></i>Export Excel
                            </a>
                        </div>
                    </form>

                    <table id="work-orders-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>WO Number</th>
                                <th>Customer</th>
                                <th>Nomor Polisi</th>
                                <th>Work Date</th>
                                <th>Sparepart</th>
                                <th>Panel</th>
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
                                    <td>{{ $wo->vehicle_plate ?? '-' }}</td>
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

@push('scripts')
    <script>
        $(function() {
            $('#work-orders-table').DataTable({
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search work orders..."
                }
            });
        });
    </script>
@endpush
