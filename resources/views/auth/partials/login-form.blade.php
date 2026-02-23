{{--
  Shared login form partial with theme support.
  Variables: $theme (client|driver|employee|admin), $titleKey, $subtitleKey, $formAction, $formRoute
  Each theme defines: icon, color classes, accent
--}}
@php
    $themes = [
        'client' => [
            'icon' => 'fa-user-circle',
            'header_bg' => 'login-theme-client',
            'btn_class' => 'btn-login-client',
            'nav_class' => 'nav-link-login-client',
        ],
        'driver' => [
            'icon' => 'fa-truck',
            'header_bg' => 'login-theme-driver',
            'btn_class' => 'btn-login-driver',
            'nav_class' => 'nav-link-login-driver',
        ],
        'employee' => [
            'icon' => 'fa-user-tie',
            'header_bg' => 'login-theme-employee',
            'btn_class' => 'btn-login-employee',
            'nav_class' => 'nav-link-login-employee',
        ],
        'admin' => [
            'icon' => 'fa-shield-halved',
            'header_bg' => 'login-theme-admin',
            'btn_class' => 'btn-login-admin',
            'nav_class' => 'nav-link-login-admin',
        ],
    ];
    $t = $themes[$theme] ?? $themes['client'];
    $loginRoutes = [
        'client' => route('client.login'),
        'driver' => route('driver.login'),
        'employee' => route('employee.login'),
        'admin' => route('admin.login'),
    ];
@endphp

