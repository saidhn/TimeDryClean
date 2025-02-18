@extends('layouts.app')

@section('content')
<div class="container">
    <div class="">
        <h3>{{ __('messages.manage_products') }}</h3>
    </div>
    <div class="mt-4">
        <div class="toolbar mb-3">
            <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> {{ __('messages.add') }}</a>
        </div>
        {{-- Search Form --}}
        <div class="mb-3">
            <form action="{{ route('products.index') }}" method="GET">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="{{ __('messages.search_product') }}" value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">{{ __('messages.search') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="">
        @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
        @endif
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('messages.id') }}</th>
                        <th>{{ __('messages.name') }}</th>
                        <th>{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                    <tr>
                        <td>{{ $product->id }}</td>
                        <td>{{ $product->name }}</td>
                        <td>
                            <a href="{{ route('products.show', $product) }}" class="btn btn-info btn-sm">{{ __('messages.show') }}</a>
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-warning btn-sm">{{ __('messages.edit') }}</a>
                            <form action="{{ route('products.destroy', $product) }}" method="POST" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{__("messages.confirm_deletion")}}')">{{ __('messages.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <x-pagination :paginator="$products" />

</div>
@endsection