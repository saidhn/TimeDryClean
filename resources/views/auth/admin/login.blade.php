@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5"> {{-- Reduced column width for a narrower form --}}
            <div class="card shadow-lg"> {{-- Added shadow for depth --}}
                <div class="card-header bg-primary text-white text-center py-3"> {{-- Styled header --}}
                    <h4 class="mb-0">{{ __('messages.admin_login') }}</h4>
                </div>

                <div class="card-body p-4"> {{-- Added padding --}}
                    @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                    @endif
                    <form method="POST" action="{{ route('admin.login.post') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="mobile" class="form-label">{{ __('messages.mobile_no') }}</label>
                            <div class="input-group">
                                <input id="mobile" type="number" class="form-control @error('mobile') is-invalid @enderror" name="mobile" value="{{ old('mobile') }}" required autocomplete="mobile" autofocus>
                                @error('mobile')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('messages.password') }}</label>
                            <div class="input-group">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                                @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label" for="remember">
                                {{ __('messages.remember_me') }}
                            </label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg"> {{-- Larger button --}}
                                {{ __('messages.login') }}
                            </button>
                        </div>

                        <div class="mt-3 text-center"> {{-- Centered links --}}
                            @if (Route::has('password.request'))
                            <a class="btn btn-link" href="{{ route('password.request') }}">
                                {{ __('messages.forgot_your_password') }}
                            </a>
                            @endif
                            <p class="mt-2 mb-0">{{__('messages.dont_have_an_account')}} <a href="{{ route('admin.register') }}">{{__('messages.register')}}</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection