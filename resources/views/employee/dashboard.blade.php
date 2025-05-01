@extends('layouts.app')

@section('content')
<div class="container">
    <h3></h3>
    <div class="mt-4">
        <p>{{ __('messages.hello') }}, {{ $employee->name }}!</p>
    </div>
</div>
@endsection