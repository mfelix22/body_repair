@extends('layouts.admin')

@section('title', 'Estimasi')
@section('page_title', 'Estimasi')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Estimasi List</h3>
                </div>
                <div class="card-body">

                    <table class="table table-bordered table-striped table-hover" id="estimasiTable">
                        <thead>
                            <tr>
                                <th>Estimasi #</th>
                                <th>Work Order</th>
                                <th>Customer</th>
                                <th>Subtotal</th>
                                <th>Discount</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($estimasis as $est)
                                <tr>
                                    <td>
                                        {{ $est->estimasi_number }}
                                        @if ($est->pendingMyApproval)
                                            <span class="badge badge-warning ml-1">
                                                <i class="fas fa-clock"></i> Awaiting Your Approval
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $est->workOrder->wo_number }}</td>
                                    <td>{{ optional($est->workOrder->customer)->name }}</td>
                                    <td>Rp {{ number_format($est->subtotal, 0, ',', '.') }}</td>
                                    <td>
                                        @if ($est->discount_amount > 0)
                                            Rp {{ number_format($est->discount_amount, 0, ',', '.') }}
                                            <small
                                                class="text-muted">({{ number_format($est->discount_percentage, 2) }}%)</small>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td><strong>Rp {{ number_format($est->total, 0, ',', '.') }}</strong></td>
                                    <td>
                                        @php $badge = $est->getStatusBadge(); @endphp
                                        <span class="badge badge-{{ $badge['color'] }}">{{ $badge['label'] }}</span>
                                    </td>
                                    <td>{{ optional($est->creator)->name }}</td>
                                    <td>{{ $est->created_at->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ route('estimasis.show', $est) }}" class="btn btn-info btn-xs">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted">No Estimasi yet.</td>
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
            $('#estimasiTable').DataTable({
                order: [
                    [8, 'desc']
                ],
                pageLength: 25,
            });
        });
    </script>
@endpush
