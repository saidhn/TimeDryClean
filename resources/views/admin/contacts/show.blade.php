@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">{{ __('messages.contact_message_details') }}</div>

                <div class="card-body">
                    @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                    @endif

                    <div class="mb-4 border p-3 rounded">
                        <h2>{{ $contact->title }}</h2>
                    </div>

                    <div class="mb-4 border p-3 rounded">
                        <p class="mt-2" style="white-space: pre-wrap;">{{ $contact->message }}</p>
                    </div>
                    <div class="d-flex justify-content-around">
                        <div class="mb-3">
                            <strong>{{ __('messages.user') }}:</strong> {{ $contact->user ? $contact->user->name : __('messages.anonymous') }}
                        </div>

                        <div class="mb-3">
                            <strong>{{ __('messages.date') }}:</strong> {{ $contact->date->format('Y-m-d H:i:s') }}
                        </div>

                        <div class="mb-3">
                            <strong>{{ __('messages.read') }}:</strong>
                            <span class="{{ $contact->isRead ? 'text-success' : 'text-danger' }}">
                                {{ $contact->isRead ? __('messages.yes') : __('messages.no') }}
                            </span>
                        </div>

                        <div class="mb-4">
                            <strong>{{ __('messages.replied') }}:</strong>
                            <span class="{{ $contact->isReplied ? 'text-success' : 'text-danger' }}">
                                {{ $contact->isReplied ? __('messages.yes') : __('messages.no') }}
                            </span>
                        </div>
                    </div>
                    {{-- Reply Section --}}
                    <div class="mb-4 border p-3 rounded">
                        <strong>{{ __('messages.reply') }}:</strong>
                        <form method="POST" action="{{ route('admin.contacts.reply', $contact->id) }}">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <textarea name="reply_message" class="form-control" rows="4" placeholder="{{ __('messages.enter_reply') }}" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('messages.send_reply') }}</button>
                        </form>
                    </div>

                    {{-- Previous Replies --}}
                    @if ($contact->replies)
                    <div class="mb-4 border p-3 rounded">
                        <strong>{{ __('messages.previous_replies') }}:</strong>
                        @foreach ($contact->replies as $reply)
                        <div class="mt-2 border p-2 rounded" style="background-color: #f8f9fa;">
                            <p style="white-space: pre-wrap;">{{ $reply['message'] }}</p>
                            <small>{{ \Carbon\Carbon::parse($reply['created_at'])->format('Y-m-d H:i:s') }}</small>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <div class="mt-4 d-flex justify-content-between">
                        <div>
                            <a href="{{ route('admin.contacts.index') }}" class="btn btn-secondary">{{ __('messages.back') }}</a>
                        </div>
                        <div>
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection