@if($order->hasDiscount())
<div class="discount-display alert alert-success border-success">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h6 class="alert-heading mb-2">
                <i class="fas fa-tag me-2"></i>{{ __('messages.discount_applied') }}
            </h6>
            <div class="discount-details">
                <p class="mb-1">
                    <strong>{{ __('messages.discount_type') }}:</strong> 
                    @if($order->discount_type === 'fixed')
                        <span class="badge bg-primary">{{ __('messages.fixed_amount') }}</span>
                    @else
                        <span class="badge bg-info">{{ __('messages.percentage') }}</span>
                    @endif
                </p>
                <p class="mb-1">
                    <strong>{{ __('messages.discount_value') }}:</strong> 
                    @if($order->discount_type === 'fixed')
                        ${{ number_format($order->discount_value, 2) }}
                    @else
                        {{ $order->discount_value }}%
                    @endif
                </p>
                <p class="mb-1">
                    <strong>{{ __('messages.discount_amount_calculated') }}:</strong> 
                    <span class="text-success fw-bold">-${{ number_format($order->discount_amount, 2) }}</span>
                </p>
                @if($order->discountAppliedBy)
                <p class="mb-1">
                    <strong>{{ __('messages.discount_applied_by') }}:</strong> {{ $order->discountAppliedBy->name ?? 'Staff' }}
                </p>
                @endif
                @if($order->discount_applied_at)
                <p class="mb-0">
                    <strong>{{ __('messages.discount_history_applied_at') }}:</strong> 
                    <small class="text-muted">{{ $order->discount_applied_at->format('M d, Y g:i A') }}</small>
                </p>
                @endif
            </div>
        </div>
        <div class="text-end">
            <div class="display-6 text-success fw-bold">
                {{ $order->discount_display }}
            </div>
        </div>
    </div>
</div>
@endif
