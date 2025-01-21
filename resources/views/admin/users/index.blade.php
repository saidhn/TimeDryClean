@extends('layouts.app')

@section('content')
<div class="container">
    <h3>{{__('messages.manage_users') }}</h3>

    <div class="mt-4">
        <p>مرحبا، {{ Auth::guard('admin')->user()->name }}!</p>

        @if($users->isEmpty())
        <p>{{__("messages.no_data_to_display")}}</p>
        @else
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{__('messages.id')}}</th>
                        <th>{{__('messages.name')}}</th>
                        <th>{{__('messages.mobile')}}</th>
                        <th>{{__('messages.email')}}</th>
                        <th>{{__('messages.created_at')}}</th>
                        <th>{{__('messages.user_type')}}</th>
                        <th>{{__('messages.address')}}</th>
                        <th>{{__('messages.modify')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->mobile }}</td>
                        <td>{{ $user->email ?? '' }}</td>
                        <td>{{ $user->created_at }}</td>
                        <td>{{ $user->user_type_translated() }}</td>
                        <td>{{ $user->address_formatted() }}</td>
                        <td><a class="btn btn-secondary" href="{{route('users.edit',$user->id)}}">{{__('messages.modify')}}</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->onEachSide(2)->links('vendor.pagination.bootstrap-5') }}
        @endif


    </div>
</div>
@endsection