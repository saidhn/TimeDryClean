@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h3 class="mb-0">{{ __('messages.user_details') }}</h3>
        @if($user->user_type === \App\Enums\UserType::CLIENT)
        <a href="{{ route('orders.create', ['user_id' => $user->id]) }}" target="_blank" rel="noopener" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>{{ __('messages.create_order_for_user') }}
        </a>
        @endif
    </div>

    @if (session('success'))
    <div class="alert alert-success mt-3">
        {{ session('success') }}
    </div>
    @endif

    <div class="mt-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">{{ __('messages.user_information') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>{{ __('messages.id') }}:</strong> {{ $user->id }}</p>
                        <p><strong>{{ __('messages.name') }}:</strong> {{ $user->name }}</p>
                        <p><strong>{{ __('messages.email') }}:</strong> {{ $user->email ?? __('messages.not_assigned') }}</p>
                        <p><strong>{{ __('messages.mobile') }}:</strong> {{ $user->mobile }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>{{ __('messages.user_type') }}:</strong> {{ $user->user_type_translated() }}</p>
                        <p><strong>{{ __('messages.created_at') }}:</strong> {{ $user->created_at->format('Y-m-d H:i') }}</p>
                        <p><strong>{{ __('messages.updated_at') }}:</strong> {{ $user->updated_at->format('Y-m-d H:i') }}</p>
                        <p><strong>{{ __('messages.address') }}:</strong> {{ $user->address_formatted() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">{{ __('messages.balance_information') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <h2 class="{{ $user->balance >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($user->balance, 2) }}
                        </h2>
                        <p class="text-muted">{{ __('messages.current_balance') }} ({{ __('messages.currency_symbol') }})</p>
                    </div>
                    <div class="col-md-3 text-center">
                        <h2 class="text-warning">
                            <i class="fas fa-star me-1"></i>{{ number_format($user->points_balance ?? 0, 2) }}
                        </h2>
                        <p class="text-muted">{{ __('messages.points_balance') }}</p>
                    </div>
                    <div class="col-md-2 text-center">
                        <h3 class="text-info">{{ $orderStats['total_orders'] }}</h3>
                        <p class="text-muted">{{ __('messages.total_orders') }}</p>
                    </div>
                    <div class="col-md-2 text-center">
                        <h3 class="text-warning">{{ $orderStats['pending_orders'] }}</h3>
                        <p class="text-muted">{{ __('messages.pending_orders') }}</p>
                    </div>
                    <div class="col-md-2 text-center">
                        <h3 class="text-success">{{ $orderStats['completed_orders'] }}</h3>
                        <p class="text-muted">{{ __('messages.completed_orders') }}</p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6 text-center">
                        <h4 class="text-primary">{{ number_format($orderStats['total_spent'], 2) }} {{ __('messages.currency_symbol') }}</h4>
                        <p class="text-muted">{{ __('messages.total_spent') }}</p>
                    </div>
                    <div class="col-md-6 text-center">
                        <h4 class="text-warning"><i class="fas fa-star me-1"></i>{{ number_format($orderStats['total_points_redeemed'] ?? 0, 2) }}</h4>
                        <p class="text-muted">{{ __('messages.points_redeemed') }}</p>
                    </div>
                </div>
                @if($user->balance < 0)
                <div class="row mt-3">
                    <div class="col-12 text-center">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#settleBalanceModal">
                            <i class="fas fa-hand-holding-usd me-1"></i>{{ __('messages.settle_balance') }}
                            ({{ number_format(abs($user->balance), 2) }} {{ __('messages.currency_symbol') }})
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>

        @php $latestSubscription = $user->clientSubscriptions->sortByDesc('id')->first(); @endphp
        @if($latestSubscription)
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">{{ __('messages.subscriptions') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('messages.subscription') }}</th>
                                <th>{{ __('messages.subscription_billing_status') }}</th>
                                <th>{{ __('messages.next_billing_date') }}</th>
                                <th>{{ __('messages.consecutive_failures') }}</th>
                                <th>{{ __('messages.last_billed_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($user->clientSubscriptions->sortByDesc('id') as $cs)
                            <tr>
                                <td>{{ optional($cs->subscription)->getDetails() }}</td>
                                <td><x-subscription-status-badge :client-subscription="$cs" /></td>
                                <td>{{ optional($cs->getPeriodEndAt())->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ $cs->consecutive_failures }}</td>
                                <td>{{ optional($cs->last_billed_at)->format('Y-m-d H:i') ?? __('messages.not_assigned') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($orders->count() > 0)
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">{{ __('messages.recent_orders') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('messages.order_id') }}</th>
                                <th>{{ __('messages.status') }}</th>
                                <th>{{ __('messages.total_price') }}</th>
                                <th>{{ __('messages.created_at') }}</th>
                                <th>{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                            <tr>
                                <td>#{{ $order->id }}</td>
                                <td>
                                    <span class="badge bg-{{ $order->status == App\Enums\OrderStatus::COMPLETED ? 'success' : ($order->status == App\Enums\OrderStatus::PENDING ? 'warning' : 'info') }}">
                                        {{ $order->status }}
                                    </span>
                                </td>
                                <td>
                                    @if($order->payment_method === 'points')
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-star me-1"></i>{{ number_format($order->points_used ?? 0, 2) }} pts
                                        </span>
                                    @else
                                        {{ number_format($order->sum_price, 2) }} {{ __('messages.currency_symbol') }}
                                    @endif
                                </td>
                                <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <a href="{{ route('orders.show', $order) }}" class="btn btn-info btn-sm">
                                        {{ __('messages.view') }}
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @else
        <div class="alert alert-info">
            {{ __('messages.no_orders_found') }}
        </div>
        @endif

        <div class="mt-4">
            @if(Auth::guard('admin')->check())
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> {{ __('messages.back') }}
            </a>
            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-warning text-white">
                <i class="fa fa-edit"></i> {{ __('messages.edit') }}
            </a>
            @else
            <a href="{{ route('employee.dashboard') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> {{ __('messages.back') }}
            </a>
            @endif
        </div>
    </div>
</div>

@if($user->balance < 0)
{{-- Settle Balance Modal --}}
<div class="modal fade" id="settleBalanceModal" tabindex="-1" aria-labelledby="settleBalanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.users.settleBalance', $user->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="settleBalanceModalLabel">
                        <i class="fas fa-hand-holding-usd me-2"></i>{{ __('messages.settle_balance') }} — {{ $user->name }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                    @endif
                    <p class="mb-3">{{ __('messages.outstanding_balance') }}:
                        <strong class="text-danger">{{ number_format(abs($user->balance), 3) }} {{ __('messages.currency_symbol') }}</strong>
                    </p>
                    <div class="mb-3">
                        <label for="settle_amount" class="form-label fw-bold">{{ __('messages.amount') }} ({{ __('messages.currency_symbol') }})</label>
                        <input type="number" step="0.001" min="0.001" name="amount" id="settle_amount" class="form-control"
                               value="{{ number_format(abs($user->balance), 3, '.', '') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('messages.payment_method_label') }}</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="settle_cash" value="cash" checked>
                            <label class="form-check-label" for="settle_cash">
                                <i class="fas fa-money-bill me-1 text-success"></i>{{ __('messages.pay_with_money') }}
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="settle_knet" value="knet">
                            <label class="form-check-label" for="settle_knet">
                                <i class="fas fa-credit-card me-1 text-primary"></i>{{ __('messages.pay_with_knet') }}
                                @if(config('services.knet.debug'))
                                    <span class="badge bg-info ms-1">{{ __('messages.knet_sandbox') }}</span>
                                @endif
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-check me-1"></i>{{ __('messages.settle_balance') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = new bootstrap.Modal(document.getElementById('settleBalanceModal'));
        modal.show();
    });
</script>
@endif
@endif
@endsection
