@extends('layouts.app')

@section('content')
<div class="container py-4">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1"><i class="fas fa-file-invoice-dollar text-success me-2"></i>{{ __('messages.invoice_data') }}</h3>
            <p class="text-muted mb-0">{{ __('messages.invoice_overview') }}</p>
        </div>
        <a href="{{ route($dashboardRoute) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>{{ __('messages.back_to_dashboard') }}
        </a>
    </div>

    {{-- KPI Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm kpi-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon-wrapper bg-primary-soft me-3">
                            <i class="fas fa-file-invoice text-primary"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">{{ __('messages.total_invoices') }}</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($totalInvoices) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm kpi-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon-wrapper bg-success-soft me-3">
                            <i class="fas fa-money-bill-wave text-success"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">{{ __('messages.total_revenue') }}</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($totalRevenue, 2) }}</h4>
                            <small class="text-muted">{{ __('messages.currency_symbol') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm kpi-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="kpi-icon-wrapper bg-info-soft me-3">
                            <i class="fas fa-calculator text-info"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small">{{ __('messages.avg_invoice_value') }}</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($avgInvoiceValue, 2) }}</h4>
                            <small class="text-muted">{{ __('messages.currency_symbol') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search & Filter --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route($currentRoute) }}" method="GET" id="filterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted">
                            <i class="fas fa-search me-1"></i>{{ __('messages.search') }}
                        </label>
                        <input type="text" name="search" class="form-control" placeholder="{{ __('messages.search_invoices') }}" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted">
                            <i class="fas fa-calendar me-1"></i>{{ __('messages.from_date') }}
                        </label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted">
                            <i class="fas fa-calendar me-1"></i>{{ __('messages.to_date') }}
                        </label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-muted">
                            <i class="fas fa-filter me-1"></i>{{ __('messages.status') }}
                        </label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('messages.all_statuses') }}</option>
                            @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ __('messages.' . strtolower($status)) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-search me-1"></i>{{ __('messages.filter_results') }}
                            </button>
                            <a href="{{ route($currentRoute) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Invoice Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($invoices->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 invoice-table">
                    <thead>
                        <tr class="table-light">
                            <th class="ps-4">{{ __('messages.order_id') }}</th>
                            <th>{{ __('messages.user') }}</th>
                            <th>{{ __('messages.date') }}</th>
                            <th class="text-center">{{ __('messages.items_count') }}</th>
                            <th class="text-end">{{ __('messages.subtotal') }}</th>
                            <th class="text-end">{{ __('messages.discount') }}</th>
                            <th class="text-end">{{ __('messages.delivery_cost') }}</th>
                            <th class="text-end">{{ __('messages.total') }}</th>
                            <th class="text-center">{{ __('messages.status') }}</th>
                            <th class="text-center pe-4">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $invoice)
                        @php
                            $statusClass = match($invoice->status) {
                                'Completed' => 'success',
                                'Pending' => 'warning',
                                'Processing' => 'info',
                                'Shipped' => 'primary',
                                'Cancelled' => 'danger',
                                default => 'secondary',
                            };
                            $itemsCount = $invoice->orderProductServices->count();
                            $subtotal = $invoice->orderProductServices->sum(function($item) {
                                return ($item->price_at_order ?? 0) * $item->quantity;
                            });
                            $deliveryCost = optional($invoice->orderDelivery)->price ?? 0;
                            $discountAmt = $invoice->discount_amount ?? 0;
                        @endphp
                        {{-- Main Row --}}
                        <tr class="invoice-row" data-invoice-id="{{ $invoice->id }}">
                            <td class="ps-4">
                                <a href="{{ route('orders.show', $invoice->id) }}" class="text-decoration-none fw-bold text-primary">
                                    #{{ $invoice->id }}
                                </a>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-2">
                                        <i class="fas fa-user-circle text-muted"></i>
                                    </div>
                                    <span>{{ $invoice->user->name ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="text-muted small">
                                <i class="far fa-clock me-1"></i>{{ $invoice->created_at->format('Y-m-d H:i') }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $itemsCount }}</span>
                            </td>
                            <td class="text-end">{{ number_format($subtotal, 2) }}</td>
                            <td class="text-end">
                                @if($discountAmt > 0)
                                <span class="text-danger">-{{ number_format($discountAmt, 2) }}</span>
                                @else
                                <span class="text-muted">0.00</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($deliveryCost, 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format($invoice->sum_price, 2) }} {{ __('messages.currency_symbol') }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $statusClass }}">{{ $invoice->statusTranslated() }}</span>
                            </td>
                            <td class="text-center pe-4">
                                <button class="btn btn-sm btn-outline-primary toggle-details-btn" data-target="details-{{ $invoice->id }}" title="{{ __('messages.show_details') }}">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </td>
                        </tr>
                        {{-- Expandable Detail Row --}}
                        <tr class="detail-row" id="details-{{ $invoice->id }}" style="display: none;">
                            <td colspan="10" class="p-0">
                                <div class="invoice-detail-wrapper px-4 py-3 bg-light">
                                    <h6 class="fw-bold mb-3">
                                        <i class="fas fa-list-ul text-primary me-2"></i>{{ __('messages.invoice_items') }} — #{{ $invoice->id }}
                                    </h6>
                                    <table class="table table-sm table-bordered bg-white mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('messages.product') }}</th>
                                                <th>{{ __('messages.service') }}</th>
                                                <th class="text-center">{{ __('messages.quantity') }}</th>
                                                <th class="text-end">{{ __('messages.unit_price') }}</th>
                                                <th class="text-end">{{ __('messages.line_total') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($invoice->orderProductServices as $item)
                                            @php
                                                $unitPrice = $item->price_at_order ?? 0;
                                                $lineTotal = $unitPrice * $item->quantity;
                                            @endphp
                                            <tr>
                                                <td>
                                                    @if(optional($item->product)->image_path)
                                                    <img src="{{ asset('storage/' . $item->product->image_path) }}" alt="" class="img-thumbnail me-1" style="max-height: 28px; max-width: 28px;">
                                                    @endif
                                                    {{ optional($item->product)->name ?? '-' }}
                                                </td>
                                                <td>{{ optional($item->productService)->name ?? '-' }}</td>
                                                <td class="text-center">{{ $item->quantity }}</td>
                                                <td class="text-end">{{ number_format($unitPrice, 3) }}</td>
                                                <td class="text-end fw-semibold">{{ number_format($lineTotal, 3) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="4" class="text-end fw-bold">{{ __('messages.subtotal') }}:</td>
                                                <td class="text-end fw-bold">{{ number_format($subtotal, 2) }} {{ __('messages.currency_symbol') }}</td>
                                            </tr>
                                            @if($discountAmt > 0)
                                            <tr>
                                                <td colspan="4" class="text-end text-danger">{{ __('messages.discount') }}:</td>
                                                <td class="text-end text-danger">-{{ number_format($discountAmt, 2) }} {{ __('messages.currency_symbol') }}</td>
                                            </tr>
                                            @endif
                                            @if($deliveryCost > 0)
                                            <tr>
                                                <td colspan="4" class="text-end">{{ __('messages.delivery_cost') }}:</td>
                                                <td class="text-end">{{ number_format($deliveryCost, 2) }} {{ __('messages.currency_symbol') }}</td>
                                            </tr>
                                            @endif
                                            <tr class="fw-bold">
                                                <td colspan="4" class="text-end">{{ __('messages.total') }}:</td>
                                                <td class="text-end">{{ number_format($invoice->sum_price, 2) }} {{ __('messages.currency_symbol') }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="py-3 px-4">
                <x-pagination :paginator="$invoices" />
            </div>
            @else
            <div class="text-center py-5">
                <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                <p class="text-muted mb-0">{{ __('messages.no_invoices_found') }}</p>
            </div>
            @endif
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

.card { border-radius: 12px; }
.card-header { border-radius: 12px 12px 0 0 !important; }

.invoice-table th {
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #6c757d;
    border-bottom: 2px solid #dee2e6;
}

.invoice-row {
    cursor: pointer;
    transition: background-color 0.2s ease;
}
.invoice-row:hover {
    background-color: rgba(70, 70, 135, 0.03);
}

.invoice-detail-wrapper {
    border-top: 2px solid #464687;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.user-avatar {
    font-size: 1.4rem;
}

.toggle-details-btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.3s ease;
}
.toggle-details-btn.active {
    background-color: #464687;
    border-color: #464687;
    color: #fff;
}
.toggle-details-btn.active i {
    transform: rotate(180deg);
}
.toggle-details-btn i {
    transition: transform 0.3s ease;
}

@media print {
    .btn, form, .pagination, .toggle-details-btn { display: none !important; }
    .detail-row { display: table-row !important; }
    .invoice-detail-wrapper { border-top: 1px solid #000; }
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle detail rows
    document.querySelectorAll('.toggle-details-btn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const targetId = this.getAttribute('data-target');
            const detailRow = document.getElementById(targetId);

            if (detailRow) {
                const isVisible = detailRow.style.display !== 'none';
                detailRow.style.display = isVisible ? 'none' : 'table-row';
                this.classList.toggle('active', !isVisible);
            }
        });
    });

    // Click on row to toggle details
    document.querySelectorAll('.invoice-row').forEach(function(row) {
        row.addEventListener('click', function() {
            const invoiceId = this.getAttribute('data-invoice-id');
            const btn = this.querySelector('.toggle-details-btn');
            if (btn) btn.click();
        });
    });
});
</script>
@endpush
@endsection
