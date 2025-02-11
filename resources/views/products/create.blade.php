@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('messages.create_product') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('products.store') }}">
                        @csrf

                        <div class="form-group">
                            <label for="name" class="mb-1">{{ __('messages.name') }}</label>
                            <input id="name" type="text" class="form-control mb-2 @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                            @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            {{ __('messages.create') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection