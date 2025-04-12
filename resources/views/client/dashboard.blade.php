@extends('layouts.app')

@section('content')
<div class="container">
    <h3></h3>
    <div class="mt-4">
        <p>{{ __('messages.hello') }}, {{ $client->name }}!</p>
        {{-- Add more dashboard content as needed --}}
        <h3>{{ __('messages.current_balance') }}:</h3>
        @if($client->balance >= 0)
        <div class="fw-bold fs-2 text-success">
            {{ $client->balance }}
        </div>
        @else
        <div class="fw-bold fs-2 text-danger">
            {{ $client->balance }}
        </div>
        @endif
    </div>

    @if ($current_orders->isEmpty())
    <p>{{ __("messages.no_data_to_display") }}</p>
    @else
    <div class="table-responsive">
        <h2>{{__('messages.current_orders')}}</h2>
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
                @foreach ($current_orders as $order)
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

    <x-pagination :paginator="$current_orders" />

    @endif
</div>
@endsection