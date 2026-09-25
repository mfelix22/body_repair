@php
    $canViewRevenue = ! (auth()->user()->hide_revenue ?? false);
@endphp
<div class="row mt-2">
    <div class="col-12">
        <div class="card card-outline card-success mb-0">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar mr-1"></i>
                    {{ $canViewRevenue ? 'Billed Revenue & Material Cost' : 'Material Cost (COGS)' }} — {{ $currentYear }}
                </h3>
                <div class="card-tools">
                    <span class="text-muted small">Active invoices only (excluding cancelled)</span>
                </div>
            </div>
            <div class="card-body">
                {{-- Summary cards for current month --}}
                <div class="row mb-3">
                    @if ($canViewRevenue)
                        <div class="col-md-6">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Billed This Month — {{ now()->format('F') }}</span>
                                    <span class="info-box-number">
                                        Rp {{ number_format($revenueThisMonth, 0, ',', '.') }}
                                    </span>
                                    <span class="progress-description">All invoiced amounts this month (excl. cancelled)</span>
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="col-md-{{ $canViewRevenue ? 6 : 12 }}">
                        <div class="info-box bg-warning">
                            <span class="info-box-icon"><i class="fas fa-boxes"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Material Cost (COGS) — {{ now()->format('F') }}</span>
                                <span class="info-box-number">
                                    Rp {{ number_format($materialCostThisMonth, 0, ',', '.') }}
                                </span>
                                <span class="progress-description">Total material COGS this month</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if (($showFinanceFigures ?? true) && $canViewRevenue)
                    {{-- Finance figures (same as Finance Dashboard) --}}
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Outstanding Amount</span>
                                    <span class="info-box-number">
                                        Rp {{ number_format($summary['outstanding_amount'], 0, ',', '.') }}
                                    </span>
                                    <span class="progress-description">All unpaid invoices (all time)</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-primary">
                                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Paid This Month</span>
                                    <span class="info-box-number">
                                        {{ $summary['paid_invoices_month'] }} invoice{{ $summary['paid_invoices_month'] === 1 ? '' : 's' }}
                                    </span>
                                    <span class="progress-description">Marked paid in {{ now()->format('F') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Revenue This Month</span>
                                    <span class="info-box-number">
                                        Rp {{ number_format($summary['revenue_this_month'], 0, ',', '.') }}
                                    </span>
                                    <span class="progress-description">Collected from paid invoices in {{ now()->format('F') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Monthly chart --}}
                <canvas id="revenueChart" style="height:260px; max-height:260px;"></canvas>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('admin/plugins/chart.js/Chart.bundle.min.js') }}"></script>
    <script>
        (function() {
            var labels = @json(array_values($monthNames));
            @if ($canViewRevenue)
                var revenue = @json(array_values($monthlyRevenue));
            @endif
            var cogs = @json(array_values($monthlyMaterialCost));

            var ctx = document.getElementById('revenueChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        @if ($canViewRevenue)
                            {
                                label: 'Billed (Rp)',
                                data: revenue,
                                backgroundColor: 'rgba(40,167,69,0.7)',
                                borderColor: 'rgba(40,167,69,1)',
                                borderWidth: 1,
                                order: 1
                            },
                        @endif
                        {
                            label: 'Material Cost / COGS (Rp)',
                            data: cogs,
                            backgroundColor: 'rgba(255,193,7,0.7)',
                            borderColor: 'rgba(255,193,7,1)',
                            borderWidth: 1,
                            order: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function(value) {
                                    if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(
                                        1) + 'M';
                                    if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) +
                                        'K';
                                    return 'Rp ' + value;
                                }
                            }
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            label: function(item, data) {
                                var label = data.datasets[item.datasetIndex].label || '';
                                var val = item.yLabel;
                                return label + ': Rp ' + val.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            });
        })();
    </script>
@endpush
