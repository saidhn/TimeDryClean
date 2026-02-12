@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('messages.edit') }} {{ __('messages.product') }}</div>

                <div class="card-body">
                    @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                    @endif

                    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="name" class="mb-2">{{ __('messages.name') }}</label>
                            <input id="name" type="text" class="form-control mb-3 @error('name') is-invalid @enderror" name="name" value="{{ $product->name }}" required autocomplete="name" autofocus>

                            @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="image" class="mb-1">{{ __('messages.image') }}</label>
                            @if ($product->image_path)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                            <div class="mb-2">
                                <button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById('delete-image-form').submit();">
                                    <i class="fas fa-trash"></i> {{ __('messages.delete_image') }}
                                </button>
                            </div>
                            @endif
                            <input id="image" type="file" class="form-control @error('image') is-invalid @enderror" name="image" accept="image/*">

                            @error('image')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            {{ __('messages.update') }}
                        </button>
                    </form>

                    @if ($product->image_path)
                    <form id="delete-image-form" action="{{ route('products.image.destroy', $product) }}" method="POST" style="display: none;">
                        @csrf
                        @method('DELETE')
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection