@props([
    'id' => 'modal-' . uniqid(),
    'title' => '',
    'size' => 'md',
    'centered' => true,
    'scrollable' => false,
    'backdrop' => 'true',
    'keyboard' => 'true'
])

@php
$sizeClasses = [
    'sm' => 'modal-sm',
    'md' => '',
    'lg' => 'modal-lg',
    'xl' => 'modal-xl',
    'fullscreen' => 'modal-fullscreen',
];

$modalDialogClasses = 'modal-dialog ' . ($sizeClasses[$size] ?? '');

if ($centered) {
    $modalDialogClasses .= ' modal-dialog-centered';
}

if ($scrollable) {
    $modalDialogClasses .= ' modal-dialog-scrollable';
}
@endphp

<div 
    class="modal fade" 
    id="{{ $id }}" 
    tabindex="-1" 
    aria-labelledby="{{ $id }}-label" 
    aria-hidden="true"
    data-bs-backdrop="{{ $backdrop }}"
    data-bs-keyboard="{{ $keyboard }}"
>
    <div class="{{ $modalDialogClasses }} animate-scale-in">
        <div class="modal-content">
            @if($title || isset($header))
                <div class="modal-header">
                    @if(isset($header))
                        {{ $header }}
                    @else
                        <h5 class="modal-title" id="{{ $id }}-label">{{ $title }}</h5>
                    @endif
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            @endif
            
            <div class="modal-body">
                {{ $slot }}
            </div>
            
            @if(isset($footer))
                <div class="modal-footer">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
