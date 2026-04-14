@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><i class="fas fa-star me-2"></i>{{ __('messages.manage_points_packages') }}</h3>
        <a href="{{ route('points.packages.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> {{ __('messages.add') }}
        </a>
    </div>

    <div class="mb-3">
        <form action="{{ route('points.packages.index') }}" method="GET">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="{{ __('messages.search') }}" value="{{ request('search') }}">
                <button class="btn btn-outline-secondary" type="submit">{{ __('messages.search') }}</button>
            </div>
        </form>
    </div>

    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>{{ __('messages.id') }}</th>
                    <th>{{ __('messages.package_name') }}</th>
                    <th>{{ __('messages.package_price_kwd') }}</th>
                    <th>{{ __('messages.package_points') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th>{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($packages as $package)
                <tr>
                    <td>{{ $package->id }}</td>
                    <td>{{ $package->name }}</td>
                    <td>{{ number_format($package->price_kwd, 3) }} {{ __('messages.currency_symbol') }}</td>
                    <td>{{ number_format($package->points, 2) }}</td>
                    <td>
                        <span class="badge bg-{{ $package->is_active ? 'success' : 'secondary' }}">
                            {{ $package->is_active ? __('messages.active') : __('messages.inactive') }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('points.packages.edit', $package) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> {{ __('messages.edit') }}
                        </a>
                        <form action="{{ route('points.packages.destroy', $package) }}" method="POST" style="display:inline-block;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm"
                                onclick="return confirm('{{ __('messages.confirm_deletion') }}')">
                                <i class="fas fa-trash-alt"></i> {{ __('messages.delete') }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">{{ __('messages.no_data') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-pagination :paginator="$packages" />
</div>
@endsection
