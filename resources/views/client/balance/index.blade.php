@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-wallet"></i> {{ __('messages.balance') }}
                </div>

                <div class="card-body text-center">
                    <p class="lead">
                        {{ __('messages.your_balance_is') }}:
                        <strong class="{{ $client->balance < 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($client->balance, 2) }}
                        </strong>
                    </p>

                    @if ($client->balance < 0)
                        <div class="mt-4">
                            <a href="#" class="btn btn-lg btn-danger shadow-sm">
                                <i class="fas fa-credit-card"></i> {{ __('messages.make_payment') }}
                            </a>
                            <p class="mt-2 text-muted">
                                {{ __('messages.balance_negative_message') }}
                            </p>
                        </div>
                    @else
                        <div class="mt-4">
                            <p class="text-success">{{ __('messages.balance_positive_message') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
        }
    </style>
@endpush