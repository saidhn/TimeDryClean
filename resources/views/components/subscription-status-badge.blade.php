@props(['clientSubscription' => null])

@php
    $status = $clientSubscription ? $clientSubscription->billingStatus() : null;

    $config = match ($status) {
        \App\Models\ClientSubscription::STATUS_ACTIVE => [
            'class' => 'status-pill status-pill-good',
            'label' => __('messages.subscription_status_active'),
        ],
        \App\Models\ClientSubscription::STATUS_FAILED_ONCE => [
            'class' => 'status-pill status-pill-warning',
            'label' => __('messages.subscription_status_failed_once'),
        ],
        \App\Models\ClientSubscription::STATUS_FAILED_MULTIPLE => [
            'class' => 'status-pill status-pill-critical',
            'label' => __('messages.subscription_status_failed_multiple'),
        ],
        default => [
            'class' => 'status-pill status-pill-neutral',
            'label' => __('messages.subscription_status_none'),
        ],
    };
@endphp

<span class="{{ $config['class'] }}" title="{{ $clientSubscription && $clientSubscription->getPeriodEndAt() ? __('messages.next_billing_date') . ': ' . $clientSubscription->getPeriodEndAt()->format('Y-m-d') : '' }}">
    {{ $config['label'] }}
</span>
