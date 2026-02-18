@extends('layouts.app')

@section('content')
<div class="container py-4">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1"><i class="fas fa-chart-line text-primary me-2"></i>{{ __('messages.total_income') }}</h3>
            <p class="text-muted mb-0">{{ __('messages.income_overview') }}</p>
        </div>
        <a href="{{ route($dashboardRoute) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>{{ __('messages.back_to_dashboard') }}
        </a>
    </div>

    {{-- KPI Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm kpi-card kpi-total">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon-wrapper bg-primary-soft me-3">
                            <i class="fas fa-coins text-primary"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">{{ __('messages.total_income') }}</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($totalIncome, 2) }}</h4>
                            <small class="text-muted">{{ __('messages.currency_symbol') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm kpi-card kpi-month">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon-wrapper bg-success-soft me-3">
                            <i class="fas fa-calendar-check text-success"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">{{ __('messages.this_month') }}</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($thisMonthIncome, 2) }}</h4>
                            <small class="text-muted">{{ __('messages.currency_symbol') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm kpi-card kpi-avg">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon-wrapper bg-info-soft me-3">
                            <i class="fas fa-receipt text-info"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">{{ __('messages.average_order_value') }}</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($averageOrderValue, 2) }}</h4>
                            <small class="text-muted">{{ __('messages.currency_symbol') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm kpi-card kpi-completed">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon-wrapper bg-warning-soft me-3">
                            <i class="fas fa-check-circle text-warning"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">{{ __('messages.completed_orders') }}</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($completedOrders) }}</h4>
                            <small class="text-muted">{{ __('messages.orders') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Income Chart --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0"><i class="fas fa-chart-bar text-primary me-2"></i>{{ __('messages.monthly_income_chart') }}</h5>
        </div>
        <div class="card-body">
            @if(array_sum($chartData) > 0)
            <div class="chart-container" style="position: relative; height: 350px;">
                <canvas id="monthlyIncomeChart"></canvas>
            </div>
            @else
            <div class="text-center py-5">
                <i class="fas fa-chart-area fa-3x text-muted mb-3"></i>
                <p class="text-muted">{{ __('messages.no_income_data') }}</p>
            </div>
            @endif
        </div>
    </div>

    <div class="row g-4">
        {{-- Income by Status --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="fas fa-pie-chart text-success me-2"></i>{{ __('messages.income_by_status') }}</h5>
                </div>
                <div class="card-body">
                    @if($incomeByStatus->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('messages.status') }}</th>
                                    <th class="text-center">{{ __('messages.order_count') }}</th>
                                    <th class="text-end">{{ __('messages.total_amount') }}</th>
                                    <th class="text-end">{{ __('messages.percentage_share') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($incomeByStatus as $row)
                                @php
                                    $statusClass = match($row->status) {
                                        'Completed' => 'success',
                                        'Pending' => 'warning',
                                        'Processing' => 'info',
                                        'Shipped' => 'primary',
                                        'Cancelled' => 'danger',
                                        default => 'secondary',
                                    };
                                    $percentage = $totalIncome > 0 ? ($row->total_amount / $totalIncome * 100) : 0;
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ __('messages.' . strtolower($row->status)) }}
                                        </span>
                                    </td>
                                    <td class="text-center fw-semibold">{{ number_format($row->order_count) }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($row->total_amount, 2) }} {{ __('messages.currency_symbol') }}</td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end">
                                            <div class="progress flex-grow-1 me-2" style="height: 6px; max-width: 80px;">
                                                <div class="progress-bar bg-{{ $statusClass }}" style="width: {{ $percentage }}%"></div>
                                            </div>
                                            <span class="small">{{ number_format($percentage, 1) }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">{{ __('messages.no_income_data') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Top Orders --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="fas fa-trophy text-warning me-2"></i>{{ __('messages.top_orders') }}</h5>
                </div>
                <div class="card-body">
                    @if($topOrders->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('messages.id') }}</th>
                                    <th>{{ __('messages.user') }}</th>
                                    <th>{{ __('messages.date') }}</th>
                                    <th class="text-end">{{ __('messages.total_price') }}</th>
                                    <th>{{ __('messages.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topOrders as $index => $order)
                                @php
                                    $statusClass = match($order->status) {
                                        'Completed' => 'success',
                                        'Pending' => 'warning',
                                        'Processing' => 'info',
                                        'Shipped' => 'primary',
                                        'Cancelled' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        @if($index < 3)
                                            <span class="badge bg-{{ $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'danger') }} rounded-pill">
                                                {{ $index + 1 }}
                                            </span>
                                        @else
                                            <span class="text-muted">{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('orders.show', $order->id) }}" class="text-decoration-none fw-semibold">
                                            #{{ $order->id }}
                                        </a>
                                    </td>
                                    <td>{{ $order->user->name ?? '-' }}</td>
                                    <td class="small text-muted">{{ $order->created_at->format('Y-m-d') }}</td>
                                    <td class="text-end fw-bold">{{ number_format($order->sum_price, 2) }} {{ __('messages.currency_symbol') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $statusClass }}">{{ $order->statusTranslated() }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">{{ __('messages.no_income_data') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.kpi-card {
    transition: all 0.3s ease;
    border-radius: 12px;
    overflow: hidden;
}
.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12) !important;
}
.kpi-icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}
.bg-primary-soft { background-color: rgba(70, 70, 135, 0.1); }
.bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
.bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
.bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
.card { border-radius: 12px; }
.card-header { border-radius: 12px 12px 0 0 !important; }
.table th { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.03em; color: #6c757d; }
.progress { border-radius: 3px; }
</style>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('monthlyIncomeChart');
    if (!ctx) return;

    const labels = @json($chartLabels);
    const data = @json($chartData);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '{{ __("messages.income") }} ({{ __("messages.currency_symbol") }})',
                data: data,
                backgroundColor: 'rgba(70, 70, 135, 0.7)',
                borderColor: 'rgba(70, 70, 135, 1)',
                borderWidth: 1,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 12,
                    titleFont: { size: 13 },
                    bodyFont: { size: 12 },
                    callbacks: {
                        label: function(ctx) {
                            return ctx.parsed.y.toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }) + ' {{ __("messages.currency_symbol") }}';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { maxRotation: 45, minRotation: 45 }
                }
            }
        }
    });
});
</script>
@endpush
@endsection
