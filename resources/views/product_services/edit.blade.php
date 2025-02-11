@extends('layouts.app')

@section('content')
<h1>{{ __('messages.edit') }} {{__('messages.product_service')}}</h1>

@if ($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('product_services.update', $productService) }}" method="POST">
    @csrf
    @method('PUT') {{-- Important for updates --}}
    <div class="mb-3">
        <label for="name" class="form-label">{{ __('messages.name') }}</label>
        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $productService->name) }}" required>
    </div>
    <div class="mb-3">
        <label for="price" class="form-label">{{ __('messages.price') }}</label>
        <input type="number" class="form-control text-center" id="price" name="price" value="{{ old('price', $productService->price) }}" step="0.01" min="0" required>
    </div>
    <button type="submit" class="btn btn-primary">{{ __('messages.update') }}</button>
    <a href="{{ route('product_services.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
</form>
@endsection