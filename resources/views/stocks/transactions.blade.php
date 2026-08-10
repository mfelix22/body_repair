@extends('layouts.admin')

@section('title', 'Stock Transactions')
@section('page_title', 'Stock Transactions')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Stock Transactions</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool d-inline-flex align-items-center" data-toggle="collapse" data-target="#filterCollapse">
                            <i class="fas fa-filter mr-1"></i>Filter
                        </button>
                    </div>
                </div>

                <!-- Filter Form -->
                <div id="filterCollapse"
                    class="collapse {{ request()->hasAny(['item_id', 'type', 'reference', 'month', 'year', 'category']) ? 'show' : '' }}">
                    <div class="card-body border-bottom">
                        <form method="GET" action="{{ route('stocks.transactions') }}" id="filterForm">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Item</label>
                                        <select name="item_id" id="item_id" class="form-control select2"
                                            style="width: 100%;">
                                            <option value="">All Items</option>
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}"
                                                    {{ request('item_id') == $item->id ? 'selected' : '' }}>
                                                    {{ $item->code }} - {{ $item->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Category</label>
                                        <select name="category" class="form-control">
                                            <option value="">All Categories</option>
                                            @foreach ($categories as $cat)
                                                <option value="{{ $cat }}"
                                                    {{ request('category') === $cat ? 'selected' : '' }}>
                                                    {{ $cat }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Month</label>
                                        <select name="month" class="form-control">
                                            <option value="">All Months</option>
                                            @for ($m = 1; $m <= 12; $m++)
                                                <option value="{{ $m }}"
                                                    {{ (int) request('month') === $m ? 'selected' : '' }}>
                                                    {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group">
                                        <label>Year</label>
                                        <select name="year" class="form-control">
                                            <option value="">All</option>
                                            @foreach ($allYears as $y)
                                                <option value="{{ $y }}"
                                                    {{ request('year') == $y ? 'selected' : '' }}>
                                                    {{ $y }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group">
                                        <label>Type</label>
                                        <select name="type" class="form-control">
                                            <option value="">All</option>
                                            <option value="in" {{ request('type') === 'in' ? 'selected' : '' }}>In
                                            </option>
                                            <option value="out" {{ request('type') === 'out' ? 'selected' : '' }}>Out
                                            </option>
                                            <option value="adjustment"
                                                {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adj</option>
                                            <option value="opening"
                                                {{ request('type') === 'opening' ? 'selected' : '' }}>Opening</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Reference</label>
                                        <select name="reference" class="form-control">
                                            <option value="">All References</option>
                                            @foreach ($referenceTypes as $refType)
                                                <option value="{{ $refType }}"
                                                    {{ request('reference') === $refType ? 'selected' : '' }}>
                                                    {{ $refType }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-search mr-1"></i>Search
                                    </button>
                                    @if (request()->hasAny(['item_id', 'type', 'reference', 'month', 'year', 'category']))
                                        <a href="{{ route('stocks.transactions') }}" class="btn btn-secondary btn-sm ml-1">
                                            <i class="fas fa-times mr-1"></i>Clear
                                        </a>
                                        <span class="ml-2 text-muted small">{{ $transactions->total() }} result(s)</span>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        @php
                            $canViewCost = auth()->user() && auth()->user()->hasAnyRole(['accounting', 'warehouse', 'director', 'viewer', 'super_admin']);
                        @endphp
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Item</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                @if ($canViewCost)
                                    <th>Unit Cost</th>
                                @endif
                                <th>Calculation</th>
                                <th>Balance</th>
                                <th>Reference</th>
                                <th>Notes</th>
                                <th>Created By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $transaction)
                                @php
                                    // Calculate previous balance
                                    $previousBalance = $transaction->balance_after - $transaction->quantity;
                                @endphp
                                <tr>
                                    <td>{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                                    <td>{{ $transaction->item->name }}</td>
                                    <td>
                                        @if ($transaction->transaction_type === 'in')
                                            <span class="badge badge-success">In</span>
                                        @elseif($transaction->transaction_type === 'out')
                                            <span class="badge badge-danger">Out</span>
                                        @elseif($transaction->transaction_type === 'opening')
                                            <span class="badge badge-info">Opening</span>
                                        @else
                                            <span class="badge badge-warning">Adjustment</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $transaction->quantity > 0 ? '+' : '' }}{{ number_format($transaction->quantity, 2) }}</strong>
                                    </td>
                                    @if ($canViewCost)
                                        <td>
                                            @if ($transaction->unit_cost > 0)
                                                <span class="text-monospace">Rp {{ number_format($transaction->unit_cost, 2) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="font-italic text-muted">
                                        {{ number_format($previousBalance, 2) }}
                                        {{ $transaction->quantity > 0 ? '+' : '-' }}
                                        {{ number_format(abs($transaction->quantity), 2) }} =
                                        <strong>{{ number_format($transaction->balance_after, 2) }}</strong>
                                    </td>
                                    <td>{{ number_format($transaction->balance_after, 2) }}</td>
                                    <td>{{ $transaction->reference_type ?? '-' }}</td>
                                    <td>{{ $transaction->notes ?? '-' }}</td>
                                    <td>{{ $transaction->creator->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer clearfix d-flex justify-content-end">
                    {{ $transactions->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#item_id').select2({
                theme: 'bootstrap4',
                placeholder: 'Select an item...',
                allowClear: true
            });
        });
    </script>
@endpush
