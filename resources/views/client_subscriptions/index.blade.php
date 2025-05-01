@extends('layouts.app')

@section('content')
<div class="container">
    <h3>{{__('messages.manage_client_subscriptions') }}</h3>

    @if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    <div class="toolbar mb-3">
        <a href="{{ route('client_subscriptions.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> {{ __('messages.add') }}</a>
    </div>
    <div class="mt-4">

        @if($clientSubscriptions->isEmpty())
        <p>{{__("messages.no_data_to_display")}}</p>
        @else

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{__('messages.id')}}</th>
                        <th>{{__('messages.name')}}</th>
                        <th>{{__('messages.mobile')}}</th>
                        <th>{{__('messages.address')}}</th>
                        <th>{{__('messages.subscription')}}</th>
                        <th>{{__('messages.date')}}</th>
                        <th>{{__('messages.modify')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clientSubscriptions as $clientSubscription)
                    <tr>
                        <td>{{ $clientSubscription->id }}</td>
                        <td>{{ optional($clientSubscription->client)->name }}</td>
                        <td>{{ optional($clientSubscription->client)->mobile }}</td>
                        <td>{{ optional($clientSubscription->client)->address_formatted() }}</td>
                        <td>{{ optional($clientSubscription->subscription)->getDetails() }}</td>
                        <td>{{ $clientSubscription->created_at }}</td>
                        <td>
                            <a class="btn btn-warning btn-sm text-white" href="{{route('client_subscriptions.edit',$clientSubscription->id)}}">
                                {{__('messages.modify')}}</a>
                            <form class="d-inline" id="user-delete-form-{{ $clientSubscription->id }}" action="{{ route('client_subscriptions.destroy', $clientSubscription->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <a class="btn btn-danger btn-sm" onclick="confirmUserDeletion({{ $clientSubscription->id }})">
                                    {{__('messages.delete')}}
                                </a>
                            </form>

                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-pagination :paginator="$clientSubscriptions" />

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