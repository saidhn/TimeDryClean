@extends('layouts.app')

@section('content')
<div class="container">
    <h3>{{__('messages.manage_my_client_subscriptions') }}</h3>

    @if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    <div class="toolbar mb-3">
        <a href="{{ route('client.clientSubscription.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> {{ __('messages.add_balance') }}</a>
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