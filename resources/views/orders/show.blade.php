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
            <p><strong>{{ __('messages.status') }}:</strong> <span class="badge bg-{{ $order->status == App\Enums\OrderStatus::COMPLETED ? 'success' : ($order->status == App\Enums\OrderStatus::PENDING ? 'warning' : 'info') }}">{{ $order->statusTranslated() }}</span></p>

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
                    <tr>
                        <td>{{ $orderProductService->product->name }}</td>
                        <td>{{ $orderProductService->productService->name }}</td>
                        <td>{{ $orderProductService->quantity }}</td>
                        <td>{{ $orderProductService->productService->price * $orderProductService->quantity }}</td>
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