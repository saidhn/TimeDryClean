@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.client_subscription_details') }}</h1>

    <p>{{ __('messages.id') }}: {{ $clientSubscription->id }}</p>
    <p>{{ __('messages.client') }}: {{ $clientSubscription->client->name }}</p>
    <p>{{ __('messages.subscription') }}: {{ $clientSubscription->subscription->getDetails() }}</p>
    <p>{{ __('messages.date') }}: {{ $clientSubscription->activated_at?->format('Y-m-d H:i') ?? $clientSubscription->created_at->format('Y-m-d H:i') }}</p>
    <p>{{ __('messages.subscription_billing_status') }}: <x-subscription-status-badge :client-subscription="$clientSubscription" /></p>
    @php $periodEnd = $clientSubscription->getPeriodEndAt(); @endphp
    @if($periodEnd)
    <p>{{ __('messages.next_billing_date') }}: {{ $periodEnd->format('Y-m-d') }}</p>
    @endif

    <a href="{{ route('client_subscriptions.index') }}" class="btn btn-secondary">{{ __('messages.back') }}</a>
</div>
@endsection