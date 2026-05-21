@extends('layouts.admin')

@section('title', 'Credit Notes')
@section('page_title', 'Credit Notes')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Credit Notes</h3>
                </div>

                <div class="card-body">
                    <table class="table table-bordered table-striped" id="creditNotesTable">
                        <thead>
                            <tr>
                                <th>Credit Note #</th>
                                <th>Ref Invoice #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($creditNotes as $cn)
                                <tr>
                                    <td><strong>{{ $cn->credit_note_number }}</strong></td>
                                    <td>
                                        <a href="{{ route('invoices.show', $cn->invoice_id) }}">
                                            {{ $cn->invoice->invoice_number ?? '-' }}
                                        </a>
                                    </td>
                                    <td>{{ $cn->customer->name ?? '-' }}</td>
                                    <td>{{ $cn->credit_note_date->format('M d, Y') }}</td>
                                    <td><strong>Rp {{ number_format($cn->grand_total, 0, ',', '.') }}</strong></td>
                                    <td>
                                        <a href="{{ route('credit_notes.show', $cn) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if (\App\Helpers\PermissionHelper::canPrint('invoices'))
                                            <a href="{{ \URL::temporarySignedRoute('credit_notes.print', now()->addMinutes(5), $cn) }}"
                                                target="_blank" class="btn btn-default btn-sm" title="Print">
                                                <i class="fas fa-print"></i>
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
            $('#creditNotesTable').DataTable({
                order: [
                    [3, 'desc']
                ],
                pageLength: 25,
            });
        });
    </script>
@endpush
