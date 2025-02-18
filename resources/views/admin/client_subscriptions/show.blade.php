@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.client_subscription_details') }}</h1>

    <p>{{ __('messages.id') }}: {{ $clientSubscription->id }}</p>
    <p>{{ __('messages.client') }}: {{ $clientSubscription->client->name }}</p>
    <p>{{ __('messages.subscription') }}: {{ $clientSubscription->subscription->id }} - ${{ $clientSubscription->subscription->paid }} - ${{ $clientSubscription->subscription->benefit }} - {{ $clientSubscription->subscription->start_date }} to {{ $clientSubscription->subscription->end_date }}</p>

    <a href="{{ route('admin.client_subscriptions.index') }}" class="btn btn-secondary">{{ __('messages.back') }}</a>
</div>
@endsection