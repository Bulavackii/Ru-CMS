@props([
    'src',
    'alt' => '',
    'class' => '',
    'width' => null,
    'height' => null,
    'lazy' => true,
    'placeholder' => null,
])

@php
    $lazyAttr = $lazy ? 'loading="lazy"' : '';
    $placeholderSrc = $placeholder ?? 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1 1\'%3E%3C/svg%3E';
@endphp

<img 
    src="{{ $lazy ? $placeholderSrc : $src }}"
    data-src="{{ $src }}"
    alt="{{ $alt }}"
    class="{{ $class }}"
    @if($width) width="{{ $width }}" @endif
    @if($height) height="{{ $height }}" @endif
    {{ $lazyAttr }}
    @if($lazy) onload="this.src=this.dataset.src" @endif
    decoding="async"
>

