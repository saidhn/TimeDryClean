@extends('layouts.app')

@section('content')
@include('auth.partials.login-form', [
    'theme' => 'employee',
    'titleKey' => 'messages.employee_login',
    'subtitleKey' => 'messages.employee_login_subtitle',
    'formAction' => route('employee.login.post'),
    'showRegister' => false,
])
@endsection
