@props(['clientSubscription' => null])

@php
    use App\Models\ClientSubscription as CS;

    if (!$clientSubscription) {
        $config = [
            'class' => 'status-pill status-pill-neutral',
            'label' => __('messages.subscription_status_none'),
        ];
    } elseif ($clientSubscription->isSuspended()) {
        $config = [
            'class' => 'status-pill status-pill-critical',
            'label' => __('messages.subscription_status_suspended'),
        ];
    } elseif ($clientSubscription->isPendingPayment()) {
        $config = [
            'class' => 'status-pill status-pill-warning',
            'label' => __('messages.subscription_status_pending_payment'),
        ];
    } else {
        // Active subscription — show billing health
        $billingStatus = $clientSubscription->billingStatus();
        $config = match ($billingStatus) {
            CS::BILLING_STATUS_FAILED_ONCE     => [
                'class' => 'status-pill status-pill-warning',
                'label' => __('messages.subscription_status_failed_once'),
            ],
            CS::BILLING_STATUS_FAILED_MULTIPLE => [
                'class' => 'status-pill status-pill-critical',
                'label' => __('messages.subscription_status_failed_multiple'),
            ],
            default => [
                'class' => 'status-pill status-pill-good',
                'label' => __('messages.subscription_status_active'),
            ],
        };
    }
@endphp

<span
    class="{{ $config['class'] }}"
    title="{{ $clientSubscription && $clientSubscription->getPeriodEndAt() ? __('messages.next_billing_date') . ': ' . $clientSubscription->getPeriodEndAt()->format('Y-m-d') : '' }}"
>
    {{ $config['label'] }}
</span>
