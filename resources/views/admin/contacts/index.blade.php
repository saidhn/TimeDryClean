@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">{{ __('messages.contact_messages') }}</div>

                <div class="card-body">
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
                                    <th>{{ __('messages.title') }}</th>
                                    <th>{{ __('messages.message') }}</th>
                                    <th>{{ __('messages.user') }}</th>
                                    <th>{{ __('messages.date') }}</th>
                                    <th>{{ __('messages.read') }}</th>
                                    <th>{{ __('messages.replied') }}</th>
                                    <th>{{ __('messages.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($contacts as $contact)
                                <tr>
                                    <td>{{ $contact->id }}</td>
                                    <td>{{ $contact->title }}</td>
                                    <td>{{ Str::limit($contact->message, 100) }}</td>
                                    <td>{{ $contact->user ? $contact->user->name : __('messages.anonymous') }}</td>
                                    <td>{{ $contact->date->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        <span class="{{ $contact->isRead ? 'text-success' : 'text-danger' }}">
                                            {{ $contact->isRead ? __('messages.yes') : __('messages.no') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="{{ $contact->isReplied ? 'text-success' : 'text-danger' }}">
                                            {{ $contact->isReplied ? __('messages.yes') : __('messages.no') }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.contacts.show', $contact->id) }}" class="btn btn-sm btn-info">{{ __('messages.view') }}</a>
                                        <form action="{{ route('admin.contacts.markRead', $contact->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-primary">{{ $contact->isRead ? __('messages.mark_unread') : __('messages.mark_read') }}</button>
                                        </form>
                                        <form action="{{ route('admin.contacts.markReplied', $contact->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-success">{{ $contact->isReplied ? __('messages.mark_unreplied') : __('messages.mark_replied') }}</button>
                                        </form>

                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8">{{ __('messages.no_contact_messages') }}</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <x-pagination :paginator="$contacts" />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection