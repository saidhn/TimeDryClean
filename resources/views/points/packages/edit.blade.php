@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>{{ __('messages.edit_points_package') }}: {{ $package->name }}</h5>
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

                    <form action="{{ route('points.packages.update', $package) }}" method="POST">
                        @csrf @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">{{ __('messages.package_name') }}</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $package->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price_kwd" class="form-label fw-bold">{{ __('messages.package_price_kwd') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ __('messages.currency_symbol') }}</span>
                                    <input type="number" name="price_kwd" id="price_kwd" step="0.001" min="0"
                                           class="form-control @error('price_kwd') is-invalid @enderror"
                                           value="{{ old('price_kwd', $package->price_kwd) }}" required>
                                </div>
                                @error('price_kwd')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="points" class="form-label fw-bold">{{ __('messages.package_points') }}</label>
                                <input type="number" name="points" id="points" step="0.01" min="0.01"
                                       class="form-control @error('points') is-invalid @enderror"
                                       value="{{ old('points', $package->points) }}" required>
                                @error('points')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">{{ __('messages.description') }}</label>
                            <textarea name="description" id="description" rows="3"
                                      class="form-control @error('description') is-invalid @enderror">{{ old('description', $package->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                                       {{ old('is_active', $package->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">{{ __('messages.active') }}</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('points.packages.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>{{ __('messages.cancel') }}
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-1"></i>{{ __('messages.update') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
