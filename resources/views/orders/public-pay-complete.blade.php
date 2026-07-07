<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.payment_status') }} — {{ config('app.name') }}</title>
    @if(app()->getLocale() === 'ar')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    @else
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f4f8; }
        .result-card { max-width: 480px; margin: 80px auto; border-radius: 16px; }
        .icon-circle { width: 80px; height: 80px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2.2rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="card result-card shadow-sm">
        <div class="card-body text-center p-5">

            @if(session('already_paid'))
                {{-- Order was already paid --}}
                <div class="icon-circle bg-info text-white mb-3">
                    <i class="fas fa-info-circle"></i>
                </div>
                <h4 class="mt-2 mb-1">{{ __('messages.order_already_paid') }}</h4>
                @if($order)
                    <p class="text-muted">{{ __('messages.id') }} #{{ $order->id }}</p>
                @endif

            @elseif($payment && $payment->status === 'success')
                {{-- Payment succeeded --}}
                <div class="icon-circle bg-success text-white mb-3">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h4 class="mt-2 mb-1 text-success">{{ __('messages.payment_success') }}</h4>
                @if($order)
                    <p class="text-muted mb-1">{{ __('messages.id') }} #{{ $order->id }}</p>
                @endif
                <p class="text-muted mb-1">
                    {{ __('messages.total_price') }}: <strong>{{ number_format($payment->amount, 3) }} KWD</strong>
                </p>
                @if($payment->transaction_id)
                    <p class="text-muted small">
                        {{ __('messages.transaction_id') }}: <code>{{ $payment->transaction_id }}</code>
                    </p>
                @endif
                <hr>
                <p class="text-muted mb-0">{{ __('messages.payment_link_thank_you') }}</p>

            @else
                {{-- Payment failed or no payment found --}}
                <div class="icon-circle bg-danger text-white mb-3">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h4 class="mt-2 mb-1 text-danger">{{ __('messages.payment_failed') }}</h4>
                @if($order)
                    <p class="text-muted mb-1">{{ __('messages.id') }} #{{ $order->id }}</p>
                @endif
                @if(session('error'))
                    <p class="text-danger small">{{ session('error') }}</p>
                @else
                    <p class="text-muted">{{ __('messages.payment_failed_description') }}</p>
                @endif

                @if($order && !$order->is_paid)
                    <a href="{{ URL::signedRoute('orders.public-pay', ['order' => $order->id]) }}"
                       class="btn btn-primary mt-3">
                        <i class="fas fa-redo me-2"></i>{{ __('messages.retry_payment') }}
                    </a>
                @endif
            @endif

        </div>
    </div>
    <p class="text-center text-muted small mt-3">{{ config('app.name') }}</p>
</div>
</body>
</html>
