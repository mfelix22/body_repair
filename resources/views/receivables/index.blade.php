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

                {{-- Filter Form --}}
                <div class="card-body pb-0">
                    <form method="GET" action="{{ route('receivables.index') }}" class="form-inline flex-wrap gap-2 mb-3">
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
                                <a href="{{ route('receivables.index') }}" class="btn btn-secondary btn-sm mr-2">
                                    <i class="fas fa-times mr-1"></i>Clear
                                </a>
                                <span class="text-muted small">{{ $receivables->total() }} result(s)</span>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="card-body">

                    <table id="receivables-table" class="table table-bordered table-striped">
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
                                            @if ($receivable->printed_at)
                                                <br><small class="text-muted"><i class="fas fa-print"></i>
                                                    {{ $receivable->printed_at->format('d/m/Y') }}</small>
                                            @endif
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
                @push('scripts')
                    <script>
                        $(document).ready(function() {
                            $('#receivables-table').DataTable({
                                "paging": false,
                                "order": [],
                                "language": {
                                    "search": "Search:",
                                }
                            });
                        });
                    </script>
                @endpush
            </div>
        </div>
    </div>
@endsection
