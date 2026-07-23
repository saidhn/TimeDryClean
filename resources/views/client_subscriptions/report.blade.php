@extends('layouts.app')

@section('content')
<div class="container">
    <div class="report-header">
        <div>
            <h3>{{ __('messages.subscriptions_report') }}</h3>
            <p>{{ __('messages.subscriptions_report_subtitle') }}</p>
        </div>
        <a href="{{ route('client_subscriptions.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i>{{ __('messages.back') }}
        </a>
    </div>

    @php
        $activeKey = \App\Models\ClientSubscription::BILLING_STATUS_OK;
        $failedOnceKey = \App\Models\ClientSubscription::BILLING_STATUS_FAILED_ONCE;
        $failedMultiKey = \App\Models\ClientSubscription::BILLING_STATUS_FAILED_MULTIPLE;

        $total = max((int) $counts['total'], 0);
        $activeCount = (int) $counts[$activeKey];
        $onceCount = (int) $counts[$failedOnceKey];
        $multiCount = (int) $counts[$failedMultiKey];

        $pct = fn ($n) => $total > 0 ? round(($n / $total) * 100) : 0;
    @endphp

    {{-- Stat cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('subscriptions.report', ['status' => 'all']) }}" class="text-decoration-none">
                <div class="stat-card is-total">
                    <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                    <div class="stat-value">{{ $total }}</div>
                    <div class="stat-label">{{ __('messages.total_subscriptions') }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('subscriptions.report', ['status' => $activeKey]) }}" class="text-decoration-none">
                <div class="stat-card is-active">
                    <div class="stat-icon"><i class="fas fa-check"></i></div>
                    <div class="stat-value">{{ $activeCount }}</div>
                    <div class="stat-label">{{ __('messages.active_subscriptions') }}</div>
                    <div class="stat-share">{{ $pct($activeCount) }}% {{ __('messages.of_total') }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('subscriptions.report', ['status' => $failedOnceKey]) }}" class="text-decoration-none">
                <div class="stat-card is-warning">
                    <div class="stat-icon"><i class="fas fa-exclamation"></i></div>
                    <div class="stat-value">{{ $onceCount }}</div>
                    <div class="stat-label">{{ __('messages.failed_once_subscriptions') }}</div>
                    <div class="stat-share">{{ $pct($onceCount) }}% {{ __('messages.of_total') }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="{{ route('subscriptions.report', ['status' => $failedMultiKey]) }}" class="text-decoration-none">
                <div class="stat-card is-critical">
                    <div class="stat-icon"><i class="fas fa-times"></i></div>
                    <div class="stat-value">{{ $multiCount }}</div>
                    <div class="stat-label">{{ __('messages.failed_multiple_subscriptions') }}</div>
                    <div class="stat-share">{{ $pct($multiCount) }}% {{ __('messages.of_total') }}</div>
                </div>
            </a>
        </div>
    </div>

    {{-- Proportion bar --}}
    @if($total > 0)
    <div class="stat-card mb-4" style="--accent: #464687;">
        <div class="proportion-bar" role="img" aria-label="{{ __('messages.subscriptions_report_subtitle') }}">
            @if($activeCount > 0)
            <div class="segment good" style="flex-grow: {{ $activeCount }};"></div>
            @endif
            @if($onceCount > 0)
            <div class="segment warning" style="flex-grow: {{ $onceCount }};"></div>
            @endif
            @if($multiCount > 0)
            <div class="segment critical" style="flex-grow: {{ $multiCount }};"></div>
            @endif
        </div>
        <div class="proportion-legend">
            <span class="legend-item"><span class="legend-dot good"></span>{{ __('messages.active_subscriptions') }}: <strong>{{ $activeCount }}</strong> ({{ $pct($activeCount) }}%)</span>
            <span class="legend-item"><span class="legend-dot warning"></span>{{ __('messages.failed_once_subscriptions') }}: <strong>{{ $onceCount }}</strong> ({{ $pct($onceCount) }}%)</span>
            <span class="legend-item"><span class="legend-dot critical"></span>{{ __('messages.failed_multiple_subscriptions') }}: <strong>{{ $multiCount }}</strong> ({{ $pct($multiCount) }}%)</span>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="filter-pills">
        <a href="{{ route('subscriptions.report', ['status' => 'all']) }}" class="{{ $statusFilter === 'all' ? 'active' : '' }}">
            {{ __('messages.filter_all') }} ({{ $total }})
        </a>
        <a href="{{ route('subscriptions.report', ['status' => $activeKey]) }}" class="{{ $statusFilter === $activeKey ? 'active' : '' }}">
            {{ __('messages.active_subscriptions') }} ({{ $activeCount }})
        </a>
        <a href="{{ route('subscriptions.report', ['status' => $failedOnceKey]) }}" class="{{ $statusFilter === $failedOnceKey ? 'active' : '' }}">
            {{ __('messages.failed_once_subscriptions') }} ({{ $onceCount }})
        </a>
        <a href="{{ route('subscriptions.report', ['status' => $failedMultiKey]) }}" class="{{ $statusFilter === $failedMultiKey ? 'active' : '' }}">
            {{ __('messages.failed_multiple_subscriptions') }} ({{ $multiCount }})
        </a>
    </div>

    @if($clientSubscriptions->isEmpty())
    <div class="stat-card text-center py-5" style="--accent: var(--status-neutral);">
        <i class="fas fa-inbox fa-2x mb-2" style="color: var(--status-neutral);"></i>
        <p class="mb-0 text-muted">{{ __('messages.no_data_to_display') }}</p>
    </div>
    @else
    <div class="table-responsive data-table-wrap">
        <table class="table mb-0 align-middle">
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
                    <td class="text-muted">#{{ $cs->id }}</td>
                    <td class="fw-semibold">{{ optional($cs->client)->name }}</td>
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
    <div class="mt-3">
        <x-pagination :paginator="$clientSubscriptions" />
    </div>
    @endif
</div>
@endsection
