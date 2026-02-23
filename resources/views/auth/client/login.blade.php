@extends('layouts.app')

@section('content')
@include('auth.partials.login-form', [
    'theme' => 'client',
    'titleKey' => 'messages.client_login',
    'subtitleKey' => 'messages.client_login_subtitle',
    'formAction' => route('client.login.post'),
    'showRegister' => true,
])
@endsection
