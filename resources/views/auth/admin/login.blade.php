@extends('layouts.app')

@section('content')
@include('auth.partials.login-form', [
    'theme' => 'admin',
    'titleKey' => 'messages.admin_login',
    'subtitleKey' => 'messages.admin_login_subtitle',
    'formAction' => route('admin.login.post'),
    'showRegister' => false,
])
@endsection
