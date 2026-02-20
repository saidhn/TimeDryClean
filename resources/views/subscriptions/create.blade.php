@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.create_subscription') }}</h1>

    <form action="{{ route('subscriptions.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="paid" class="form-label">{{ __('messages.paid') }}</label>
            <input type="number" step="0.01" name="paid" id="paid" class="form-control" value="0.00"> {{-- Use number input with step --}}
        </div>

        <div class="mb-3">
            <label for="benefit" class="form-label">{{ __('messages.benefit') }}</label>
            <input type="number" step="0.01" name="benefit" id="benefit" class="form-control" value="0.00">
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="period_duration" class="form-label">{{ __('messages.period_duration') }}</label>
                <input type="number" min="1" max="366" name="period_duration" id="period_duration" class="form-control" value="1" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="period_unit" class="form-label">{{ __('messages.period_unit') }}</label>
                <select name="period_unit" id="period_unit" class="form-control" required>
                    <option value="day">{{ __('messages.period_day_plural') }}</option>
                    <option value="week">{{ __('messages.period_week_plural') }}</option>
                    <option value="month" selected>{{ __('messages.period_month_plural') }}</option>
                    <option value="year">{{ __('messages.period_year_plural') }}</option>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('messages.create') }}</button>
    </form>
</div>
@endsection