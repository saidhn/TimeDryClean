@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ isset($clientSubscription) ? __('messages.edit_client_subscription') : __('messages.create_client_subscription') }}</h1>

    <form action="{{ isset($clientSubscription) ? route('client_subscriptions.update', $clientSubscription) : route('client_subscriptions.store') }}" method="POST">
        @csrf
        @if(isset($clientSubscription))
        @method('PUT')
        @endif

        <div class="mb-3">
            <label for="user_id" class="form-label">{{ __('messages.client') }}</label>
            <select name="user_id" id="user_id" class="form-control" required>
                @foreach ($clients as $client)
                <option value="{{ $client->id }}" {{ isset($clientSubscription) && $clientSubscription->user_id == $client->id ? 'selected' : '' }}>
                    {{ $client->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="subscription_id" class="form-label">{{ __('messages.subscription') }}</label>
            <select name="subscription_id" id="subscription_id" class="form-control" required>
                @foreach ($subscriptions as $subscription)
                <option value="{{ $subscription->id }}" {{ isset($clientSubscription) && $clientSubscription->subscription_id == $subscription->id ? 'selected' : '' }}>
                    {{ $subscription->getDetails() }}
                </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">{{ isset($clientSubscription) ? __('messages.update') : __('messages.create') }}</button>
    </form>
</div>
@endsection