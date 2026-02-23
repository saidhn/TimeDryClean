@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.client_orders_and_balance') }}</h1>

    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif

    <h2 class="mt-4 mb-3">{{ __('messages.orders_with_billing_info') }}</h2>

    <div class="table-responsive">
        <table class="table table-bordered table-hover shadow-sm">
            <thead class="table-light">
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
                @php
                    $statusBadge = match($order->status) {
                        \App\Enums\OrderStatus::COMPLETED => 'success',
                        \App\Enums\OrderStatus::CANCELLED => 'danger',
                        \App\Enums\OrderStatus::PENDING => 'warning',
                        \App\Enums\OrderStatus::PROCESSING => 'info',
                        \App\Enums\OrderStatus::SHIPPED => 'primary',
                        default => 'secondary',
                    };
                @endphp
                <tr>
                    <td class="align-middle"><strong>#{{ $order->id }}</strong></td>
                    <td>
                        @if($order->orderProductServices->isNotEmpty())
                        <ul class="list-unstyled mb-0">
                            @foreach($order->orderProductServices as $ops)
                            @if($ops->product)
                            <li class="py-1">
                                <span class="text-dark">{{ $ops->product->name }}</span>
                                <small class="text-muted">({{ $ops->productService->name ?? '-' }})</small>
                                <br>
                                <small>
                                    {{ $ops->quantity }} × {{ number_format($ops->price_at_order ?? 0, 3) }} {{ __('messages.currency_symbol') }}
                                    = <strong class="text-primary">{{ number_format($ops->line_total ?? 0, 3) }} {{ __('messages.currency_symbol') }}</strong>
                                </small>
                            </li>
                            @endif
                            @endforeach
                        </ul>
                        @else
                        <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="align-middle">
                        <span class="badge bg-{{ $statusBadge }}">{{ $order->statusTranslated() }}</span>
                    </td>
                    <td class="align-middle">
                        @if($order->orderDelivery)
                            {{ number_format($order->orderDelivery->price ?? 0, 3) }} {{ __('messages.currency_symbol') }}
                        @else
                            <span class="text-muted">0</span>
                        @endif
                    </td>
                    <td class="align-middle">
                        <strong>{{ number_format($order->sum_price, 3) }} {{ __('messages.currency_symbol') }}</strong>
                    </td>
                    <td class="align-middle">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">{{ __('messages.no_orders_found') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-pagination :paginator="$orders" />

    <hr>

    <h2 class="mb-3">{{ __('messages.balance_summary') }}</h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>{{ __('messages.total_billed') }}:</strong> {{ number_format($totalBilled, 3) }} {{ __('messages.currency_symbol') }}</p>
                    <p><strong>{{ __('messages.total_paid') }}:</strong> {{ number_format($totalPaid, 3) }} {{ __('messages.currency_symbol') }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>{{ __('messages.current_balance') }}:</strong>
                        <span class="fs-5 fw-bold {{ $client->balance >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($client->balance, 3) }} {{ __('messages.currency_symbol') }}
                        </span>
                    </p>
                    @if($client->balance > 0)
                        <p class="text-success mb-0">{{ __('messages.you_have_a_credit_of') }} {{ number_format($client->balance, 3) }}.</p>
                    @elseif($client->balance < 0)
                        <p class="text-danger mb-0">{{ __('messages.you_owe') }} {{ number_format(abs($client->balance), 3) }}.</p>
                    @else
                        <p class="text-muted mb-0">{{ __('messages.your_balance_is') }} 0.00.</p>
                    @endif
                </div>
            </div>

            @if($client->balance < 0)
            <hr>
            <a href="{{ route('client.payment.create') }}" class="btn btn-success btn-lg">
                <i class="fas fa-credit-card"></i> {{ __('messages.make_payment') }} (KNET)
            </a>
            <p class="text-muted small mt-2">{{ __('messages.pay_via_knet') }}</p>
            @endif
        </div>
    </div>
</div>
@endsection