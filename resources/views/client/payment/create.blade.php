@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-credit-card"></i> {{ __('messages.make_payment') }} - KNET
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-4 p-3 bg-light rounded">
                        <h5>{{ __('messages.balance_summary') }}</h5>
                        <p class="mb-1">
                            {{ __('messages.current_balance') }}:
                            <strong class="{{ $client->balance >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($client->balance, 3) }} {{ __('messages.currency_symbol') }}
                            </strong>
                        </p>
                        @if($client->balance < 0)
                        <p class="text-danger mb-0">{{ __('messages.you_owe') }} {{ number_format(abs($client->balance), 3) }} {{ __('messages.currency_symbol') }}</p>
                        @endif
                    </div>

                    <form action="{{ route('client.payment.store') }}" method="POST" id="paymentForm">
                        @csrf

                        <div class="mb-3">
                            <label for="amount" class="form-label fw-bold">{{ __('messages.payment_amount') }}</label>
                            <div class="input-group">
                                <input type="number" step="0.001" min="0.001" name="amount" id="amount"
                                    class="form-control form-control-lg @error('amount') is-invalid @enderror"
                                    value="{{ old('amount', $amountDue > 0 ? number_format($amountDue, 3, '.', '') : '') }}"
                                    required>
                                <span class="input-group-text">{{ __('messages.currency_symbol') }}</span>
                            </div>
                            @error('amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <a href="{{ route('client.bills.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> {{ __('messages.back') }}
                            </a>
                            <button type="submit" class="btn btn-success btn-lg" id="payBtn">
                                <i class="fas fa-lock"></i> {{ __('messages.pay_now') }}
                            </button>
                        </div>
                    </form>

                    <div class="mt-4 text-center">
                        <img src="https://upload.wikimedia.org/wikipedia/en/thumb/a/a5/KNET_logo.svg/1200px-KNET_logo.svg.png" alt="KNET" height="40" class="opacity-75">
                        <p class="text-muted small mt-2">{{ __('messages.pay_via_knet') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('paymentForm').addEventListener('submit', function() {
        var btn = document.getElementById('payBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("messages.processing") }}...';
    });
</script>
@endpush
