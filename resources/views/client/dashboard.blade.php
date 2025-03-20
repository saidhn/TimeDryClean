@extends('layouts.app')

@section('content')
<div class="container">
    <h3></h3>



    <div class="mt-4">
        {{-- Content specific to the dashboard goes here --}}
        <p>{{ __('messages.hello') }}, {{ Auth::guard('client')->user()->name }}!</p>
        {{-- Add more dashboard content as needed --}}
    </div>
</div>
@endsection