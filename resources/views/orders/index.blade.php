@extends('layouts.app')

@section('content')
<div class="container">
    <h3>{{ __('messages.manage_orders') }}</h3>
    <div class="mt-4">
        <div class="toolbar mb-3">
            <a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> {{ __('messages.add') }}</a>
            </a>
        </div>


        {{-- Search and Date Filter Form --}}
        <div class="mb-3">
            <form action="{{ route('orders.index') }}" method="GET">
                <div class="input-group">
                    {{-- Existing search input --}}
                    <input type="text" name="search" class="form-control" placeholder="{{ __('messages.search_order') }}" value="{{ request('search') }}">

                    {{-- New Start Date input --}}
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" title="{{ __('messages.start_date') }}">

                    {{-- New End Date input --}}
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}" title="{{ __('messages.end_date') }}">

                    <button class="btn btn-outline-secondary" type="submit">{{ __('messages.search') }}</button>
                </div>
            </form>
        </div>

        @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
        @endif

        @if ($orders->isEmpty())
        <p>{{ __("messages.no_data_to_display") }}</p>
        @else
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('messages.id') }}</th>
                        <th>{{ __('messages.user') }}</th>
                        <th>{{ __('messages.driver') }}</th>
                        <th>{{ __('messages.date') }}</th>
                        <th>{{ __('messages.status') }}</th>
                        <th>{{ __('messages.payment_status') }}</th>
                        <th>{{ __('messages.total_price') }}</th>
                        <th>{{ __('messages.modify') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->user->name }}</td>
                        <td>{{ optional(optional($order->orderDelivery)->driver)->name }}</td>
                        <td>{{ $order->created_at->format('Y-m-d') }}</td>
                        <td>
                            <span class="badge bg-{{ $order->status == App\Enums\OrderStatus::COMPLETED ? 'success' : ($order->status == App\Enums\OrderStatus::PENDING ? 'warning' : 'info') }}">
                                {{ $order->statusTranslated() }}
                            </span>
                        </td>
                        <td>
                            @if($order->is_paid)
                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>{{ __('messages.paid') }}</span>
                            @else
                                <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>{{ __('messages.not_paid') }}</span>
                            @endif
                        </td>
                        <td>
                            @if($order->payment_method === 'points')
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-star me-1"></i>{{ number_format($order->points_used ?? 0, 2) }} pts
                                </span>
                            @else
                                {{ $order->sum_price }}
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('orders.show', $order->id) }}" class="btn btn-info btn-sm" title="{{ __('messages.show') }}">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if(Auth::guard('admin')->check() || Auth::guard('employee')->check() || Auth::guard('driver')->check())

                            <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-warning btn-sm" title="{{ __('messages.edit') }}">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form class="d-inline" id="order-delete-form-{{ $order->id }}" action="{{ route('orders.destroy', $order->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __("messages.confirm_deletion") }}')" title="{{ __('messages.delete') }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                            @endif
                            @if(!$order->is_paid && (Auth::guard('admin')->check() || Auth::guard('employee')->check()))
                            <button type="button"
                                    class="btn btn-success btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#payOrderModal"
                                    data-order-id="{{ $order->id }}"
                                    data-order-total="{{ number_format($order->sum_price, 3) }}"
                                    title="{{ __('messages.pay_now') }}">
                                <i class="fas fa-credit-card"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm btn-copy-payment-link"
                                    data-url="{{ URL::signedRoute('orders.public-pay', ['order' => $order->id]) }}"
                                    title="{{ __('messages.copy_payment_link') }}">
                                <i class="fas fa-link"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-pagination :paginator="$orders" />

        @endif
    </div>
</div>
<script>
    function confirmUserDeletion(userId) {
        var result = confirm('{{__("messages.confirm_deletion")}}');
        if (result) {
            document.getElementById('user-delete-form-' + userId).submit();
        }
    }
</script>

@if(Auth::guard('admin')->check() || Auth::guard('employee')->check())
{{-- Shared Pay Now modal (one modal for all orders on this page) --}}
<div class="modal fade" id="payOrderModal" tabindex="-1" aria-labelledby="payOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="payOrderForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="payOrderModalLabel">
                        <i class="fas fa-credit-card me-2"></i>{{ __('messages.pay_now') }}
                        — {{ __('messages.id') }} #<span id="payOrderIdLabel"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">{{ __('messages.total_price') }}: <strong id="payOrderTotalLabel"></strong> KWD</p>
                    <div class="mb-2">
                        <label class="form-label fw-bold">{{ __('messages.payment_method_label') }}</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="idx_pay_money" value="money" checked>
                            <label class="form-check-label" for="idx_pay_money">
                                <i class="fas fa-money-bill me-1 text-success"></i>{{ __('messages.pay_with_money') }}
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="idx_pay_points" value="points">
                            <label class="form-check-label" for="idx_pay_points">
                                <i class="fas fa-star me-1 text-warning"></i>{{ __('messages.pay_with_points') }}
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="idx_pay_knet" value="knet">
                            <label class="form-check-label" for="idx_pay_knet">
                                <i class="fas fa-credit-card me-1 text-primary"></i>{{ __('messages.pay_with_knet') }}
                                @if(config('services.knet.debug'))
                                    <span class="badge bg-info ms-1">{{ __('messages.knet_sandbox') }}</span>
                                @endif
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>{{ __('messages.pay_now') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Payment link toast notification --}}
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
    <div id="linkCopiedToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-check-circle me-2"></i>{{ __('messages.payment_link_copied') }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Pay Now modal — populate form when modal is shown
    var payModal = document.getElementById('payOrderModal');
    payModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Button that triggered the modal
        var orderId = button.getAttribute('data-order-id');
        var orderTotal = button.getAttribute('data-order-total');
        
        document.getElementById('payOrderIdLabel').textContent = orderId;
        document.getElementById('payOrderTotalLabel').textContent = orderTotal;
        document.getElementById('payOrderForm').action = '/orders/' + orderId + '/pay';
    });

    // Copy payment link to clipboard
    var linkCopiedToastEl = document.getElementById('linkCopiedToast');
    var toast = linkCopiedToastEl ? new bootstrap.Toast(linkCopiedToastEl, { delay: 2500 }) : null;
    
    document.querySelectorAll('.btn-copy-payment-link').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = this.getAttribute('data-url');
            if (!url) return;
            
            navigator.clipboard.writeText(url).then(function () {
                if (toast) toast.show();
            }).catch(function () {
                // Fallback for browsers without clipboard API
                window.prompt('{{ __('messages.copy_payment_link') }}:', url);
            });
        });
    });
});
</script>
@endif

@endsection
