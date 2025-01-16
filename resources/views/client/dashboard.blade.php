@extends('layouts.app')

@section('content')
<div class="container">
    <h3>واجهة الزبائن</h3>



    <div class="mt-4">
        {{-- Content specific to the dashboard goes here --}}
        <p>مرحبا، {{ Auth::guard('client')->user()->name }}!</p>
        {{-- Add more dashboard content as needed --}}
    </div>
</div>
@endsection