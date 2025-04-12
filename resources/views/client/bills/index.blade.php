@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{__('messages.client_orders_and_balance')}}</h1>

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
                <th>{{ __('messages.total_price') }}</th>
                <th>{{ __('messages.created_at') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td>{{ $order->id }}</td>
                <td>@if($order->orderProductServices->isNotEmpty())
                    <ul>
                        @foreach($order->orderProductServices as $orderProductService)
                        @if($orderProductService->product)
                        <li>{{ $orderProductService->product->name }}</li>
                        <small>({{ $orderProductService->productService->name }})
                            <strong class="text-danger">({{ $orderProductService->productService->price }})</strong></small>
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
                <td>{{ number_format($order->sum_price, 2) }}</td>

                <td>{{ $order->created_at }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6">No orders found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{ $orders->links() }}

    <hr>

    <h2>{{__('messages.balance_summary')}}</h2>

    <div class="card">
        <div class="card-body">
            <p><strong>{{__('messages.total_billed')}}:</strong> {{ number_format($totalBilled, 2) }}</p>
            <p><strong>{{__('messages.total_paid')}}:</strong> {{ number_format($totalPaid, 2) }}</p>
            <p><strong>{{__('messages.current_balance')}}:</strong> {{ number_format($currentBalance, 2) }}</p>

            @if($currentBalance > 0)
                <p class="text-success">{{__('messages.you_have_a_credit_of')}} {{ number_format($currentBalance, 2) }}.</p>
                @elseif($currentBalance < 0)
                <p class="text-danger">{{ __('messages.you_owe') }} {{ number_format(abs($currentBalance), 2) }}.</p>
                @else
                <p>{{ __('your_balance_is') }} 0.00.</p>
                @endif

                <a href="#" class="btn btn-success mt-3">{{__('messages.make_payment')}}</a>
        </div>
    </div>
</div>
@endsection