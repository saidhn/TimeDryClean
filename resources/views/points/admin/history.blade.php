@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="fas fa-history me-2"></i>{{ __('messages.points_purchase_history') }}</h3>
        <a href="{{ route('points.assign.form') }}" class="btn btn-success btn-sm">
            <i class="fas fa-hand-holding-heart me-1"></i>{{ __('messages.assign_points') }}
        </a>
    </div>

    <div class="mb-3">
        <form action="{{ route('points.history') }}" method="GET">
            <div class="input-group">
                <input type="text" name="search" class="form-control"
                       placeholder="{{ __('messages.search') }}" value="{{ request('search') }}">
                <button class="btn btn-outline-secondary" type="submit">{{ __('messages.search') }}</button>
            </div>
        </form>
    </div>

    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>{{ __('messages.id') }}</th>
                    <th>{{ __('messages.user') }}</th>
                    <th>{{ __('messages.package_name') }}</th>
                    <th>{{ __('messages.package_points') }}</th>
                    <th>{{ __('messages.package_price_kwd') }}</th>
                    <th>{{ __('messages.payment_method_label') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th>{{ __('messages.added_by') }}</th>
                    <th>{{ __('messages.created_at') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchases as $purchase)
                <tr>
                    <td>{{ $purchase->id }}</td>
                    <td>{{ optional($purchase->user)->name ?? '-' }}</td>
                    <td>{{ optional($purchase->pointsPackage)->name ?? '-' }}</td>
                    <td>{{ number_format($purchase->points_awarded, 2) }}</td>
                    <td>{{ $purchase->price_paid_kwd ? number_format($purchase->price_paid_kwd, 3) . ' ' . __('messages.currency_symbol') : '-' }}</td>
                    <td>{{ $purchase->payment_method ?? '-' }}</td>
                    <td>
                        <span class="badge bg-{{ $purchase->status === 'completed' ? 'success' : ($purchase->status === 'pending' ? 'warning' : 'danger') }}">
                            {{ $purchase->status }}
                        </span>
                    </td>
                    <td>{{ optional($purchase->addedBy)->name ?? '-' }}</td>
                    <td>{{ $purchase->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted">{{ __('messages.no_data') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :paginator="$purchases" />
</div>
@endsection
