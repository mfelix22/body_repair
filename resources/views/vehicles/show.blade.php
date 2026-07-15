@extends('layouts.admin')

@section('title', 'Vehicle – ' . $vehicle->plate_number)
@section('page_title', 'Vehicle Service History')

@section('content')
    <div class="row">
        {{-- Vehicle Info Card --}}
        <div class="col-md-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-car mr-1"></i> {{ $vehicle->plate_number }}</h3>
                    <div class="card-tools">
                        @if (\App\Helpers\PermissionHelper::canUpdate('vehicles'))
                            <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-warning btn-xs">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        @endif
                        <a href="{{ route('vehicles.index') }}" class="btn btn-secondary btn-xs">
                            <i class="fas fa-list"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th width="40%" class="pl-3">Customer</th>
                            <td>
                                @if ($vehicle->customer)
                                    <a href="{{ route('customers.show', $vehicle->customer) }}">
                                        {{ $vehicle->customer->name }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="pl-3">Brand / Model</th>
                            <td>{{ $vehicle->brand ?? '-' }} {{ $vehicle->model ?? '' }}</td>
                        </tr>
                        <tr>
                            <th class="pl-3">Year</th>
                            <td>{{ $vehicle->year ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="pl-3">Color</th>
                            <td>{{ $vehicle->color ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="pl-3">Chasis No.</th>
                            <td>{{ $vehicle->chasis_no ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th class="pl-3">Status</th>
                            <td>
                                @if ($vehicle->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                        @if ($vehicle->notes)
                            <tr>
                                <th class="pl-3">Notes</th>
                                <td>{{ $vehicle->notes }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Summary Stats --}}
            <div class="row">
                <div class="col-6">
                    <div class="small-box bg-info" style="margin-bottom:12px;">
                        <div class="inner">
                            <h3>{{ $stats['total_services'] }}</h3>
                            <p>Total Services</p>
                        </div>
                        <div class="icon"><i class="fas fa-tools"></i></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="small-box bg-success" style="margin-bottom:12px;">
                        <div class="inner">
                            <h3>{{ $stats['completed_services'] }}</h3>
                            <p>Completed</p>
                        </div>
                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="small-box bg-warning" style="margin-bottom:12px;">
                        <div class="inner">
                            <h3>{{ $stats['active_work_orders'] }}</h3>
                            <p>Active WOs</p>
                        </div>
                        <div class="icon"><i class="fas fa-spinner"></i></div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="small-box bg-primary" style="margin-bottom:12px;">
                        <div class="inner">
                            <h3 style="font-size:0.95rem;">Rp {{ number_format($stats['total_billed'], 0, ',', '.') }}</h3>
                            <p>Total Billed</p>
                        </div>
                        <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    </div>
                </div>
            </div>

            @if ($stats['last_service_date'])
                <div class="callout callout-info">
                    <h6 class="mb-0"><i class="fas fa-calendar-check mr-1"></i> Last Service</h6>
                    <p class="mb-0">{{ $stats['last_service_date']->format('d M Y') }}
                        <small class="text-muted">({{ $stats['last_service_date']->diffForHumans() }})</small>
                    </p>
                </div>
            @endif

            @if ($stats['active_work_orders'] > 0)
                <div class="callout callout-warning">
                    <h6 class="mb-0"><i class="fas fa-tools mr-1"></i> Currently In Service</h6>
                    <p class="mb-0 text-warning font-weight-bold">{{ $stats['active_work_orders'] }} active work order(s)
                    </p>
                </div>
            @endif
        </div>

        {{-- Service History --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title"><i class="fas fa-history mr-1"></i> Service History</h3>
                    @if (\App\Helpers\PermissionHelper::canCreate('work_orders'))
                        <a href="{{ route('work_orders.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> New Work Order
                        </a>
                    @endif
                </div>
                <div class="card-body p-0">
                    @forelse ($vehicle->workOrders as $wo)
                        @php
                            $woStatusClass = match ($wo->status) {
                                'completed', 'invoiced' => 'success',
                                'in_progress', 'on_progress' => 'warning',
                                'cancelled' => 'danger',
                                default => 'secondary',
                            };
                            $invoice = $wo->invoice;
                            $invoiceStatusClass = match ($invoice?->status) {
                                'paid' => 'success',
                                'partial' => 'warning',
                                'sent' => 'info',
                                'cancelled' => 'danger',
                                default => 'secondary',
                            };
                        @endphp
                        <div class="border-bottom px-3 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div style="flex:1;">
                                    <div class="d-flex align-items-center flex-wrap">
                                        <a href="{{ route('work_orders.show', $wo) }}" class="font-weight-bold mr-2"
                                            style="font-size:0.95rem;">
                                            {{ $wo->wo_number }}
                                        </a>
                                        <span class="badge badge-{{ $woStatusClass }} mr-1">
                                            {{ ucwords(str_replace('_', ' ', $wo->status)) }}
                                        </span>
                                        @if ($invoice)
                                            <span class="badge badge-{{ $invoiceStatusClass }}">
                                                Invoice: {{ ucwords(str_replace('_', ' ', $invoice->status)) }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-1 text-muted" style="font-size:0.82rem;">
                                        <i class="fas fa-calendar-alt mr-1"></i>
                                        {{ $wo->work_date?->format('d M Y') ?? '-' }}
                                        @if ($wo->deadline)
                                            &bull; <i class="fas fa-flag mr-1"></i>Deadline:
                                            <span
                                                class="{{ $wo->deadline->isPast() && !in_array($wo->status, ['completed', 'cancelled', 'invoiced']) ? 'text-danger font-weight-bold' : '' }}">
                                                {{ $wo->deadline->format('d M Y') }}
                                            </span>
                                        @endif
                                        @if ($wo->sa_sales)
                                            &bull; SA: {{ $wo->sa_sales }}
                                        @endif
                                    </div>

                                    @if ($wo->description)
                                        <div class="mt-1 text-dark" style="font-size:0.85rem;">
                                            {{ Str::limit($wo->description, 100) }}
                                        </div>
                                    @endif

                                    @php
                                        $panelCount = $wo->labors?->where('is_extra', false)->count() ?? 0;
                                        $tierLabels = [
                                            '0_300'   => '0–300jt',
                                            '300_500' => '300–500jt',
                                            '500_800' => '500–800jt',
                                            '800_2000'=> '800jt–2M',
                                        ];
                                    @endphp
                                    @if ($panelCount > 0 || $wo->vehicle_price_tier)
                                        <div class="mt-1">
                                            @if ($panelCount > 0)
                                                <span class="badge badge-light border mr-1">
                                                    <i class="fas fa-tools mr-1"></i>{{ $panelCount }} Panel
                                                </span>
                                            @endif
                                            @if ($wo->vehicle_price_tier)
                                                <span class="badge badge-light border">
                                                    <i class="fas fa-car mr-1"></i>{{ $tierLabels[$wo->vehicle_price_tier] ?? $wo->vehicle_price_tier }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="ml-3 text-right" style="min-width:110px;">
                                    @if ($wo->grand_total)
                                        <div class="font-weight-bold" style="font-size:0.9rem;">
                                            Rp {{ number_format($wo->grand_total, 0, ',', '.') }}
                                        </div>
                                        <small class="text-muted">Work Order</small>
                                    @endif
                                    @if ($invoice?->grand_total)
                                        <div class="font-weight-bold text-success mt-1" style="font-size:0.85rem;">
                                            Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}
                                        </div>
                                        <small class="text-muted">Invoice</small>
                                    @endif
                                    <div class="mt-1">
                                        <a href="{{ route('work_orders.show', $wo) }}"
                                            class="btn btn-xs btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if ($invoice)
                                            <a href="{{ route('invoices.show', $invoice) }}"
                                                class="btn btn-xs btn-outline-success">
                                                <i class="fas fa-file-invoice"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-tools" style="font-size:2rem;"></i>
                            <p class="mt-2">No service history yet for this vehicle.</p>
                            @if (\App\Helpers\PermissionHelper::canCreate('work_orders'))
                                <a href="{{ route('work_orders.create') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Create First Work Order
                                </a>
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
