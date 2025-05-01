@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{__('messages.delivery_history')}}</h1>

    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    <div class="mt-2 mb-2">
        <h3 class="d-inline">{{ __('messages.current_balance') }}</h3>
        @if($driver->balance >= 0)
        <div class="fw-bold fs-2 text-success d-inline">
            {{ $driver->balance }}
        </div>
        @else
        <div class="fw-bold fs-2 text-danger d-inline">
            {{ $driver->balance }}
        </div>
        @endif
    </div>

    {{-- Date Filtering Form --}}
    {{-- Using GET method so the filter parameters are visible in the URL --}}
    <form method="GET" action="{{ route('driver.deliveryHistory') }}" class="form-inline mb-4">
        <div class="form-group">
            <label for="start_date" class="mr-2">{{ __('messages.start_date') }}:</label>
            {{-- Use old() to repopulate the field if validation fails or after submission --}}
            {{-- Use value attribute to keep the selected date after filtering --}}
            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
        </div>
        <div class="form-group ml-3"> {{-- Use ml-3 for spacing, adjust as needed for Bootstrap 3 --}}
            <label for="end_date" class="mr-2">{{ __('messages.end_date') }}:</label>
            <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
        </div>
        <button type="submit" class="btn btn-primary ml-3">{{ __('messages.filter') }}</button> {{-- Use ml-3 for spacing --}}
        {{-- Optional: Add a reset button --}}
        @if(request('start_date') || request('end_date'))
            <a href="{{ route('driver.deliveryHistory') }}" class="btn btn-secondary ml-2">{{ __('messages.reset_filter') }}</a>
        @endif
    </form>


    {{-- Wrap the table and pagination in a div if you want to apply styles or JS based on filtering --}}
    {{-- Or just keep them as is, the form submission handles the filtering --}}
    <table class="table">
        <thead>
            <tr>
                <th>{{ __('messages.order_id') }}</th>
                <th>{{ __('messages.order_status') }}</th>
                <th>{{ __('messages.delivery_price') }}</th>
                <th>{{ __('messages.driver') }}</th>
                <th>{{ __('messages.created_at') }}</th>
                <th>{{ __('messages.order_details') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($current_orders as $order)
            <tr>
                <td>{{ $order->id }}</td>

                <td>{{ $order->statusTranslated() }}</td>
                <td>@if($order->orderDelivery)
                    {{-- Removed ul/small for simpler table cell display --}}
                    {{ $order->orderDelivery->price }}
                    @else
                    {{-- Display something or leave empty if no delivery price --}}
                    -
                    @endif
                </td>

                {{-- Added a check for orderDelivery relationship before accessing driver --}}
                <td>{{ $order->orderDelivery ? $order->orderDelivery->driver->name : '-' }}</td>
                <td>{{ $order->created_at }}</td>
                <td>
                    {{-- Ensure the route name is correct --}}
                    <a href="{{ route('orders.details', $order->id) }}" class="btn btn-info btn-sm" title="{{ __('messages.show') }}">
                        <i class="fas fa-eye"></i> {{ __('messages.order_details') }}
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">{{__('messages.no_orders_found')}}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Ensure pagination links include filter parameters --}}
    {{ $current_orders->appends(request()->query())->links() }}

    {{-- If you are using a custom pagination component, make sure it appends query parameters --}}
    {{-- <x-pagination :paginator="$current_orders" /> --}}
    {{-- If using a custom component, you might need to pass appends manually like: --}}
    {{-- <x-pagination :paginator="$current_orders" :appends="request()->query()" /> --}}


</div>
@endsection

{{-- Add any necessary CSS for form-inline if not already in your app.css --}}
{{-- This is basic Bootstrap 3 form-inline styling --}}
<style>
    .form-inline .form-group {
        display: inline-block;
        margin-bottom: 0;
        vertical-align: middle;
    }
    .form-inline .form-control {
        display: inline-block;
        width: auto;
        vertical-align: middle;
    }
    .form-inline .btn {
         vertical-align: middle;
    }
     /* Basic spacing for Bootstrap 3 */
    .ml-2 { margin-left: .5rem; }
    .ml-3 { margin-left: 1rem; }
    .mr-2 { margin-right: .5rem; }
</style>
