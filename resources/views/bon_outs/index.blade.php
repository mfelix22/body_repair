@extends('layouts.admin')
@section('title', 'Bon Out')
@section('page_title', 'Bon Out (Stock Issue)')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bon Out List</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canCreate('bon_outs'))
                            <a href="{{ route('bon_outs.createStandalone') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Create Adjustment Bon Out
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Filter Form --}}
                <div class="card-body pb-0">
                    <form method="GET" action="{{ route('bon_outs.index') }}" class="form-inline flex-wrap gap-2 mb-3">
                        <div class="form-group mr-2 mb-2">
                            <label class="mr-1 font-weight-bold">Month</label>
                            <select name="month" class="form-control form-control-sm">
                                <option value="">All Months</option>
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ (int) $month === $m ? 'selected' : '' }}>
                                        {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="form-group mr-2 mb-2">
                            <label class="mr-1 font-weight-bold">Year</label>
                            <select name="year" class="form-control form-control-sm">
                                <option value="">All Years</option>
                                @foreach ($allYears as $y)
                                    <option value="{{ $y }}"
                                        {{ (string) $year === (string) $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mr-2 mb-2">
                            <label class="mr-1 font-weight-bold">Category</label>
                            <select name="category" class="form-control form-control-sm">
                                <option value="">All Categories</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>
                                        {{ $cat }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <button type="submit" class="btn btn-info btn-sm mr-1">
                                <i class="fas fa-filter mr-1"></i>Filter
                            </button>
                            @if ($month || $year || $category)
                                <a href="{{ route('bon_outs.index') }}" class="btn btn-secondary btn-sm mr-2">
                                    <i class="fas fa-times mr-1"></i>Clear
                                </a>
                                <span class="text-muted small">{{ $bonOuts->total() }} result(s)</span>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="card-body">

                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Bon Out #</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Work Order</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bonOuts as $bonOut)
                                <tr>
                                    <td><strong>{{ $bonOut->bon_out_number }}</strong></td>
                                    <td>
                                        @if ($bonOut->bon_out_type == 1)
                                            <span class="badge badge-info">Workshop</span>
                                        @elseif ($bonOut->bon_out_type == 2)
                                            <span class="badge badge-primary">Regular</span>
                                        @else
                                            <span class="badge badge-warning">Adjustment</span>
                                        @endif
                                    </td>
                                    <td>{{ $bonOut->issued_date->format('Y-m-d') }}</td>
                                    <td>
                                        @if ($bonOut->workOrder)
                                            <a href="{{ route('work_orders.show', $bonOut->workOrder) }}">
                                                {{ $bonOut->workOrder->wo_number }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $bonOut->workOrder->customer->name ?? '-' }}</td>
                                    <td>{{ $bonOut->workOrder->vehicle_plate ?? '-' }}</td>
                                    <td>
                                        @if ($bonOut->status === 'on_progress')
                                            <span class="badge badge-secondary">On Progress</span>
                                        @elseif ($bonOut->status === 'completed')
                                            <span class="badge badge-success">Completed</span>
                                        @else
                                            <span class="badge badge-danger">Cancelled</span>
                                        @endif
                                    </td>
                                    <td>{{ $bonOut->creator->name ?? '-' }}</td>
                                    <td>
                                        <a href="{{ route('bon_outs.show', $bonOut) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No Bon Out records yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer clearfix">
                    {{ $bonOuts->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
@endsection
