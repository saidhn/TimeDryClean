@extends('layouts.app')

@section('content')
<div class="container">
    <div class="">
        <h3>{{ __('messages.manage_subscriptions') }}</h3> {{-- Adapt the message key --}}
    </div>
    <div class="mt-4">
        <div class="toolbar mb-3">
            <a href="{{ route('subscriptions.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> {{ __('messages.add') }} </a>
        </div>

        <div class="mb-3">
            <form action="{{ route('subscriptions.index') }}" method="GET">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="{{ __('messages.search') }}" value="{{ request('search') }}">
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
                        <th>{{ __('messages.paid') }}</th> {{-- Add other relevant columns --}}
                        <th>{{ __('messages.benefit') }}</th>
                        <th>{{ __('messages.start_date') }}</th>
                        <th>{{ __('messages.end_date') }}</th>
                        <th>{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($subscriptions as $subscription)
                    <tr>
                        <td>{{ $subscription->id }}</td>
                        <td>{{ $subscription->paid }}</td>
                        <td>{{ $subscription->benefit }}</td>
                        <td>{{ $subscription->start_date }}</td>
                        <td>{{ $subscription->end_date }}</td>
                        <td>
                            <a href="{{ route('subscriptions.show', $subscription) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-eye"></i> {{ __('messages.show') }}
                            </a>
                            <a href="{{ route('subscriptions.edit', $subscription) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> {{ __('messages.edit') }}
                            </a>
                            <form action="{{ route('subscriptions.destroy', $subscription) }}" method="POST" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{__("messages.confirm_deletion")}}')">
                                    <i class="fas fa-trash-alt"></i> {{ __('messages.delete') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <x-pagination :paginator="$subscriptions" /> {{-- Make sure you have the pagination component --}}

</div>
@endsection