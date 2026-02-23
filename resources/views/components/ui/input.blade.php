@props([
    'type' => 'text',
    'label' => null,
    'error' => null,
    'icon' => null,
    'iconPosition' => 'left',
    'loading' => false,
    'required' => false,
    'helpText' => null
])

@php
$inputId = $attributes->get('id', 'input-' . uniqid());
$classes = 'form-control';

if ($icon && $iconPosition === 'left') {
    $classes .= ' ps-5';
}

if ($icon && $iconPosition === 'right') {
    $classes .= ' pe-5';
}

if ($error) {
    $classes .= ' is-invalid';
}
@endphp

<div class="mb-3">
    @if($label)
        <label for="{{ $inputId }}" class="form-label">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    
    <div class="position-relative">
        @if($icon && $iconPosition === 'left')
            <div class="position-absolute start-0 top-50 translate-middle-y ps-3">
                <i class="{{ $icon }} text-muted"></i>
            </div>
        @endif
        
        <input 
            type="{{ $type }}"
            id="{{ $inputId }}"
            {{ $attributes->merge(['class' => $classes]) }}
            @if($required) required @endif
        />
        
        @if($loading)
            <div class="position-absolute end-0 top-50 translate-middle-y pe-3">
                <div class="spinner-border spinner-border-sm text-muted" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        @elseif($icon && $iconPosition === 'right')
            <div class="position-absolute end-0 top-50 translate-middle-y pe-3">
                <i class="{{ $icon }} text-muted"></i>
            </div>
        @endif
    </div>
    
    @if($helpText && !$error)
        <div class="form-text">{{ $helpText }}</div>
    @endif
    
    @if($error)
        <div class="invalid-feedback d-block">
            {{ $error }}
        </div>
    @endif
</div>
