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

        @if ($orders->isEmpty())
        <p>{{ __("messages.no_data_to_display") }}</p>
        @else
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('messages.id') }}</th>
                        <th>{{ __('messages.user') }}</th>
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
                        <td>{{ $order->created_at->format('Y-m-d') }}</td>
                        <td>{{ $order->status_translated() }}</td>
                        <td>{{ $order->sum_price }}</td>
                        <td>
                            <a class="btn btn-secondary btn-sm" href="{{ route('orders.show', $order->id) }}">
                                <span class="svg-icon svg-icon-primary svg-icon-2x"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="16px" height="16px" viewBox="0 0 24 24" version="1.1">
                                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                            <rect x="0" y="0" width="24" height="24" />
                                            <path d="M8,17.9148182 L8,5.96685884 C8,5.56391781 8.16211443,5.17792052 8.44982609,4.89581508 L10.965708,2.42895648 C11.5426798,1.86322723 12.4640974,1.85620921 13.0496196,2.41308426 L15.5337377,4.77566479 C15.8314604,5.0588212 16,5.45170806 16,5.86258077 L16,17.9148182 C16,18.7432453 15.3284271,19.4148182 14.5,19.4148182 L9.5,19.4148182 C8.67157288,19.4148182 8,18.7432453 8,17.9148182 Z" fill="#ffffff" fill-rule="nonzero" transform="translate(12.000000, 10.707409) rotate(-135.000000) translate(-12.000000, -10.707409) " />
                                            <rect fill="#ffffff" opacity="0.8" x="5" y="20" width="15" height="2" rx="1" />
                                        </g>
                                    </svg></span>
                                {{ __('messages.modify') }}
                            </a>
                            <form class="d-inline" id="user-delete-form-{{ $order->id }}" action="{{ route('orders.destroy', $order->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <a class="btn btn-danger btn-sm" onclick="confirmUserDeletion({{ $order->id }})">
                                    <span class="svg-icon svg-icon-primary svg-icon-2x"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="16px" height="16px" viewBox="0 0 24 24" version="1.1">
                                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                <polygon points="0 0 24 0 24 24 0 24" />
                                                <path d="M9,11 C6.790861,11 5,9.209139 5,7 C5,4.790861 6.790861,3 9,3 C11.209139,3 13,4.790861 13,7 C13,9.209139 11.209139,11 9,11 Z M21,8 L17,8 C16.4477153,8 16,7.55228475 16,7 C16,6.44771525 16.4477153,6 17,6 L21,6 C21.5522847,6 22,6.44771525 22,7 C22,7.55228475 21.5522847,8 21,8 Z" fill="#ffffff" fill-rule="nonzero" opacity="0.8" />
                                                <path d="M0.00065168429,20.1992055 C0.388258525,15.4265159 4.26191235,13 8.98334134,13 C13.7712164,13 17.7048837,15.2931929 17.9979143,20.2 C18.0095879,20.3954741 17.9979143,21 17.2466999,21 C13.541124,21 8.03472472,21 0.727502227,21 C0.476712155,21 -0.0204617505,20.45918 0.00065168429,20.1992055 Z" fill="#ffffff" fill-rule="nonzero" />
                                            </g>
                                        </svg></span>
                                    {{ __('messages.delete') }}
                                </a>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination-container">
            @if ($orders->onFirstPage())
            <span class="disabled">{{__('pagination.previous')}}</span>
            @else
            <a href="{{ $users->previousPageUrl() }}" class="pagination-link">{{__('pagination.previous')}}</a>
            @endif

            @for ($i = 1; $i <= $users->lastPage(); $i++)
                @if ($i == $users->currentPage())
                <span class="current">{{ $i }}</span>
                @else
                <a href="{{ $users->url($i) }}" class="pagination-link">{{ $i }}</a>
                @endif
                @endfor

                @if ($users->hasMorePages())
                <a href="{{ $users->nextPageUrl() }}" class="pagination-link">{{__('pagination.next')}}</a>
                @else
                <span class="disabled">{{__('pagination.next')}}</span>
                @endif
        </div>
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