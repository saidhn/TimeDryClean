@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.order_details') }}</h1>

    @if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif
    <div class="card">
        <div class="card-body">
            <p><strong>{{ __('messages.id') }}:</strong> {{ $order->id }}</p>
            <p><strong>{{ __('messages.user') }}:</strong> {{ $order->user->name }}</p>
            
            @if($order->orderDelivery)
            <div class="card mb-3">
                <div class="card-header">{{ __('messages.delivery_information') }}</div>
                <div class="card-body">
                    <p><strong>{{ __('messages.driver') }}:</strong> {{ optional($order->orderDelivery->driver)->name ?? __('messages.not_assigned') }}</p>
                    <p><strong>{{ __('messages.delivery_price') }}:</strong> {{ $order->orderDelivery->price ?? 0 }}</p>
                    <p><strong>{{ __('messages.delivery_direction') }}:</strong> {{ $order->orderDelivery->direction }}</p>
                    @if($order->orderDelivery->address)
                    <p><strong>{{ __('messages.province') }}:</strong> {{ optional($order->orderDelivery->address->province)->name }}</p>
                    <p><strong>{{ __('messages.city') }}:</strong> {{ optional($order->orderDelivery->address->city)->name }}</p>
                    @endif
                    <p><strong>{{ __('messages.street') }}:</strong> {{ $order->orderDelivery->street }}</p>
                    <p><strong>{{ __('messages.building') }}:</strong> {{ $order->orderDelivery->building }}</p>
                    <p><strong>{{ __('messages.floor') }}:</strong> {{ $order->orderDelivery->floor }}</p>
                    <p><strong>{{ __('messages.appartment_number') }}:</strong> {{ $order->orderDelivery->apartment_number }}</p>
                </div>
            </div>
            @endif
            @if ($order->discount)
            <p><strong>{{ __('messages.discount') }}:</strong> {{ $order->discount->code }}</p>
            @endif
            @if ($order->clientSubscription)
            <p><strong>{{ __('messages.client_subscription') }}:</strong> {{ $order->clientSubscription->id }}</p>
            @endif
            @php $pm = $order->payment_method ?? 'money'; @endphp
            @if($pm === 'points')
            <p><strong>{{ __('messages.total_price') }}:</strong>
                <span class="badge bg-warning text-dark fs-6"><i class="fas fa-star me-1"></i>{{ number_format($order->points_used ?? 0, 2) }} pts</span>
            </p>
            @else
            <p><strong>{{ __('messages.total_price') }}:</strong> {{ $order->sum_price }} {{ __('messages.currency_symbol') }}</p>
            @endif
            <p><strong>{{ __('messages.payment_method_label') }}:</strong>
                @if($pm === 'points')
                    <span class="badge bg-warning text-dark"><i class="fas fa-star me-1"></i>{{ __('messages.pay_with_points') }}</span>
                @elseif($pm === 'knet')
                    <span class="badge bg-primary"><i class="fas fa-credit-card me-1"></i>{{ __('messages.pay_with_knet') }}</span>
                @else
                    <span class="badge bg-success"><i class="fas fa-money-bill me-1"></i>{{ __('messages.pay_with_money') }}</span>
                @endif
            </p>
            <p><strong>{{ __('messages.payment_status') }}:</strong>
                @if($order->is_paid)
                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>{{ __('messages.paid') }}</span>
                @else
                    <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>{{ __('messages.not_paid') }}</span>
                @endif
            </p>
            <p><strong>{{ __('messages.status') }}:</strong> <span class="badge bg-{{ $order->status == App\Enums\OrderStatus::DELIVERED ? 'success' : ($order->status == App\Enums\OrderStatus::PLACED ? 'warning' : 'info') }}">{{ $order->statusTranslated() }}</span></p>

            @if($order->notes)
            <p><strong>{{ __('messages.order_notes') }}:</strong> {{ $order->notes }}</p>
            @endif

            <h2>{{ __('messages.order_products') }}</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('messages.product') }}</th>
                        <th>{{ __('messages.product_service') }}</th>
                        <th>{{ __('messages.quantity') }}</th>
                        @if($pm === 'points')
                        <th>{{ __('messages.points') }}</th>
                        @else
                        <th>{{ __('messages.price') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->orderProductServices as $orderProductService)
                    @php
                        $displayPrice = $orderProductService->price_at_order;
                        $displayPoints = $orderProductService->points_at_order;
                        if (!$displayPrice || ($pm === 'points' && $displayPoints === null)) {
                            $psp = \App\Models\ProductServicePrice::where('product_id', $orderProductService->product_id)
                                ->where('product_service_id', $orderProductService->product_service_id)
                                ->first();
                            if (!$displayPrice) {
                                $displayPrice = $psp ? $psp->price : 0;
                            }
                            if ($pm === 'points' && $displayPoints === null) {
                                $displayPoints = $psp ? $psp->points_price : null;
                            }
                        }
                    @endphp
                    <tr>
                        <td>
                            @if ($orderProductService->product->image_path)
                            <img src="{{ asset('storage/' . $orderProductService->product->image_path) }}" alt="{{ $orderProductService->product->name }}" class="img-thumbnail me-1" style="max-height: 35px; max-width: 35px;">
                            @endif
                            {{ $orderProductService->product->name }}
                        </td>
                        <td>{{ $orderProductService->productService->name }}</td>
                        <td>{{ $orderProductService->quantity }}</td>
                        @if($pm === 'points')
                        <td>
                            @if($displayPoints !== null)
                                <span class="text-warning fw-bold"><i class="fas fa-star small me-1"></i>{{ number_format($displayPoints * $orderProductService->quantity, 2) }} pts</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        @else
                        <td>{{ number_format($displayPrice * $orderProductService->quantity, 3) }} KWD</td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                @include('components.discount-summary', ['order' => $order])
            </div>

            @if($order->hasDiscount())
            <div class="mt-3">
                @include('components.discount-display', ['order' => $order])
            </div>
            @endif

            <a href="{{ route('orders.index') }}" class="btn btn-secondary">{{ __('messages.back') }}</a>

            @if(!$order->is_paid && (Auth::guard('admin')->check() || Auth::guard('employee')->check()))
            <button type="button" class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#payNowModal">
                <i class="fas fa-credit-card me-1"></i>{{ __('messages.pay_now') }}
            </button>
            @endif
        </div>
    </div>
</div>
@endsection

@if(!$order->is_paid && (Auth::guard('admin')->check() || Auth::guard('employee')->check()))
{{-- Pay Now Modal --}}
<div class="modal fade" id="payNowModal" tabindex="-1" aria-labelledby="payNowModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('orders.pay', $order->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="payNowModalLabel">
                        <i class="fas fa-credit-card me-2"></i>{{ __('messages.pay_now') }} — {{ __('messages.id') }} #{{ $order->id }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                    @endif
                    <p class="mb-3">{{ __('messages.total_price') }}: <strong>{{ number_format($order->sum_price, 3) }} KWD</strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('messages.payment_method_label') }}</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="pay_money" value="money" checked>
                            <label class="form-check-label" for="pay_money">
                                <i class="fas fa-money-bill me-1 text-success"></i>{{ __('messages.pay_with_money') }}
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="pay_points" value="points"
                                   {{ ($requiredPoints ?? null) === null ? 'disabled' : '' }}>
                            <label class="form-check-label" for="pay_points">
                                <i class="fas fa-star me-1 text-warning"></i>{{ __('messages.pay_with_points') }}
                                @if(($requiredPoints ?? null) !== null)
                                    <span class="text-muted small">({{ number_format($requiredPoints, 2) }} pts)</span>
                                @else
                                    <span class="text-muted small">({{ __('messages.points_not_available') }})</span>
                                @endif
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="pay_knet" value="knet">
                            <label class="form-check-label" for="pay_knet">
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
@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = new bootstrap.Modal(document.getElementById('payNowModal'));
        modal.show();
    });
</script>
@endif
@endif