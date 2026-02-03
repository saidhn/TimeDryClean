@props([
    'type' => 'text',
    'count' => 1,
    'height' => null,
    'width' => null,
    'circle' => false
])

@php
$baseClasses = 'placeholder-glow';
$skeletonClasses = 'placeholder';

if ($circle) {
    $skeletonClasses .= ' rounded-circle';
} else {
    $skeletonClasses .= ' rounded';
}

$style = '';
if ($height) {
    $style .= "height: {$height};";
}
if ($width) {
    $style .= "width: {$width};";
}
@endphp

@if($type === 'text')
    <div class="{{ $baseClasses }}">
        @for($i = 0; $i < $count; $i++)
            <span class="{{ $skeletonClasses }} col-{{ $i === $count - 1 ? '7' : '12' }} mb-2 d-block" style="{{ $style }}"></span>
        @endfor
    </div>
@elseif($type === 'card')
    <div class="card" aria-hidden="true">
        <div class="card-body">
            <div class="{{ $baseClasses }}">
                <span class="{{ $skeletonClasses }} col-6 mb-3 d-block"></span>
                <span class="{{ $skeletonClasses }} col-12 mb-2 d-block"></span>
                <span class="{{ $skeletonClasses }} col-12 mb-2 d-block"></span>
                <span class="{{ $skeletonClasses }} col-8 d-block"></span>
            </div>
        </div>
    </div>
@elseif($type === 'avatar')
    <div class="{{ $baseClasses }}">
        <span class="{{ $skeletonClasses }} rounded-circle d-block" style="width: {{ $width ?? '48px' }}; height: {{ $height ?? '48px' }};"></span>
    </div>
@elseif($type === 'button')
    <div class="{{ $baseClasses }}">
        <span class="{{ $skeletonClasses }} btn disabled" style="{{ $style }}"></span>
    </div>
@elseif($type === 'table')
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th><span class="{{ $skeletonClasses }} col-8"></span></th>
                    <th><span class="{{ $skeletonClasses }} col-6"></span></th>
                    <th><span class="{{ $skeletonClasses }} col-7"></span></th>
                </tr>
            </thead>
            <tbody>
                @for($i = 0; $i < ($count ?? 5); $i++)
                    <tr>
                        <td><span class="{{ $skeletonClasses }} col-10"></span></td>
                        <td><span class="{{ $skeletonClasses }} col-8"></span></td>
                        <td><span class="{{ $skeletonClasses }} col-6"></span></td>
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>
@else
    <div class="{{ $baseClasses }}">
        <span class="{{ $skeletonClasses }}" style="{{ $style }}"></span>
    </div>
@endif
