@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('messages.add_subscription') }}</h1>

    @if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($subscriptions->isEmpty())
    <div class="alert alert-info">
        {{ __('messages.subscription_no_available_plans') }}
    </div>
    @else
    <form id="subscription-form" action="{{ route('client.client_subscriptions.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="subscription_id" class="form-label">{{ __('messages.subscription') }}</label>
            <select name="subscription_id" id="subscription_id" class="form-control" required>
                <option value="">-- {{ __('messages.select') }} --</option>
                @foreach ($subscriptions as $subscription)
                <option
                    value="{{ $subscription->id }}"
                    data-paid="{{ $subscription->paid }}"
                    data-benefit="{{ $subscription->benefit }}"
                    data-period="{{ $subscription->period_label }}"
                    {{ isset($clientSubscription) && $clientSubscription->subscription_id == $subscription->id ? 'selected' : '' }}>
                    {{ $subscription->getDetails() }}
                </option>
                @endforeach
            </select>
        </div>

        {{-- Subscription summary card (shown when a plan is selected) --}}
        <div id="subscription-summary" class="card border-primary mb-4 d-none">
            <div class="card-body">
                <h5 class="card-title">{{ __('messages.subscription_details') }}</h5>
                <ul class="list-unstyled mb-0">
                    <li>
                        <strong>{{ __('messages.paid') }}:</strong>
                        <span id="summary-paid">—</span> {{ __('messages.currency_symbol') }}
                    </li>
                    <li>
                        <strong>{{ __('messages.benefit') }}:</strong>
                        <span id="summary-benefit">—</span> {{ __('messages.currency_symbol') }}
                    </li>
                    <li>
                        <strong>{{ __('messages.period') }}:</strong>
                        <span id="summary-period">—</span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Free subscription button --}}
        <button type="submit" id="btn-free" class="btn btn-success d-none">
            <i class="fas fa-check-circle"></i> {{ __('messages.create') }}
        </button>

        {{-- Paid subscription button --}}
        <button type="submit" id="btn-pay" class="btn btn-primary d-none">
            <i class="fas fa-credit-card"></i> {{ __('messages.pay_via_knet') }}
        </button>

        <a href="{{ route('client.clientSubscription.index') }}" class="btn btn-secondary ms-2">
            {{ __('messages.back') }}
        </a>
    </form>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const select      = document.getElementById('subscription_id');
        const summary     = document.getElementById('subscription-summary');
        const summaryPaid = document.getElementById('summary-paid');
        const summaryBenefit = document.getElementById('summary-benefit');
        const summaryPeriod  = document.getElementById('summary-period');
        const btnFree = document.getElementById('btn-free');
        const btnPay  = document.getElementById('btn-pay');

        select.addEventListener('change', function () {
            const option = this.options[this.selectedIndex];
            if (!option || !option.value) {
                summary.classList.add('d-none');
                btnFree.classList.add('d-none');
                btnPay.classList.add('d-none');
                return;
            }

            const paid    = parseFloat(option.dataset.paid) || 0;
            const benefit = parseFloat(option.dataset.benefit) || 0;
            const period  = option.dataset.period || '';

            summaryPaid.textContent    = paid.toFixed(3);
            summaryBenefit.textContent = benefit.toFixed(3);
            summaryPeriod.textContent  = period;
            summary.classList.remove('d-none');

            if (paid <= 0) {
                btnFree.classList.remove('d-none');
                btnPay.classList.add('d-none');
            } else {
                btnFree.classList.add('d-none');
                btnPay.classList.remove('d-none');
            }
        });

        // Trigger change if a value is already selected (e.g. after validation error)
        if (select.value) {
            select.dispatchEvent(new Event('change'));
        }
    });
</script>
@endpush
@endsection