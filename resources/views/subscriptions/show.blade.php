@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.subscription_details') }}</h1>

    <p>{{ __('messages.id') }}: {{ $subscription->id }}</p>
    <p>{{ __('messages.paid') }}: {{ $subscription->paid }}</p>
    <p>{{ __('messages.benefit') }}: {{ $subscription->benefit }}</p>
    <p>{{ __('messages.period') }}: {{ $subscription->period_label }}</p>

    <a href="{{ route('subscriptions.index') }}" class="btn btn-secondary">{{ __('messages.back') }}</a>
</div>
@endsection