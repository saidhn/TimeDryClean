@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.client_subscription_details') }}</h1>

    <p>{{ __('messages.id') }}: {{ $clientSubscription->id }}</p>
    <p>{{ __('messages.client') }}: {{ $clientSubscription->client->name }}</p>
    <p>{{ __('messages.subscription') }}: {{ $clientSubscription->subscription->getDetails() }}</p>
    <p>{{ __('messages.date') }}: {{ $clientSubscription->activated_at?->format('Y-m-d H:i') ?? $clientSubscription->created_at->format('Y-m-d H:i') }}</p>
    @php $periodEnd = $clientSubscription->getPeriodEndAt(); @endphp
    @if($periodEnd)
    <p>{{ __('messages.period') }} {{ __('messages.end_date') }}: {{ $periodEnd->format('Y-m-d') }} {{ $clientSubscription->isActive() ? '(' . __('messages.subscription_active') . ')' : '(' . __('messages.subscription_expired') . ')' }}</p>
    @endif

    <a href="{{ route('admin.client_subscriptions.index') }}" class="btn btn-secondary">{{ __('messages.back') }}</a>
</div>
@endsection