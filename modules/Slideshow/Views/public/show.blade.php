@extends('layouts.app')

@section('title', $slideshow->title)

@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $slideshow->title }}</h1>

<div class="slideshow">
    @foreach ($slideshow->items as $item)
        @if($item->media_type === 'image')
            <img src="{{ asset('storage/' . $item->file_path) }}" class="mb-4 max-w-full">
        @elseif($item->media_type === 'video')
            <video controls class="mb-4 max-w-full">
                <source src="{{ asset('storage/' . $item->file_path) }}" type="video/mp4">
                Ваш браузер не поддерживает видео.
            </video>
        @endif
    @endforeach
</div>
@endsection
