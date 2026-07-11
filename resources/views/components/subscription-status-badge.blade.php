@props(['clientSubscription' => null])

@php
    $status = $clientSubscription ? $clientSubscription->billingStatus() : null;

    $config = match ($status) {
        \App\Models\ClientSubscription::STATUS_ACTIVE => [
            'class' => 'subscription-badge subscription-badge-active',
            'icon' => 'fa-check-circle',
            'label' => __('messages.subscription_status_active'),
        ],
        \App\Models\ClientSubscription::STATUS_FAILED_ONCE => [
            'class' => 'subscription-badge subscription-badge-warning',
            'icon' => 'fa-exclamation-triangle',
            'label' => __('messages.subscription_status_failed_once'),
        ],
        \App\Models\ClientSubscription::STATUS_FAILED_MULTIPLE => [
            'class' => 'subscription-badge subscription-badge-danger',
            'icon' => 'fa-times-circle',
            'label' => __('messages.subscription_status_failed_multiple'),
        ],
        default => [
            'class' => 'subscription-badge subscription-badge-none',
            'icon' => 'fa-minus-circle',
            'label' => __('messages.subscription_status_none'),
        ],
    };
@endphp

<span class="{{ $config['class'] }}" title="{{ $clientSubscription && $clientSubscription->getPeriodEndAt() ? __('messages.next_billing_date') . ': ' . $clientSubscription->getPeriodEndAt()->format('Y-m-d') : '' }}">
    <i class="fas {{ $config['icon'] }}"></i>
    {{ $config['label'] }}
</span>
