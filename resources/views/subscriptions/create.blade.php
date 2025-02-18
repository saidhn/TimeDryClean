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
            <input type="number" step="0.01" name="benefit" id="benefit" class="form-control" value="0.00"> {{-- Use number input with step --}}
        </div>

        <div class="mb-3">
            <label for="start_date" class="form-label">{{ __('messages.start_date') }}</label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}" required>
        </div>

        <div class="mb-3">
            <label for="end_date" class="form-label">{{ __('messages.end_date') }}</label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}" required>
        </div>


        <button type="submit" class="btn btn-primary">{{ __('messages.create') }}</button>
    </form>
</div>
@endsection