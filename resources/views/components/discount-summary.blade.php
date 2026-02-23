<div class="discount-summary">
    <div class="row g-2">
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <span>{{ __('messages.order_subtotal') }}:</span>
                <span>{{ __('messages.currency_symbol') }} {{ number_format($order->sum_price, 2) }}</span>
            </div>
        </div>
        
        @if($order->hasDiscount())
        <div class="col-12">
            <div class="d-flex justify-content-between text-success">
                <span>
                    <i class="fas fa-tag me-1"></i>{{ __('messages.discount') }}
                    @if($order->discount_type === 'percentage')
                        ({{ $order->discount_value }}%)
                    @endif:
                </span>
                <span class="fw-bold">-{{ __('messages.currency_symbol') }} {{ number_format($order->discount_amount, 2) }}</span>
            </div>
        </div>
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <span>{{ __('messages.new_subtotal') }}:</span>
                <span>{{ __('messages.currency_symbol') }} {{ number_format($order->sum_price - $order->discount_amount, 2) }}</span>
            </div>
        </div>
        @endif
        
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <span>{{ __('messages.tax') }}:</span>
                <span>{{ __('messages.currency_symbol') }} {{ number_format($order->tax ?? 0, 2) }}</span>
            </div>
        </div>
        
        <div class="col-12">
            <hr class="my-2">
        </div>
        
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <span>{{ __('messages.total_price') }}:</span>
                <span class="{{ $order->hasDiscount() ? 'text-success' : '' }}">
                    {{ __('messages.currency_symbol') }} {{ number_format($order->total ?? ($order->sum_price + ($order->tax ?? 0)), 2) }}
                </span>
            </div>
        </div>
        
        @if($order->hasDiscount())
        <div class="col-12">
            <div class="alert alert-success py-2 px-3 mb-0 mt-2">
                <small>
                    <i class="fas fa-check-circle me-1"></i>
                    {{ __('messages.discount_you_saved') }} <strong>{{ __('messages.currency_symbol') }} {{ number_format($order->discount_amount, 2) }}</strong> {{ __('messages.savings') }}!
                </small>
            </div>
        </div>
        @endif
    </div>
</div>
