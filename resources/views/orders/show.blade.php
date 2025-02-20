@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.order_details') }}</h1>

    <div class="card">
        <div class="card-body">
            <p><strong>{{ __('messages.id') }}:</strong> {{ $order->id }}</p>
            <p><strong>{{ __('messages.user') }}:</strong> {{ $order->user->name }}</p>
            @if ($order->discount)
            <p><strong>{{ __('messages.discount') }}:</strong> {{ $order->discount->code }}</p>
            @endif
            @if ($order->clientSubscription)
            <p><strong>{{ __('messages.client_subscription') }}:</strong> {{ $order->clientSubscription->id }}</p>
            @endif
            <p><strong>{{ __('messages.total_price') }}:</strong> {{ $order->sum_price }}</p>
            <p><strong>{{ __('messages.status') }}:</strong> {{ $order->statusTranslated() }}</p>

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

            @if ($order->delivery_price > 0)
            <p><strong>{{ __('messages.delivery_price') }}:</strong> {{ $order->delivery_price }}</p>
            @endif
            @if ($order->driver_id)
            <p><strong>{{ __('messages.driver') }}:</strong> {{ $order->driver->name }}</p>
            @endif
            @if ($order->bring_order)
            <p><strong>{{ __('messages.bring_order') }}:</strong> Yes</p>
            @endif
            @if ($order->return_order)
            <p><strong>{{ __('messages.return_order') }}:</strong> Yes</p>
            @endif

            <a href="{{ route('orders.index') }}" class="btn btn-secondary">{{ __('messages.back') }}</a>
        </div>
    </div>
</div>
@endsection