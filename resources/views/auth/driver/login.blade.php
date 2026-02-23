@extends('layouts.app')

@section('content')
@include('auth.partials.login-form', [
    'theme' => 'driver',
    'titleKey' => 'messages.driver_login',
    'subtitleKey' => 'messages.driver_login_subtitle',
    'formAction' => route('driver.login.post'),
    'showRegister' => false,
])
@endsection
