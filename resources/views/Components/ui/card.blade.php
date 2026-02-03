@props([
    'variant' => 'default',
    'padding' => 'normal',
    'hover' => true
])

@php
$variantClasses = [
    'default' => 'border-0 shadow-sm',
    'bordered' => 'border',
    'elevated' => 'border-0 shadow',
    'flat' => 'border-0',
];

$paddingClasses = [
    'none' => '',
    'sm' => 'p-3',
    'normal' => 'p-4',
    'lg' => 'p-5',
];

$classes = 'card ' . ($variantClasses[$variant] ?? 'border-0 shadow-sm');

if ($hover) {
    $classes .= ' hover-lift';
}
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if(isset($header))
        <div class="card-header bg-transparent border-bottom">
            {{ $header }}
        </div>
    @endif
    
    <div class="card-body {{ $paddingClasses[$padding] ?? 'p-4' }}">
        {{ $slot }}
    </div>
    
    @if(isset($footer))
        <div class="card-footer bg-transparent border-top">
            {{ $footer }}
        </div>
    @endif
</div>
