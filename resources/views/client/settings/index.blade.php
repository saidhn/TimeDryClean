@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-cog"></i> {{ __('messages.settings') }}
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('client.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="notification_language" class="form-label fw-bold">
                                {{ __('messages.notification_language') }}
                            </label>
                            <p class="text-muted small">{{ __('messages.notification_language_help') }}</p>
                            <select name="notification_language" id="notification_language" class="form-select @error('notification_language') is-invalid @enderror">
                                <option value="en" {{ ($client->notification_language ?? 'en') === 'en' ? 'selected' : '' }}>English</option>
                                <option value="ar" {{ ($client->notification_language ?? 'en') === 'ar' ? 'selected' : '' }}>العربية</option>
                            </select>
                            @error('notification_language')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> {{ __('messages.save') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
