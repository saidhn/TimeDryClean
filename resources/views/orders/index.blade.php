@extends('layouts.app')

@section('content')
<div class="container">
    <h3>{{ __('messages.manage_orders') }}</h3>
    <div class="mt-4">
        <div class="toolbar mb-3">
            <a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> {{ __('messages.add') }}</a>
            </a>
        </div>


        {{-- Search and Date Filter Form --}}
        <div class="mb-3">
            <form action="{{ route('orders.index') }}" method="GET">
                <div class="input-group">
                    {{-- Existing search input --}}
                    <input type="text" name="search" class="form-control" placeholder="{{ __('messages.search_order') }}" value="{{ request('search') }}">

                    {{-- New Start Date input --}}
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}" title="{{ __('messages.start_date') }}">

                    {{-- New End Date input --}}
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}" title="{{ __('messages.end_date') }}">

                    <button class="btn btn-outline-secondary" type="submit">{{ __('messages.search') }}</button>
                </div>
            </form>
        </div>

        @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
        @endif

        @if ($orders->isEmpty())
        <p>{{ __("messages.no_data_to_display") }}</p>
        @else
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('messages.id') }}</th>
                        <th>{{ __('messages.user') }}</th>
                        <th>{{ __('messages.driver') }}</th>
                        <th>{{ __('messages.date') }}</th>
                        <th>{{ __('messages.status') }}</th>
                        <th>{{ __('messages.total_price') }}</th>
                        <th>{{ __('messages.modify') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                    <tr>
                        <td>{{ $order->id }}</td>
                        <td>{{ $order->user->name }}</td>
                        <td>{{ optional(optional($order->orderDelivery)->driver)->name }}</td>
                        <td>{{ $order->created_at->format('Y-m-d') }}</td>
                        <td>{{ $order->statusTranslated() }}</td>
                        <td>{{ $order->sum_price }}</td>
                        <td>
                            <a href="{{ route('orders.show', $order->id) }}" class="btn btn-info btn-sm" title="{{ __('messages.show') }}">
                                <i class="fas fa-eye"></i> {{-- View Icon --}}
                            </a>
                            @if(Auth::guard('admin')->check() || Auth::guard('employee')->check() || Auth::guard('driver')->check())

                            <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-warning btn-sm" title="{{ __('messages.edit') }}">
                                <i class="fas fa-edit"></i> {{-- Edit Icon --}}
                            </a>
                            <form class="d-inline" id="order-delete-form-{{ $order->id }}" action="{{ route('orders.destroy', $order->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __("messages.confirm_deletion") }}')" title="{{ __('messages.delete') }}">
                                    <i class="fas fa-trash-alt"></i> {{-- Delete Icon --}}
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <x-pagination :paginator="$orders" />

        @endif
    </div>
</div>
<script>
    function confirmUserDeletion(userId) {
        var result = confirm('{{__("messages.confirm_deletion")}}');
        if (result) {
            // If confirmed, submit the form with the DELETE method
            document.getElementById('user-delete-form-' + userId).submit();
        }
    }
</script>
@endsection
