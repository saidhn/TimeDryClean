@props([
    'striped' => false,
    'hover' => true,
    'bordered' => false,
    'borderless' => false,
    'small' => false,
    'responsive' => true
])

@php
$classes = 'table';

if ($striped) $classes .= ' table-striped';
if ($hover) $classes .= ' table-hover';
if ($bordered) $classes .= ' table-bordered';
if ($borderless) $classes .= ' table-borderless';
if ($small) $classes .= ' table-sm';

$classes .= ' align-middle';
@endphp

@if($responsive)
    <div class="table-responsive">
        <table {{ $attributes->merge(['class' => $classes]) }}>
            {{ $slot }}
        </table>
    </div>
@else
    <table {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </table>
@endif
