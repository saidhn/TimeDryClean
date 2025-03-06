<!DOCTYPE html>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ get_direction() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'مصبغة تايم') }}</title>
    @if (get_direction() === 'rtl')
    @vite(['resources/css/app_rtl.css','resources/css/shared_all.css', 'resources/js/app.js']) {{-- Include RTL CSS --}}
    @else
    @vite(['resources/css/app_ltr.css','resources/css/shared_all.css', 'resources/js/app.js']) {{-- Include LTR CSS --}}
    @endif
</head>


<body>
    <header class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                <img class="logo-white" src="{{ Vite::asset('resources/images/L5.png') }}" alt="Your Logo" height="30">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto justify-content-center text-center">

                    @auth


                    @if (Auth::user()->user_type == 'client')
                    @include('client.menu')
                    @elseif (Auth::user()->user_type == 'employee')
                    @include('employee.menu')
                    @elseif (Auth::user()->user_type == 'driver')
                    @include('driver.menu')
                    @elseif (Auth::user()->user_type == 'admin')
                    @include('admin.menu')
                    @endif


                    @else


                    @guest
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('client.login') }}">{{__('messages.client_login')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('driver.login') }}">{{__('messages.driver_login')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('employee.login') }}">{{__('messages.employee_login')}}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.login') }}">{{__('messages.admin_login')}}</a>
                    </li>
                    @if (Route::has('register'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('client.register') }}">{{__('messages.client_register')}}</a>
                    </li>
                    @endif
                    @endguest


                    @if (Route::has('register'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">{{__('messages.register')}}</a>
                    </li>
                    @endif
                    @endauth

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ strtoupper(App::getLocale()) }}
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="{{ route('set_language', 'en') }}">EN</a></li>
                            <li><a class="dropdown-item" href="{{ route('set_language', 'ar') }}">AR</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <div class="container mt-5">
        @yield('content')
    </div>
    @stack('scripts')
</body>

</html>