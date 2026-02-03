@extends('layouts.app')

@section('content')
<div class="container">
    <h3>{{ __('messages.user_details') }}</h3>

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
                        <p class="text-muted">{{ __('messages.current_balance') }}</p>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3 class="text-info">{{ $orderStats['total_orders'] }}</h3>
                        <p class="text-muted">{{ __('messages.total_orders') }}</p>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3 class="text-warning">{{ $orderStats['pending_orders'] }}</h3>
                        <p class="text-muted">{{ __('messages.pending_orders') }}</p>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3 class="text-success">{{ $orderStats['completed_orders'] }}</h3>
                        <p class="text-muted">{{ __('messages.completed_orders') }}</p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12 text-center">
                        <h4 class="text-primary">{{ number_format($orderStats['total_spent'], 2) }} {{ __('messages.currency_symbol') }}</h4>
                        <p class="text-muted">{{ __('messages.total_spent') }}</p>
                    </div>
                </div>
            </div>
        </div>

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
                                <td>{{ number_format($order->sum_price, 2) }} {{ __('messages.currency_symbol') }}</td>
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
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> {{ __('messages.back') }}
            </a>
            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-warning text-white">
                <i class="fa fa-edit"></i> {{ __('messages.edit') }}
            </a>
        </div>
    </div>
</div>
@endsection
