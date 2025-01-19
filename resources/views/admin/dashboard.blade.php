@extends('layouts.app')

@section('content')
<div class="container">
    <h3>{{ __('messages.admin_dashboard') }}</h3>



    <div class="mt-4">
        {{-- Content specific to the dashboard goes here --}}
        <p>مرحبا، {{ Auth::guard('admin')->user()->name }}!</p>
        {{-- Add more dashboard content as needed --}}
    </div>
</div>
@endsection