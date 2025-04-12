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
                    <form method="GET" action="{{ route('contact.index') }}">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="{{ __('messages.search') }}" value="{{ request('search') }}">
                            </div>
                            <div class="col-md-3">
                                <select name="is_read" class="form-control">
                                    <option value="">{{ __('messages.all_read_status') }}</option>
                                    <option value="1" {{ request('is_read') === '1' ? 'selected' : '' }}>{{ __('messages.read') }}</option>
                                    <option value="0" {{ request('is_read') === '0' ? 'selected' : '' }}>{{ __('messages.unread') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="is_replied" class="form-control">
                                    <option value="">{{ __('messages.all_reply_status') }}</option>
                                    <option value="1" {{ request('is_replied') === '1' ? 'selected' : '' }}>{{ __('messages.replied') }}</option>
                                    <option value="0" {{ request('is_replied') === '0' ? 'selected' : '' }}>{{ __('messages.unreplied') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">{{ __('messages.filter') }}</button>
                            </div>
                        </div>
                    </form>
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
                                    <td><a href="{{ route('contact.show', $contact->id) }}">{{ $contact->id }}</a></td>
                                    <td><a href="{{ route('contact.show', $contact->id) }}">{{ $contact->title }}</a></td>
                                    <td><a href="{{ route('contact.show', $contact->id) }}">{{ Str::limit($contact->message, 100) }}</a></td>
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
                                        <a href="{{ route('contact.show', $contact->id) }}" class="btn btn-sm btn-info">{{ __('messages.view') }}</a>
                                        <form action="{{ route('contact.markRead', $contact->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-primary">{{ $contact->isRead ? __('messages.mark_unread') : __('messages.mark_read') }}</button>
                                        </form>
                                        <form action="{{ route('contact.markReplied', $contact->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-success">{{ $contact->isReplied ? __('messages.mark_unreplied') : __('messages.mark_replied') }}</button>
                                        </form>
                                        <form action="{{ route('contact.destroy', $contact->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('messages.confirm_deletion') }}')">{{ __('messages.delete') }}</button>
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