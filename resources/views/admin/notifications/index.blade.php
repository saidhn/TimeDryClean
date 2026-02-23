@extends('layouts.app')

@section('content')
<div class="container">
    <h3>{{ __('messages.notification_templates') }}</h3>
    <p class="text-muted">{{ __('messages.notification_templates_help') }}</p>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.notifications.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-body">
                @foreach ($templates as $template)
                    <div class="mb-4 p-3 border rounded">
                        <input type="hidden" name="templates[{{ $loop->index }}][id]" value="{{ $template->id }}">
                        <h6 class="text-muted">{{ $template->key }}</h6>
                        @if ($template->description)
                            <p class="small text-muted">{{ $template->description }}</p>
                        @endif
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">{{ __('messages.message_arabic') }}</label>
                                <textarea name="templates[{{ $loop->index }}][message_ar]" class="form-control" rows="2">{{ $template->message_ar }}</textarea>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">{{ __('messages.message_english') }}</label>
                                <textarea name="templates[{{ $loop->index }}][message_en]" class="form-control" rows="2">{{ $template->message_en }}</textarea>
                            </div>
                        </div>
                        <small class="text-muted">Placeholders: :balance, :amount</small>
                    </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> {{ __('messages.save') }}
        </button>
    </form>
</div>
@endsection
