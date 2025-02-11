@extends('layouts.app')

@section('content')
<h3>{{ __('messages.create') }} {{ __('messages.product_service') }}</h3>

@if ($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('product_services.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="name" class="form-label">{{ __('messages.name') }}</label>
        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
    </div>
    <div class="mb-3">
        <label for="price" class="form-label">{{ __('messages.price') }}</label>
        <input type="number" class="form-control text-center" id="price" name="price" value="{{ old('price') }}" step="0.01" min="0" required>
    </div>
    <button type="submit" class="btn btn-primary">{{ __('messages.create') }}</button>
    <a href="{{ route('product_services.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
</form>
@endsection