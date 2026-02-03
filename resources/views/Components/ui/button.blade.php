@props([
    'variant' => 'primary',
    'size' => 'md',
    'loading' => false,
    'icon' => null,
    'iconPosition' => 'left',
    'type' => 'button'
])

@php
$variantClasses = [
    'primary' => 'btn-primary',
    'secondary' => 'btn-secondary',
    'success' => 'btn-success',
    'danger' => 'btn-danger',
    'warning' => 'btn-warning',
    'info' => 'btn-info',
    'light' => 'btn-light',
    'dark' => 'btn-dark',
    'outline-primary' => 'btn-outline-primary',
    'outline-secondary' => 'btn-outline-secondary',
    'link' => 'btn-link',
];

$sizeClasses = [
    'sm' => 'btn-sm',
    'md' => '',
    'lg' => 'btn-lg',
];

$classes = 'btn ' . ($variantClasses[$variant] ?? 'btn-primary') . ' ' . ($sizeClasses[$size] ?? '');
$classes .= ' d-inline-flex align-items-center gap-2 transition-all';

if ($loading) {
    $classes .= ' disabled';
}
@endphp

<button 
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $classes]) }}
    @if($loading) disabled @endif
>
    @if($loading)
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
    @elseif($icon && $iconPosition === 'left')
        <i class="{{ $icon }}"></i>
    @endif
    
    <span>{{ $slot }}</span>
    
    @if($icon && $iconPosition === 'right' && !$loading)
        <i class="{{ $icon }}"></i>
    @endif
</button>
