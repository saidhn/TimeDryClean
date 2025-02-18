@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.edit_subscription') }}</h1>

    <form action="{{ route('subscriptions.update', $subscription) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="paid" class="form-label">{{ __('messages.paid') }}</label>
            <input type="number" step="0.01" name="paid" id="paid" class="form-control" value="{{ $subscription->paid }}"> {{-- Value from the database --}}
        </div>

        <div class="mb-3">
            <label for="benefit" class="form-label">{{ __('messages.benefit') }}</label>
            <input type="number" step="0.01" name="benefit" id="benefit" class="form-control" value="{{ $subscription->benefit }}"> {{-- Value from the database --}}
        </div>

        <div class="mb-3">
            <label for="start_date" class="form-label">{{ __('messages.start_date') }}</label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $subscription->start_date }}" required>
        </div>

        <div class="mb-3">
            <label for="end_date" class="form-label">{{ __('messages.end_date') }}</label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $subscription->end_date }}" required>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('messages.update') }}</button>
    </form>
</div>
@endsection