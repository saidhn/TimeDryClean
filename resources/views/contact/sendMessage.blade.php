@extends('layouts.app')

@push('scripts')
<link rel="stylesheet" href="{{ Vite::asset('resources/css/pages/contact.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="send-form-wrap">
        <div class="send-header">
            <div class="send-header-icon text-primary" style="color: #464687 !important;">
                <i class="fas fa-paper-plane"></i>
            </div>
            <h2>{{ __('messages.send_message_title') }}</h2>
            <p class="text-muted">{{ __('messages.contact_form') }}</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success rounded-pill border-0 shadow-sm text-center mb-4">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }} 🎉
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <form method="POST" action="{{ route('contact.send') }}">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="title" class="form-label fw-bold text-dark">{{ __('messages.title') }} <span class="text-danger">*</span></label>
                        <input id="title" type="text" class="form-control form-control-lg bg-light border-0 rounded-3 @error('title') is-invalid @enderror"
                            name="title" value="{{ old('title') }}" required autocomplete="title" autofocus placeholder="{{ __('messages.title') }}">
                        @error('title')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="message" class="form-label fw-bold text-dark">{{ __('messages.message') }} <span class="text-danger">*</span></label>
                        <textarea id="message" class="form-control bg-light border-0 rounded-3 @error('message') is-invalid @enderror"
                            name="message" required rows="6" placeholder="{{ __('messages.type_message_here') }}">{{ old('message') }}</textarea>
                        @error('message')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <div class="d-grid mt-5">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm">
                            <i class="fas fa-paper-plane me-2"></i>{{ __('messages.send') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="{{ route('contact.index') }}" class="text-muted text-decoration-none">
                <i class="fas fa-arrow-{{ get_direction() == 'rtl' ? 'right' : 'left' }} me-2"></i>{{ __('messages.back') }}
            </a>
        </div>
    </div>
</div>
@endsection