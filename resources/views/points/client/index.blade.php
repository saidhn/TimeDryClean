@extends('layouts.app')

@section('content')
<div class="container">
    <h3><i class="fas fa-star me-2"></i>{{ __('messages.my_points') }}</h3>

    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Points balance card --}}
    <div class="card bg-primary text-white mb-4">
        <div class="card-body text-center py-4">
            <h5 class="card-title">{{ __('messages.current_points_balance') }}</h5>
            <h2 class="fw-bold">{{ number_format($client->points_balance, 2) }} <small class="fs-6">pts</small></h2>
        </div>
    </div>

    {{-- Available packages --}}
    <h5 class="mb-3">{{ __('messages.points_packages') }}</h5>
    <div class="row g-3 mb-5">
        @forelse ($packages as $package)
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-primary">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-primary">{{ $package->name }}</h5>
                    @if ($package->description)
                    <p class="card-text text-muted small">{{ $package->description }}</p>
                    @endif
                    <div class="mt-auto">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-success fs-6">{{ number_format($package->points, 0) }} pts</span>
                            <span class="fw-bold">{{ number_format($package->price_kwd, 3) }} {{ __('messages.currency_symbol') }}</span>
                        </div>
                        <form action="{{ route('client.points.buy') }}" method="POST">
                            @csrf
                            <input type="hidden" name="points_package_id" value="{{ $package->id }}">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-shopping-cart me-1"></i>{{ __('messages.buy_points') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="alert alert-info">{{ __('messages.no_data') }}</div>
        </div>
        @endforelse
    </div>

    {{-- Purchase history --}}
    <h5 class="mb-3">{{ __('messages.points_purchase_history') }}</h5>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>{{ __('messages.id') }}</th>
                    <th>{{ __('messages.package_name') }}</th>
                    <th>{{ __('messages.package_points') }}</th>
                    <th>{{ __('messages.package_price_kwd') }}</th>
                    <th>{{ __('messages.payment_method_label') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th>{{ __('messages.created_at') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($history as $purchase)
                <tr>
                    <td>{{ $purchase->id }}</td>
                    <td>{{ optional($purchase->pointsPackage)->name ?? '-' }}</td>
                    <td>{{ number_format($purchase->points_awarded, 2) }}</td>
                    <td>{{ $purchase->price_paid_kwd ? number_format($purchase->price_paid_kwd, 3) . ' ' . __('messages.currency_symbol') : '-' }}</td>
                    <td>{{ $purchase->payment_method ?? '-' }}</td>
                    <td><span class="badge bg-{{ $purchase->status === 'completed' ? 'success' : ($purchase->status === 'pending' ? 'warning' : 'danger') }}">{{ $purchase->status }}</span></td>
                    <td>{{ $purchase->created_at->format('Y-m-d H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">{{ __('messages.no_data') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-pagination :paginator="$history" />
</div>
@endsection
