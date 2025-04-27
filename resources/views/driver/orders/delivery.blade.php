@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{__('messages.delivery_orders')}}</h1>

    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    <h2>{{__('messages.orders_with_billing_info')}}</h2>

    <table class="table">
        <thead>
            <tr>
                <th>{{ __('messages.order_id') }}</th>
                <th>{{ __('messages.order_details') }}</th>
                <th>{{ __('messages.order_status') }}</th>
                <th>{{ __('messages.delivery_price') }}</th>
                <th>{{ __('messages.driver') }}</th>
                <th>{{ __('messages.created_at') }}</th>
                <th>{{ __('messages.order_details') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($current_orders as $order)
            <tr>
                <td>{{ $order->id }}</td>
                <td>@if($order->orderProductServices->isNotEmpty())
                    <ul>
                        @foreach($order->orderProductServices as $orderProductService)
                        @if($orderProductService->product)
                        <li>{{ $orderProductService->product->name }}</li>
                        <small>({{ $orderProductService->productService->name }})</small>
                        @else
                        <li></li>
                        @endif
                        @endforeach
                    </ul>
                    @else
                    <p></p>
                    @endif
                </td>
                <td>{{ $order->statusTranslated() }}</td>
                <td>@if($order->orderDelivery)
                    <ul>
                        <small>{{ $order->orderDelivery->price }}</small>
                    </ul>
                    @else
                    <p></p>
                    @endif
                </td>

                <td>{{ $order->orderDelivery->driver->name }}</td>
                <td>{{ $order->created_at }}</td>
                <td><a href="{{ route('orders.details', $order->id) }}" class="btn btn-info btn-sm" title="{{ __('messages.show') }}">
                            <i class="fas fa-eye"></i> {{ __('messages.order_details') }}
                        </a></td>
            </tr>
            @empty
            <tr>
                <td colspan="6">{{__('messages.no_orders_found')}}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <x-pagination :paginator="$current_orders" />

</div>
@endsection