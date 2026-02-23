@props([
    'icon' => 'fas fa-inbox',
    'title' => 'No data found',
    'message' => '',
    'actionText' => null,
    'actionUrl' => null
])

<div class="text-center py-5">
    <div class="mb-4">
        <i class="{{ $icon }} text-muted" style="font-size: 4rem;"></i>
    </div>
    
    <h5 class="text-muted mb-2">{{ $title }}</h5>
    
    @if($message)
        <p class="text-muted mb-4">{{ $message }}</p>
    @endif
    
    @if($actionText && $actionUrl)
        <a href="{{ $actionUrl }}" class="btn btn-primary">
            {{ $actionText }}
        </a>
    @endif
    
    {{ $slot }}
</div>
