@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('messages.product_details') }}</div>

                <div class="card-body">
                    <p><strong>{{ __('messages.id') }}:</strong> {{ $product->id }}</p>
                    <p><strong>{{ __('messages.name') }}:</strong> {{ $product->name }}</p>
                </div>

                <a class="btn btn-primary" href="{{ route('products.index') }}">{{ __('messages.back') }} </a>
            </div>
        </div>
    </div>
</div>
@endsection