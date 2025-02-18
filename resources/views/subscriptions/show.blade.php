@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.subscription_details') }}</h1>

    <p>{{ __('messages.id') }}: {{ $subscription->id }}</p>
    <p>{{ __('messages.paid') }}: {{ $subscription->paid }}</p> {{-- Display the decimal value --}}
    <p>{{ __('messages.benefit') }}: {{ $subscription->benefit }}</p> {{-- Display the decimal value --}}
    <p>{{ __('messages.start_date') }}: {{ $subscription->start_date }}</p>
    <p>{{ __('messages.end_date') }}: {{ $subscription->end_date }}</p>

    <a href="{{ route('subscriptions.index') }}" class="btn btn-secondary">{{ __('messages.back') }}</a>
</div>
@endsection