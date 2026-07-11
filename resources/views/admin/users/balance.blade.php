@extends('layouts.app')

@section('content')
<div class="container">
    <h3>{{ __('messages.users_by_balance') }}</h3>

    <div class="mt-4">
        <p>{{ __('messages.hello') }}، {{ Auth::guard('admin')->user()->name }}!</p>

        <div class="toolbar mb-3">
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
                <span class="svg-icon svg-icon-primary svg-icon-2x">
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="16px" height="16px" viewBox="0 0 24 24" version="1.1">
                        <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <polygon points="0 0 24 0 24 24 0 24" />
                            <path d="M18,8 L16,8 C15.4477153,8 15,7.55228475 15,7 C15,6.44771525 15.4477153,6 16,6 L18,6 L18,4 C18,3.44771525 18.4477153,3 19,3 C19.5522847,3 20,3.44771525 20,4 L20,6 L22,6 C22.5522847,6 23,6.44771525 23,7 C23,7.55228475 22.5522847,8 22,8 L20,8 L20,10 C20,10.5522847 19.5522847,11 19,11 C18.4477153,11 18,10.5522847 18,10 L18,8 Z M9,11 C6.790861,11 5,9.209139 5,7 C5,4.790861 6.790861,3 9,3 C11.209139,3 13,4.790861 13,7 C13,9.209139 11.209139,11 9,11 Z" fill="#ffffff" fill-rule="nonzero" opacity="0.8" />
                            <path d="M0.00065168429,20.1992055 C0.388258525,15.4265159 4.26191235,13 8.98334134,13 C13.7712164,13 17.7048837,15.2931929 17.9979143,20.2 C18.0095879,20.3954741 17.9979143,21 17.2466999,21 C13.541124,21 8.03472472,21 0.727502227,21 C0.476712155,21 -0.0204617505,20.45918 0.00065168429,20.1992055 Z" fill="#ffffff" fill-rule="nonzero" />
                        </g>
                    </svg>
                </span>
                {{ __('messages.add') }}
            </a>
        </div>

        <div class="mb-3">
            <form action="{{ route('admin.users.balance') }}" method="GET">
                <div class="row g-2 align-items-center mb-2">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="{{ __('messages.search_user') }}" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="balance_filter" class="form-select">
                            <option value="all" {{ $balanceFilter === 'all' ? 'selected' : '' }}>{{ __('messages.all_balances') }}</option>
                            <option value="positive" {{ $balanceFilter === 'positive' ? 'selected' : '' }}>{{ __('messages.positive_balance') }}</option>
                            <option value="negative" {{ $balanceFilter === 'negative' ? 'selected' : '' }}>{{ __('messages.negative_balance') }}</option>
                            <option value="zero" {{ $balanceFilter === 'zero' ? 'selected' : '' }}>{{ __('messages.zero_balance') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="points_filter" class="form-select">
                            <option value="all" {{ $pointsFilter === 'all' ? 'selected' : '' }}>{{ __('messages.all_points') }}</option>
                            <option value="positive" {{ $pointsFilter === 'positive' ? 'selected' : '' }}>{{ __('messages.positive_points') }}</option>
                            <option value="zero" {{ $pointsFilter === 'zero' ? 'selected' : '' }}>{{ __('messages.zero_points') }}</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary form-control" type="submit">{{ __('messages.filter') }}</button>
                    </div>
                    <div class="col-md-1">
                        <a href="{{ route('admin.users.balance') }}" class="btn btn-light border form-control">{{ __('messages.clear') }}</a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex align-items-center flex-wrap">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" name="user_type[]" value="admin" id="adminCheckboxBalance" {{ in_array('admin', request('user_type', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="adminCheckboxBalance">{{ __('messages.admin') }}</label>
                            </div>
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" name="user_type[]" value="employee" id="employeeCheckboxBalance" {{ in_array('employee', request('user_type', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="employeeCheckboxBalance">{{ __('messages.employee') }}</label>
                            </div>
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" name="user_type[]" value="driver" id="driverCheckboxBalance" {{ in_array('driver', request('user_type', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="driverCheckboxBalance">{{ __('messages.driver') }}</label>
                            </div>
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" name="user_type[]" value="client" id="clientCheckboxBalance" {{ in_array('client', request('user_type', [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="clientCheckboxBalance">{{ __('messages.client') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @if($users->isEmpty())
            <p>{{ __('messages.no_data_to_display') }}</p>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('messages.id') }}</th>
                            <th>{{ __('messages.name') }}</th>
                            <th>{{ __('messages.mobile') }}</th>
                            <th>{{ __('messages.email') }}</th>
                            <th>{{ __('messages.created_at') }}</th>
                            <th>{{ __('messages.user_type') }}</th>
                            <th>{{ __('messages.address') }}</th>
                            <th>{{ __('messages.balance') }}</th>
                            <th>{{ __('messages.points_balance') }}</th>
                            <th>{{ __('messages.subscription_billing_status') }}</th>
                            <th>{{ __('messages.modify') }}</th>
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
                                <td>
                                    @if($user->balance > 0)
                                        <span class="badge bg-success">{{ $user->balance }}</span>
                                    @elseif($user->balance < 0)
                                        <span class="badge bg-danger">{{ $user->balance }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $user->balance }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->points_balance > 0)
                                        <span class="badge bg-primary">{{ number_format($user->points_balance, 2) }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ number_format($user->points_balance, 2) }}</span>
                                    @endif
                                </td>
                                <td><x-subscription-status-badge :client-subscription="$user->latestClientSubscription" /></td>
                                <td>
                                    <a class="btn btn-info btn-sm text-white" href="{{ route('admin.users.show', $user->id) }}">{{ __('messages.show') }}</a>
                                    <a class="btn btn-warning btn-sm text-white" href="{{ route('admin.users.edit', $user->id) }}">{{ __('messages.modify') }}</a>
                                    <form class="d-inline" id="user-delete-form-balance-{{ $user->id }}" action="{{ route('admin.users.destroy', $user->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <a class="btn btn-danger btn-sm" onclick="confirmUserDeletionBalance({{ $user->id }})">{{ __('messages.delete') }}</a>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-pagination :paginator="$users" />
        @endif
    </div>
</div>

<script>
    function confirmUserDeletionBalance(userId) {
        var result = confirm('{{ __('messages.confirm_deletion') }}');
        if (result) {
            document.getElementById('user-delete-form-balance-' + userId).submit();
        }
    }
</script>
@endsection
