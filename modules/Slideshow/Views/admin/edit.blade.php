@extends('layouts.admin')

@section('title', 'Редактирование слайдшоу')
@section('header', 'Слайды: ' . $slideshow->title)

@section('content')
    {{-- Форма добавления слайда --}}
    <form method="POST" action="{{ route('admin.slides.store') }}" enctype="multipart/form-data" class="mb-6 max-w-xl">
        @csrf
        <input type="hidden" name="slideshow_id" value="{{ $slideshow->id }}">

        <div class="mb-4">
            <label for="media" class="block mb-1 font-semibold">Файл (изображение или видео)</label>
            <input type="file" name="media" id="media" class="w-full border rounded px-3 py-2" required>
        </div>

        <div class="mb-4">
            <label for="caption" class="block mb-1 font-semibold">Подпись (необязательно)</label>
            <input type="text" name="caption" id="caption" class="w-full border rounded px-3 py-2">
        </div>

        <div class="mb-4">
            <label for="order" class="block mb-1 font-semibold">Порядок</label>
            <input type="number" name="order" id="order" value="0" class="w-full border rounded px-3 py-2">
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">➕ Добавить слайд</button>
    </form>

    {{-- Существующие слайды --}}
    @if ($slideshow->items->count())
        <h2 class="text-xl font-bold mb-2">Текущие слайды</h2>
        <div class="grid grid-cols-2 gap-4">
            @foreach ($slideshow->items->sortBy('order') as $slide)
                <div class="border rounded overflow-hidden shadow">
                    @if ($slide->media_type === 'image')
                        <img src="{{ asset('storage/' . $slide->file_path) }}" class="w-full">
                    @else
                        <video controls class="w-full">
                            <source src="{{ asset('storage/' . $slide->file_path) }}">
                        </video>
                    @endif
                    <div class="p-2 text-sm">
                        {{ $slide->caption ?? 'Без подписи' }}
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-gray-500">Нет слайдов</p>
    @endif
@endsection
