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
            <p><strong>{{ __('messages.total_price') }}:</strong> {{ $order->sum_price }}</p>
            <p><strong>{{ __('messages.payment_method_label') }}:</strong>
                @if(($order->payment_method ?? 'money') === 'points')
                    <span class="badge bg-warning text-dark"><i class="fas fa-star me-1"></i>{{ __('messages.pay_with_points') }}</span>
                    &nbsp;<span class="text-muted small">({{ number_format($order->points_used ?? 0, 2) }} pts {{ __('messages.points_used') }})</span>
                @else
                    <span class="badge bg-success"><i class="fas fa-money-bill me-1"></i>{{ __('messages.pay_with_money') }}</span>
                @endif
            </p>
            <p><strong>{{ __('messages.status') }}:</strong> <span class="badge bg-{{ $order->status == App\Enums\OrderStatus::COMPLETED ? 'success' : ($order->status == App\Enums\OrderStatus::PENDING ? 'warning' : 'info') }}">{{ $order->statusTranslated() }}</span></p>

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
                        <th>{{ __('messages.price') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->orderProductServices as $orderProductService)
                    @php
                        $displayPrice = $orderProductService->price_at_order;
                        if (!$displayPrice) {
                            $psp = \App\Models\ProductServicePrice::where('product_id', $orderProductService->product_id)
                                ->where('product_service_id', $orderProductService->product_service_id)
                                ->first();
                            $displayPrice = $psp ? $psp->price : 0;
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
                        <td>{{ number_format($displayPrice * $orderProductService->quantity, 3) }} KWD</td>
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
        </div>
    </div>
</div>
@endsection