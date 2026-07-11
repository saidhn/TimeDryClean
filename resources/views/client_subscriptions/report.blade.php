@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h3 class="mb-0">{{ __('messages.subscriptions_report') }}</h3>
        <a href="{{ route('client_subscriptions.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>{{ __('messages.back') }}
        </a>
    </div>

    @php
        $activeKey = \App\Models\ClientSubscription::STATUS_ACTIVE;
        $failedOnceKey = \App\Models\ClientSubscription::STATUS_FAILED_ONCE;
        $failedMultiKey = \App\Models\ClientSubscription::STATUS_FAILED_MULTIPLE;
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('subscriptions.report', ['status' => 'all']) }}" class="text-decoration-none">
                <div class="subscription-stat-card total">
                    <div class="stat-value">{{ $counts['total'] }}</div>
                    <div class="stat-label"><i class="fas fa-layer-group me-1"></i>{{ __('messages.total_subscriptions') }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('subscriptions.report', ['status' => $activeKey]) }}" class="text-decoration-none">
                <div class="subscription-stat-card active">
                    <div class="stat-value">{{ $counts[$activeKey] }}</div>
                    <div class="stat-label"><i class="fas fa-check-circle me-1"></i>{{ __('messages.active_subscriptions') }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('subscriptions.report', ['status' => $failedOnceKey]) }}" class="text-decoration-none">
                <div class="subscription-stat-card failed-once">
                    <div class="stat-value">{{ $counts[$failedOnceKey] }}</div>
                    <div class="stat-label"><i class="fas fa-exclamation-triangle me-1"></i>{{ __('messages.failed_once_subscriptions') }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('subscriptions.report', ['status' => $failedMultiKey]) }}" class="text-decoration-none">
                <div class="subscription-stat-card failed-multiple">
                    <div class="stat-value">{{ $counts[$failedMultiKey] }}</div>
                    <div class="stat-label"><i class="fas fa-times-circle me-1"></i>{{ __('messages.failed_multiple_subscriptions') }}</div>
                </div>
            </a>
        </div>
    </div>

    <ul class="nav nav-pills mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $statusFilter === 'all' ? 'active' : '' }}" href="{{ route('subscriptions.report', ['status' => 'all']) }}">
                {{ __('messages.filter_all') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $statusFilter === $activeKey ? 'active' : '' }}" href="{{ route('subscriptions.report', ['status' => $activeKey]) }}">
                {{ __('messages.active_subscriptions') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $statusFilter === $failedOnceKey ? 'active' : '' }}" href="{{ route('subscriptions.report', ['status' => $failedOnceKey]) }}">
                {{ __('messages.failed_once_subscriptions') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $statusFilter === $failedMultiKey ? 'active' : '' }}" href="{{ route('subscriptions.report', ['status' => $failedMultiKey]) }}">
                {{ __('messages.failed_multiple_subscriptions') }}
            </a>
        </li>
    </ul>

    @if($clientSubscriptions->isEmpty())
    <p>{{ __('messages.no_data_to_display') }}</p>
    @else
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead>
                <tr>
                    <th>{{ __('messages.id') }}</th>
                    <th>{{ __('messages.name') }}</th>
                    <th>{{ __('messages.mobile') }}</th>
                    <th>{{ __('messages.subscription') }}</th>
                    <th>{{ __('messages.subscription_billing_status') }}</th>
                    <th>{{ __('messages.next_billing_date') }}</th>
                    <th>{{ __('messages.consecutive_failures') }}</th>
                    <th>{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clientSubscriptions as $cs)
                <tr>
                    <td>{{ $cs->id }}</td>
                    <td>{{ optional($cs->client)->name }}</td>
                    <td>{{ optional($cs->client)->mobile }}</td>
                    <td>{{ optional($cs->subscription)->getDetails() }}</td>
                    <td><x-subscription-status-badge :client-subscription="$cs" /></td>
                    <td>{{ optional($cs->getPeriodEndAt())->format('Y-m-d') ?? '-' }}</td>
                    <td>{{ $cs->consecutive_failures }}</td>
                    <td>
                        @if($cs->user_id)
                        <a href="{{ route('admin.users.show', $cs->user_id) }}" class="btn btn-info btn-sm">{{ __('messages.show') }}</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-pagination :paginator="$clientSubscriptions" />
    @endif
</div>
@endsection