<style>
    .login-page-wrapper { min-height: 60vh; display: flex; align-items: center; }
    .login-card { border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,.08); }
    .login-header { padding: 2rem 1.5rem; text-align: center; color: #fff; }
    .login-header .login-icon { font-size: 3rem; margin-bottom: 0.75rem; opacity: 0.95; }
    .login-header h1 { font-size: 1.5rem; font-weight: 600; margin: 0 0 0.25rem 0; }
    .login-header p { font-size: 0.875rem; margin: 0; opacity: 0.9; }
    .login-theme-client { background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); }
    .login-theme-driver { background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%); }
    .login-theme-employee { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); }
    .login-theme-admin { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); }
    .btn-login-client { background: #0d9488; border-color: #0d9488; }
    .btn-login-client:hover { background: #0f766e; border-color: #0f766e; color: #fff; }
    .btn-login-driver { background: #ea580c; border-color: #ea580c; }
    .btn-login-driver:hover { background: #c2410c; border-color: #c2410c; color: #fff; }
    .btn-login-employee { background: #2563eb; border-color: #2563eb; }
    .btn-login-employee:hover { background: #1d4ed8; border-color: #1d4ed8; color: #fff; }
    .btn-login-admin { background: #7c3aed; border-color: #7c3aed; }
    .btn-login-admin:hover { background: #6d28d9; border-color: #6d28d9; color: #fff; }
    .login-role-tabs { display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; padding: 1rem 0; }
    .login-role-tabs .role-pill {
        padding: 0.4rem 0.75rem; border-radius: 50px; font-size: 0.8rem; text-decoration: none;
        transition: all 0.2s; border: 2px solid transparent;
    }
    .login-role-tabs .role-pill.active { font-weight: 600; color: #fff; }
    .login-role-tabs .role-pill:not(.active) { background: #f1f5f9; color: #64748b; }
    .login-role-tabs .role-pill:not(.active):hover { background: #e2e8f0; }
    .role-pill-client.active { background: #0d9488; }
    .role-pill-driver.active { background: #ea580c; }
    .role-pill-employee.active { background: #2563eb; }
    .role-pill-admin.active { background: #7c3aed; }
    .login-form-body { padding: 2rem 1.75rem; }
    .input-with-icon { position: relative; }
    .input-with-icon .form-control { padding-inline-start: 2.75rem; }
    .input-with-icon .input-icon {
        position: absolute; top: 50%; transform: translateY(-50%);
        inset-inline-start: 1rem; color: #94a3b8; font-size: 1rem; pointer-events: none;
    }
    .input-with-icon .form-control:focus ~ .input-icon,
    .input-with-icon .form-control:focus { color: inherit; }
    .login-footer-links { font-size: 0.875rem; }
</style>

<div class="login-page-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                {{-- Role switcher: clear indication of current form --}}
                <div class="login-role-tabs">
                    <a href="{{ $loginRoutes['client'] }}" class="role-pill role-pill-client {{ $theme === 'client' ? 'active' : '' }}">
                        <i class="fas fa-user-circle me-1"></i> {{ __('messages.client_login') }}
                    </a>
                    <a href="{{ $loginRoutes['driver'] }}" class="role-pill role-pill-driver {{ $theme === 'driver' ? 'active' : '' }}">
                        <i class="fas fa-truck me-1"></i> {{ __('messages.driver_login') }}
                    </a>
                    <a href="{{ $loginRoutes['employee'] }}" class="role-pill role-pill-employee {{ $theme === 'employee' ? 'active' : '' }}">
                        <i class="fas fa-user-tie me-1"></i> {{ __('messages.employee_login') }}
                    </a>
                    <a href="{{ $loginRoutes['admin'] }}" class="role-pill role-pill-admin {{ $theme === 'admin' ? 'active' : '' }}">
                        <i class="fas fa-shield-halved me-1"></i> {{ __('messages.admin_login') }}
                    </a>
                </div>

                <div class="card login-card shadow-lg">
                    {{-- Themed header with icon + title + subtitle --}}
                    <div class="login-header {{ $t['header_bg'] }}">
                        <div class="login-icon"><i class="fas {{ $t['icon'] }}"></i></div>
                        <h1>{{ __($titleKey) }}</h1>
                        <p>{{ __($subtitleKey) }}</p>
                    </div>

                    <div class="login-form-body">
                        @if (session('success'))
                            <div class="alert alert-success d-flex align-items-center">
                                <i class="fas fa-check-circle me-2"></i>
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ $formAction }}">
                            @csrf

                            <div class="mb-3">
                                <label for="mobile" class="form-label">{{ __('messages.mobile_no') }}</label>
                                <div class="input-with-icon">
                                    <input id="mobile" type="tel" inputmode="numeric" pattern="[0-9]*"
                                        class="form-control form-control-lg @error('mobile') is-invalid @enderror"
                                        name="mobile" value="{{ old('mobile') }}" required autocomplete="mobile" autofocus
                                        placeholder="{{ __('messages.mobile_no') }}">
                                    <i class="fas fa-mobile-alt input-icon"></i>
                                    @error('mobile')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">{{ __('messages.password') }}</label>
                                <div class="input-with-icon">
                                    <input id="password" type="password"
                                        class="form-control form-control-lg @error('password') is-invalid @enderror"
                                        name="password" required autocomplete="current-password"
                                        placeholder="{{ __('messages.password') }}">
                                    <i class="fas fa-lock input-icon"></i>
                                    @error('password')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4 form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">{{ __('messages.remember_me') }}</label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-lg text-white {{ $t['btn_class'] }}">
                                    <i class="fas fa-sign-in-alt me-2"></i>{{ __('messages.login') }}
                                </button>
                            </div>

                            <div class="login-footer-links mt-4 text-center">
                                @if (Route::has('password.request'))
                                    <a class="text-muted text-decoration-none" href="{{ route('password.request') }}">
                                        {{ __('messages.forgot_your_password') }}
                                    </a>
                                @endif
                                @if (isset($showRegister) && $showRegister)
                                    <p class="mt-3 mb-0">
                                        {{ __('messages.dont_have_an_account') }}
                                        <a href="{{ route('client.register') }}" class="fw-medium">{{ __('messages.register') }}</a>
                                    </p>
                                @endif
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
