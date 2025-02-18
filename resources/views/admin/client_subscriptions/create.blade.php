@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ isset($clientSubscription) ? __('messages.edit_client_subscription') : __('messages.add_client_subscription') }}</h1>

    <form action="{{ isset($clientSubscription) ? route('client_subscriptions.update', $clientSubscription) : route('client_subscriptions.store') }}" method="POST">
        @csrf
        @if(isset($clientSubscription))
        @method('PUT')
        @endif
        <div class="mb-3">
            <label for="user_id">{{ __('messages.client') }}</label>
            <select id="client-select" name="user_id"
                class="form-control @error('user_id') is-invalid @enderror" required>
                <option value="">{{ __('messages.select_user') }}</option>
            </select>
            @error('user_id')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
            @enderror
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
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new TomSelect('#client-select', {
            valueField: 'id',
            labelField: 'name',
            searchField: 'name',
            load: function(query, callback) {
                fetch(`/users/search?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(json => {
                        // Check if the data exists and has length
                        if (json.data && json.data.length) {
                            callback(json.data);
                        } else {
                            callback([]); // No results found
                        }
                    })
                    .catch(() => {
                        callback([]); // In case of an error, return empty results
                    });
            },
            render: {
                option: function(item, escape) {
                    return `
                    <div>
                        <strong>${escape(item.name)}</strong>
                        <div class="text-muted">ID: ${escape(item.id)}, Mobile: ${escape(item.mobile)}</div>
                    </div>
                `;
                },
                item: function(item, escape) {
                    return `<div>${escape(item.name)}</div>`;
                }
            }
        });
    });
</script>
@endpush
@endsection