@props(['value', 'decimals' => 2, 'suffix' => ''])

@php
    $numeric = (float) $value;
    $variant = $numeric > 0 ? 'good' : ($numeric < 0 ? 'critical' : 'neutral');
@endphp

<span class="status-pill status-pill-{{ $variant }}">
    {{ number_format($numeric, $decimals) }}{{ $suffix ? ' ' . $suffix : '' }}
</span>
