@extends('layouts.app')

@section('content')
<h1>{{ __('messages.product_service') }}</h1>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">{{ __('messages.name') }}: {{ $productService->name }}</h5>

        <a href="{{ route('product_services.index') }}" class="btn btn-primary">{{ __('messages.back') }}</a>
    </div>
</div>
@endsection