@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>{{ __('messages.employee_dashboard') }}</h3>
        <p class="mb-0">{{ __('messages.hello') }}, {{ $employee->name }}!</p>
    </div>

    <div class="row g-4">
        <!-- Create Order Card -->
        <div class="col-md-4">
            <a href="{{ route('orders.create') }}" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm dashboard-card">
                    <div class="card-body text-center py-4">
                        <div class="mb-3">
                            <i class="fas fa-plus-circle fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title text-dark">{{ __('messages.create_order') }}</h5>
                        <p class="card-text text-muted">{{ __('messages.create_new_order') }}</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Invoice Data Card -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm dashboard-card">
                <div class="card-body text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-file-invoice-dollar fa-3x text-success"></i>
                    </div>
                    <h5 class="card-title text-dark">{{ __('messages.invoice_data') }}</h5>
                    <p class="card-text text-muted">{{ __('messages.view_invoice_statistics') }}</p>
                </div>
            </div>
        </div>

        <!-- Total Income Card -->
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm dashboard-card">
                <div class="card-body text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-chart-line fa-3x text-info"></i>
                    </div>
                    <h5 class="card-title text-dark">{{ __('messages.total_income') }}</h5>
                    <p class="card-text text-muted">{{ __('messages.view_income_reports') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-card {
    transition: all 0.3s ease;
    cursor: pointer;
}
.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
}
.dashboard-card a:hover .card-title,
.dashboard-card a:hover .card-text {
    color: inherit !important;
}
</style>
@endsection