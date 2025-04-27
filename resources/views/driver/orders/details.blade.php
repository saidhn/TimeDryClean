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
            <p><strong>{{ __('messages.driver') }}:</strong> {{ optional(optional($order->orderDelivery)->driver)->name }}</p>

            @if (optional($order->orderDelivery)->direction == App\Enums\DeliveryDirection::ORDER_TO_WORK
            || optional($order->orderDelivery)->direction == App\Enums\DeliveryDirection::BOTH)
            <p><strong>{{ __('messages.required_delivery') }}:</strong> <small class="d-inline border border-primary p-2 rounded-pill">{{ __('messages.bring_order') }}</small>
                @endif

                @if (optional($order->orderDelivery)->direction == App\Enums\DeliveryDirection::WORK_TO_ORDER
                || optional($order->orderDelivery)->direction == App\Enums\DeliveryDirection::BOTH)
                <small class="d-inline border border-primary p-2 rounded-pill">{{ __('messages.return_order') }}</small>
            </p>
            @endif

            <p><strong>{{ __('messages.delivery_price') }}:</strong> {{ optional($order->orderDelivery)->price }}</p>

            @if ($order->discount)
            <p><strong>{{ __('messages.discount') }}:</strong> {{ $order->discount->code }}</p>
            @endif
            @if ($order->clientSubscription)
            <p><strong>{{ __('messages.client_subscription') }}:</strong> {{ $order->clientSubscription->id }}</p>
            @endif
            <p><strong>{{ __('messages.total_price') }}:</strong> {{ $order->sum_price }}</p>
            <p><strong>{{ __('messages.status') }}:</strong> {{ $order->statusTranslated() }}</p>

            <h2 class="mt-4">{{__('messages.address')}}</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('messages.province') }}</th>
                        <th>{{ __('messages.city') }}</th>
                        <th>{{ __('messages.street') }}</th>
                        <th>{{ __('messages.building') }}</th>
                        <th>{{ __('messages.floor') }}</th>
                        <th>{{ __('messages.appartment_number') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ optional(optional(optional($order->orderDelivery)->address)->province)->name }}</td>
                        <td>{{ optional(optional(optional($order->orderDelivery)->address)->city)->name }}</td>
                        <td>{{ optional($order->orderDelivery)->street }}</td>
                        <td>{{ optional($order->orderDelivery)->building }}</td>
                        <td>{{ optional($order->orderDelivery)->floor }}</td>
                        <td>{{ optional($order->orderDelivery)->apartment_number }}</td>
                    </tr>
                </tbody>
            </table>

            <h2 class="mt-4">{{ __('messages.change_status') }}</h2>
            <div>
                <form class='d-inline' method="POST" action="{{ route('driver.orders.update', ['order' => $order->id, 'status' => App\Enums\OrderStatus::SHIPPED]) }}">
                    @csrf
                    @method('PUT')
                    <button class="btn btn-primary">{{__('messages.change_status_to_shipped')}}</button>
                </form>

                <form class='d-inline' method="POST" action="{{ route('driver.orders.update', ['order' => $order->id, 'status' => App\Enums\OrderStatus::COMPLETED]) }}">
                    @csrf
                    @method('PUT')
                    <button class="btn btn-primary">{{__('messages.change_status_to_completed')}}</button>
                </form>
            </div>

            <h2 class="mt-4">{{ __('messages.order_products') }}</h2>
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




            <a href="{{ route('driver.delivery') }}" class="btn btn-secondary">{{ __('messages.back') }}</a>
        </div>
    </div>
</div>
@endsection