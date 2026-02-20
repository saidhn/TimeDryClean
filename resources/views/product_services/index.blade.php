@extends('layouts.app')

@section('content')
<div class="container">
    <h3>{{ __('messages.product_services') }}</h3>

    @if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    <div class="toolbar mb-3">
        <a href="{{ route('product_services.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> {{ __('messages.add') }}</a>

    </div>
    {{-- Search Form --}}
    <div class="mb-3">
        <form action="{{ route('product_services.index') }}" method="GET">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="{{__('messages.search')}} {{ __('messages.product_service') }}" value="{{ request('search') }}">
                <button class="btn btn-outline-secondary" type="submit">{{ __('messages.search') }}</button>
            </div>
        </form>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>{{ __('messages.name') }}</th>
                <th>{{ __('messages.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($productServices as $productService)
            <tr>
                <td>{{ $productService->name }}</td>
                <td>
                    <a href="{{ route('product_services.show', $productService) }}" class="btn btn-info btn-sm">{{ __('messages.view') }}</a>
                    <a href="{{ route('product_services.edit', $productService) }}" class="btn btn-warning btn-sm">{{ __('messages.edit') }}</a>
                    <form action="{{ route('product_services.destroy', $productService) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('messages.confirm_deletion') }}')">{{ __('messages.delete') }}</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<x-pagination :paginator="$productServices" />

@endsection