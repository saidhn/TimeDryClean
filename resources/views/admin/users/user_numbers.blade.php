@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">{{ __('messages.whatsapp_message') }}</div>

                <div class="card-body">
                    <form method="GET" action="{{ route('admin.users.byNumber') }}">
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <input type="text" name="search" class="form-control" placeholder="{{ __('messages.search') }}" value="{{ request('search') }}">
                            </div>
                            <div class="col-md-3">
                                <select name="user_type" class="form-control">
                                    <option value="">{{ __('messages.all_user_types') }}</option>
                                    <option value="client" {{ request('user_type') === 'client' ? 'selected' : '' }}>{{ __('messages.client') }}</option>
                                    <option value="employee" {{ request('user_type') === 'employee' ? 'selected' : '' }}>{{ __('messages.employee') }}</option>
                                    <option value="driver" {{ request('user_type') === 'driver' ? 'selected' : '' }}>{{ __('messages.driver') }}</option>
                                    <option value="admin" {{ request('user_type') === 'admin' ? 'selected' : '' }}>{{ __('messages.admin') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input type="checkbox" name="negative_balance" value="1" id="negativeBalance" class="form-check-input" {{ request('negative_balance') == 1 ? 'checked' : '' }}>
                                    <label for="negativeBalance" class="form-check-label">{{ __('messages.negative_balance') }}</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary">{{ __('messages.filter') }}</button>
                            </div>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('admin.users.sendWhatsapp') }}">
                        @csrf
                        <div class="mb-3">
                            <textarea name="message" id="messageTextarea" class="form-control" placeholder="{{ __('messages.whatsapp_message') }}" required></textarea>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select-all"></th>
                                        <th>{{ __('messages.id') }}</th>
                                        <th>{{ __('messages.name') }}</th>
                                        <th>{{ __('messages.mobile_no') }}</th>
                                        <th>{{ __('messages.user_type') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($users as $user)
                                        <tr>
                                            <td><input type="checkbox" name="users[]" value="{{ $user->mobile }}"></td>
                                            <td>{{ $user->id }}</td>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->mobile }}</td>
                                            <td>{{ __('messages.' . $user->user_type) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5">{{ __('messages.no_users_found') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="btn btn-success">{{ __('messages.send_whatsapp') }}</button>
                        <button type="submit" name="send_filtered" value="1" class="btn btn-info">{{ __('messages.send_whatsapp_filtered') }}</button>
                        <button type="submit" name="send_negative_balance" value="1" class="btn btn-warning">{{ __('messages.send_negative_balance_message') }}</button>
                        <input type="hidden" name="filtered_users" value="{{ json_encode($filteredUsers->pluck('mobile')->toArray()) }}">
                        <input type="hidden" name="negative_balance_message" value="{{ __('messages.negative_balance_reminder') }}">
                    </form>

                    <x-pagination :paginator="$users" />
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('select-all').addEventListener('change', function() {
        let checkboxes = document.querySelectorAll('input[name="users[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    document.querySelector('button[name="send_negative_balance"]').addEventListener('click', function(event) {
        document.getElementById('messageTextarea').removeAttribute('required');
    });

    document.querySelector('button[name="send_filtered"]').addEventListener('click', function(event) {
        document.getElementById('messageTextarea').setAttribute('required','');
    });

    document.querySelector('button[type="submit"]:not([name="send_filtered"]):not([name="send_negative_balance"])').addEventListener('click', function(event) {
        document.getElementById('messageTextarea').setAttribute('required','');
    });
</script>
@endsection