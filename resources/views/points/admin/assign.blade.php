@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-hand-holding-heart me-2"></i>{{ __('messages.assign_points') }}</h5>
                </div>
                <div class="card-body">
                    @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif
                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('points.assign') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="user_id" class="form-label fw-bold">{{ __('messages.user') }}</label>
                            <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                                <option value="">{{ __('messages.select_user') }}</option>
                                @foreach ($clients as $client)
                                <option value="{{ $client->id }}" {{ old('user_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->name }} ({{ $client->mobile }}) — {{ number_format($client->points_balance, 2) }} pts
                                </option>
                                @endforeach
                            </select>
                            @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="points_package_id" class="form-label fw-bold">{{ __('messages.points_packages') }}</label>
                            <select name="points_package_id" id="points_package_id" class="form-select @error('points_package_id') is-invalid @enderror" required>
                                <option value="">{{ __('messages.select_package') }}</option>
                                @foreach ($packages as $package)
                                <option value="{{ $package->id }}" {{ old('points_package_id') == $package->id ? 'selected' : '' }}>
                                    {{ $package->name }} — {{ number_format($package->points, 0) }} pts
                                </option>
                                @endforeach
                            </select>
                            @error('points_package_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('points.history') }}" class="btn btn-secondary">
                                <i class="fas fa-history me-1"></i>{{ __('messages.points_purchase_history') }}
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-1"></i>{{ __('messages.assign_points') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
