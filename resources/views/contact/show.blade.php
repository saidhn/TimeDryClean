@extends('layouts.app')

@push('scripts')
<link rel="stylesheet" href="{{ Vite::asset('resources/css/pages/contact.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Header Actions -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="{{ route('contact.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="fas fa-arrow-{{ get_direction() == 'rtl' ? 'right' : 'left' }} me-2"></i>{{ __('messages.back') }}
                </a>
                
                @if(Auth::user()->user_type === 'admin')
                <div class="d-flex gap-2">
                    <form action="{{ route('contact.markRead', $contact->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-{{ $contact->isRead ? 'secondary' : 'primary' }} rounded-pill">
                            <i class="fas fa-envelope{{ $contact->isRead ? '' : '-open' }} me-1"></i>
                            {{ $contact->isRead ? __('messages.mark_unread') : __('messages.mark_read') }}
                        </button>
                    </form>
                    <form action="{{ route('contact.destroy', $contact->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger rounded-pill" onclick="return confirm('{{ __('messages.confirm_deletion') }}')">
                            <i class="fas fa-trash me-1"></i>{{ __('messages.delete') }}
                        </button>
                    </form>
                </div>
                @endif
            </div>

            @if (session('success'))
            <div class="alert alert-success rounded-pill border-0 shadow-sm mb-4">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            </div>
            @endif

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <!-- Chat Header -->
                <div class="bg-light p-4 border-bottom d-flex align-items-center">
                    <div class="contact-avatar shadow-sm me-3 bg-white" style="{{ get_direction() == 'rtl' ? 'margin-right: 0 !important; margin-left: 1rem !important;' : '' }}">
                        <i class="fas fa-hashtag" style="color: #464687;"></i>
                    </div>
                    <div>
                        <h4 class="mb-1 fw-bold text-dark">{{ $contact->title }}</h4>
                        <div class="text-muted small">
                            <i class="fas fa-clock me-1"></i>{{ $contact->date->format('Y-m-d h:i A') }}
                            <span class="mx-2">•</span>
                            <span class="status-pill {{ $contact->isRead ? 'status-pill-neutral' : 'status-pill-warning' }}">
                                {{ $contact->isRead ? __('messages.read') : __('messages.unread') }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Chat Messages Area -->
                <div class="card-body p-4 bg-white chat-container">
                    
                    <!-- Original Message (User) -->
                    <div class="chat-message is-user">
                        <div class="chat-avatar shadow-sm">
                            @if($contact->user)
                                {{ mb_substr($contact->user->name, 0, 1) }}
                            @else
                                <i class="fas fa-user-secret"></i>
                            @endif
                        </div>
                        <div class="chat-bubble-wrap">
                            <div class="chat-meta mb-1">
                                <span class="fw-bold">{{ $contact->user ? $contact->user->name : __('messages.anonymous') }}</span>
                            </div>
                            <div class="chat-bubble">{{ $contact->message }}</div>
                            <div class="chat-meta mt-1">
                                {{ $contact->date->diffForHumans() }}
                            </div>
                        </div>
                    </div>

                    <!-- Replies (Admin) -->
                    @if ($contact->replies && count($contact->replies) > 0)
                        @foreach ($contact->replies as $reply)
                        <div class="chat-message is-admin">
                            <div class="chat-avatar shadow-sm">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div class="chat-bubble-wrap">
                                <div class="chat-meta mb-1">
                                    <span class="fw-bold">{{ __('messages.admin_reply') }}</span>
                                </div>
                                <div class="chat-bubble">{{ $reply['message'] }}</div>
                                <div class="chat-meta mt-1">
                                    {{ \Carbon\Carbon::parse($reply['created_at'])->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @endif

                </div>
            </div>

            <!-- Reply Box (Only for Admin, or allowed users) -->
            @if(Auth::user()->user_type === 'admin')
                @if ($contact->user)
                <div class="reply-bar">
                    <form method="POST" action="{{ route('contact.reply', $contact->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="d-flex gap-3 align-items-start">
                            <div class="chat-avatar shadow-sm" style="background: rgba(70, 70, 135, 0.1); color: #464687;">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div class="flex-grow-1">
                                <textarea name="reply_message" class="form-control mb-2" rows="3" placeholder="{{ __('messages.type_message_here') }}" required></textarea>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                                        {{ __('messages.send_reply') }} <i class="fas fa-paper-plane ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                @else
                <div class="alert alert-secondary rounded-pill border-0 text-center">
                    <i class="fas fa-info-circle me-2"></i>{{ __('messages.reply_disabled_anonymous') }}
                </div>
                @endif
            @endif

        </div>
    </div>
</div>
@endsection