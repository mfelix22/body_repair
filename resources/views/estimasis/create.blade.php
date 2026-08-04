@extends('layouts.admin')

@section('title', 'New Estimasi')
@section('page_title', 'New Estimasi')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">New Estimasi for {{ $workOrder->wo_number }}</h3>
                </div>
                <form action="{{ route('estimasis.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="work_order_id" value="{{ $workOrder->id }}">
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <table class="table table-bordered table-sm mb-4">
                            <tr>
                                <th style="width:200px">Work Order</th>
                                <td>{{ $workOrder->wo_number }}</td>
                            </tr>
                            <tr>
                                <th>Customer</th>
                                <td>{{ optional($workOrder->customer)->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Panel + Labor Total</th>
                                <td>Rp {{ number_format($workOrder->grand_total, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <th>Pergantian Sparepart</th>
                                <td>Rp {{ number_format($sparepartTotal, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <th>Work Order Total</th>
                                <td><strong>Rp {{ number_format($estimasiSubtotal, 0, ',', '.') }}</strong></td>
                            </tr>
                        </table>

                        <div class="form-group">
                            <label for="discount_percentage">Discount (%)</label>
                            <input type="number" step="0.01" min="0" max="100" name="discount_percentage"
                                id="discount_percentage" class="form-control" value="{{ old('discount_percentage', 0) }}"
                                data-subtotal="{{ $estimasiSubtotal }}">
                            <small class="form-text text-muted">
                                Leave at 0 for a plain estimate with no discount (no approval required).<br>
                                &le; 20% requires <strong>Manager</strong> approval only.
                                &gt; 20% requires <strong>Manager + Director</strong> approval.
                            </small>
                        </div>

                        <table class="table table-bordered table-sm" style="max-width:420px;">
                            <tr>
                                <th>Subtotal</th>
                                <td class="text-right" id="preview-subtotal">
                                    Rp {{ number_format($estimasiSubtotal, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <th>Discount Amount</th>
                                <td class="text-right text-danger" id="preview-discount">Rp 0</td>
                            </tr>
                            <tr class="font-weight-bold">
                                <th>Total</th>
                                <td class="text-right" id="preview-total">
                                    Rp {{ number_format($estimasiSubtotal, 0, ',', '.') }}</td>
                            </tr>
                        </table>

                        <div class="form-group">
                            <label for="notes">Notes (optional)</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Estimasi
                        </button>
                        <a href="{{ route('work_orders.show', $workOrder) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const input = document.getElementById('discount_percentage');
            const subtotal = parseFloat(input.dataset.subtotal) || 0;

            function fmt(n) {
                return 'Rp ' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function update() {
                let pct = parseFloat(input.value) || 0;
                if (pct < 0) pct = 0;
                if (pct > 100) pct = 100;
                const discount = Math.round(subtotal * pct / 100);
                const total = subtotal - discount;
                document.getElementById('preview-discount').textContent = fmt(discount);
                document.getElementById('preview-total').textContent = fmt(total);
            }

            input.addEventListener('input', update);
            update();
        })();
    </script>
@endpush
