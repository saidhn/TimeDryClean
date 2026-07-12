@extends('layouts.app')

@push('scripts')
<link rel="stylesheet" href="{{ Vite::asset('resources/css/pages/contact.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0 text-primary fw-bold" style="color: #464687 !important;">
                    <i class="fas fa-inbox me-2"></i>{{ __('messages.contact_messages') }}
                </h2>
                @if(Auth::user()->user_type !== 'admin')
                <a href="{{ route('contact.showForm') }}" class="btn btn-primary rounded-pill px-4">
                    <i class="fas fa-pen me-2"></i>{{ __('messages.new_message') }}
                </a>
                @endif
            </div>

            @if (session('success'))
            <div class="alert alert-success rounded-pill border-0 shadow-sm">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            </div>
            @endif

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('contact.index') }}" class="mb-4">
                        <div class="filter-pills d-flex align-items-center flex-wrap gap-2">
                            <div class="position-relative flex-grow-1" style="max-width: 300px;">
                                <i class="fas fa-search position-absolute top-50 translate-middle-y text-muted" style="left: 15px; {{ get_direction() == 'rtl' ? 'right: 15px; left: auto;' : '' }}"></i>
                                <input type="text" name="search" class="form-control rounded-pill ps-5 bg-light border-0" placeholder="{{ __('messages.search') }}" value="{{ request('search') }}" style="{{ get_direction() == 'rtl' ? 'padding-right: 2.5rem !important; padding-left: 1rem !important;' : 'padding-left: 2.5rem !important;' }}">
                            </div>
                            
                            <select name="is_read" class="form-select rounded-pill w-auto bg-light border-0">
                                <option value="">{{ __('messages.all_read_status') }}</option>
                                <option value="1" {{ request('is_read') === '1' ? 'selected' : '' }}>{{ __('messages.read') }}</option>
                                <option value="0" {{ request('is_read') === '0' ? 'selected' : '' }}>{{ __('messages.unread') }}</option>
                            </select>

                            <select name="is_replied" class="form-select rounded-pill w-auto bg-light border-0">
                                <option value="">{{ __('messages.all_reply_status') }}</option>
                                <option value="1" {{ request('is_replied') === '1' ? 'selected' : '' }}>{{ __('messages.replied') }}</option>
                                <option value="0" {{ request('is_replied') === '0' ? 'selected' : '' }}>{{ __('messages.unreplied') }}</option>
                            </select>

                            <button type="submit" class="btn btn-primary rounded-pill px-4">{{ __('messages.filter') }}</button>
                        </div>
                    </form>

                    <div class="contact-inbox-wrap">
                        @forelse ($contacts as $contact)
                        <a href="{{ route('contact.show', $contact->id) }}" class="contact-card {{ !$contact->isRead ? 'unread' : '' }}">
                            <div class="contact-avatar shadow-sm">
                                @if($contact->user)
                                    {{ mb_substr($contact->user->name, 0, 1) }}
                                @else
                                    <i class="fas fa-user-secret"></i>
                                @endif
                            </div>
                            <div class="contact-body">
                                <div class="contact-header">
                                    <h3 class="contact-title">{{ $contact->title }}</h3>
                                    <span class="contact-date">{{ $contact->date->diffForHumans() }}</span>
                                </div>
                                <div class="contact-preview">
                                    {{ Str::limit($contact->message, 80) }}
                                </div>
                                <div class="contact-badges">
                                    @if($contact->isReplied)
                                        <span class="status-pill status-pill-good"><i class="fas fa-reply me-1"></i>{{ __('messages.replied') }}</span>
                                    @endif
                                    @if(!$contact->isRead)
                                        <span class="status-pill status-pill-warning"><i class="fas fa-envelope me-1"></i>{{ __('messages.new_message') }}</span>
                                    @endif
                                </div>
                            </div>
                            
                            @if(Auth::user()->user_type === 'admin')
                            <div class="ms-auto" style="z-index: 2;" onclick="event.preventDefault();">
                                <form action="{{ route('contact.destroy', $contact->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle p-2" title="{{ __('messages.delete') }}" onclick="return confirm('{{ __('messages.confirm_deletion') }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                            @endif
                        </a>
                        @empty
                        <div class="empty-state">
                            <i class="fas fa-inbox empty-icon"></i>
                            <h4 class="fw-bold text-dark mb-2">{{ __('messages.no_contact_messages') }}</h4>
                            @if(Auth::user()->user_type !== 'admin')
                            <p class="text-muted mb-4">{{ __('messages.chat_with_support') }}</p>
                            <a href="{{ route('contact.showForm') }}" class="btn btn-primary rounded-pill px-4">
                                <i class="fas fa-paper-plane me-2"></i>{{ __('messages.send_message') }}
                            </a>
                            @endif
                        </div>
                        @endforelse
                    </div>

                    <div class="mt-4">
                        <x-pagination :paginator="$contacts" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection