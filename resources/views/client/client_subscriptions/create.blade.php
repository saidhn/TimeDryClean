@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.add_balance') }}</h1>

    <form action="{{ route('client.client_subscriptions.store') }}" method="POST">
        @csrf
        @if(isset($clientSubscription))
        @method('PUT')
        @endif
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

        <button type="submit" class="btn btn-primary">{{ __('messages.create') }}</button>
    </form>
</div>
@endsection