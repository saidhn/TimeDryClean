@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            @php
                $paymentDetails = json_decode($payment->details, true) ?? [];
                $isOrderPayment = ($paymentDetails['type'] ?? null) === 'order';
                $orderId = $paymentDetails['order_id'] ?? null;
            @endphp
            <div class="card shadow">
                @if($payment->status === 'completed')
                <div class="card-header bg-success text-white text-center">
                    <i class="fas fa-check-circle fa-2x"></i>
                    <h4 class="mt-2 mb-0">{{ __('messages.payment_successful') }}</h4>
                </div>
                <div class="card-body text-center">
                    <p class="lead text-success">{{ __('messages.payment_thank_you') }}</p>

                    <div class="p-3 bg-light rounded my-4">
                        <div class="row">
                            <div class="col-6 text-end"><strong>{{ __('messages.payment_amount') }}:</strong></div>
                            <div class="col-6 text-start">{{ number_format($payment->amount, 3) }} {{ __('messages.currency_symbol') }}</div>
                        </div>
                        <div class="row">
                            <div class="col-6 text-end"><strong>{{ __('messages.payment_method_label') }}:</strong></div>
                            <div class="col-6 text-start">KNET</div>
                        </div>
                        <div class="row">
                            <div class="col-6 text-end"><strong>{{ __('messages.payment_reference') }}:</strong></div>
                            <div class="col-6 text-start">{{ $payment->transaction_id }}</div>
                        </div>
                        <div class="row">
                            <div class="col-6 text-end"><strong>{{ __('messages.date') }}:</strong></div>
                            <div class="col-6 text-start">{{ $payment->payment_date?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i') }}</div>
                        </div>
                        @if($isOrderPayment && $orderId)
                        <div class="row mt-2">
                            <div class="col-6 text-end"><strong>{{ __('messages.order') }}:</strong></div>
                            <div class="col-6 text-start">
                                <a href="{{ route('orders.show', $orderId) }}" class="fw-bold text-success">#{{ $orderId }}</a>
                            </div>
                        </div>
                        @else
                        <div class="row mt-2">
                            <div class="col-6 text-end"><strong>{{ __('messages.current_balance') }}:</strong></div>
                            <div class="col-6 text-start">
                                <span class="{{ $payment->user->balance >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                    {{ number_format($payment->user->balance, 3) }} {{ __('messages.currency_symbol') }}
                                </span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @else
                <div class="card-header bg-danger text-white text-center">
                    <i class="fas fa-times-circle fa-2x"></i>
                    <h4 class="mt-2 mb-0">{{ __('messages.payment_failed') }}</h4>
                </div>
                <div class="card-body text-center">
                    <p class="lead text-danger">{{ __('messages.payment_failed_message') }}</p>

                    <div class="p-3 bg-light rounded my-4">
                        <p class="mb-1"><strong>{{ __('messages.payment_reference') }}:</strong> {{ $payment->transaction_id }}</p>
                        <p class="mb-0"><strong>{{ __('messages.payment_amount') }}:</strong> {{ number_format($payment->amount, 3) }} {{ __('messages.currency_symbol') }}</p>
                    </div>

                    @if($isOrderPayment && $orderId)
                    <a href="{{ route('orders.show', $orderId) }}" class="btn btn-warning btn-lg">
                        <i class="fas fa-redo"></i> {{ __('messages.retry_order_payment') }}
                    </a>
                    @else
                    <a href="{{ route('client.payment.create') }}" class="btn btn-warning btn-lg">
                        <i class="fas fa-redo"></i> {{ __('messages.try_again') }}
                    </a>
                    @endif
                </div>
                @endif

                <div class="card-footer text-center">
                    @if($isOrderPayment)
                    <a href="{{ route('orders.index') }}" class="btn btn-primary">
                        <i class="fas fa-list"></i> {{ __('messages.orders') }}
                    </a>
                    @else
                    <a href="{{ route('client.bills.index') }}" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> {{ __('messages.back') }}
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
