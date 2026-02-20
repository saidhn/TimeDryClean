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
            <input type="number" step="0.01" name="benefit" id="benefit" class="form-control" value="{{ $subscription->benefit }}">
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="period_duration" class="form-label">{{ __('messages.period_duration') }}</label>
                <input type="number" min="1" max="366" name="period_duration" id="period_duration" class="form-control" value="{{ $subscription->period_duration }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="period_unit" class="form-label">{{ __('messages.period_unit') }}</label>
                <select name="period_unit" id="period_unit" class="form-control" required>
                    <option value="day" {{ $subscription->period_unit === 'day' ? 'selected' : '' }}>{{ __('messages.period_day_plural') }}</option>
                    <option value="week" {{ $subscription->period_unit === 'week' ? 'selected' : '' }}>{{ __('messages.period_week_plural') }}</option>
                    <option value="month" {{ $subscription->period_unit === 'month' ? 'selected' : '' }}>{{ __('messages.period_month_plural') }}</option>
                    <option value="year" {{ $subscription->period_unit === 'year' ? 'selected' : '' }}>{{ __('messages.period_year_plural') }}</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('messages.update') }}</button>
    </form>
</div>
@endsection