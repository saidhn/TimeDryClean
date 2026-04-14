@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>{{ __('messages.create_points_package') }}</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('points.packages.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">{{ __('messages.package_name') }}</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price_kwd" class="form-label fw-bold">{{ __('messages.package_price_kwd') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ __('messages.currency_symbol') }}</span>
                                    <input type="number" name="price_kwd" id="price_kwd" step="0.001" min="0"
                                           class="form-control @error('price_kwd') is-invalid @enderror"
                                           value="{{ old('price_kwd') }}" required>
                                </div>
                                @error('price_kwd')<div class="text-danger small">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="points" class="form-label fw-bold">{{ __('messages.package_points') }}</label>
                                <input type="number" name="points" id="points" step="0.01" min="0.01"
                                       class="form-control @error('points') is-invalid @enderror"
                                       value="{{ old('points') }}" required>
                                @error('points')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">{{ __('messages.description') }}</label>
                            <textarea name="description" id="description" rows="3"
                                      class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                                       {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">{{ __('messages.active') }}</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('points.packages.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>{{ __('messages.cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>{{ __('messages.create') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
