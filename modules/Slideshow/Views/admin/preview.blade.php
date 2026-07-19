@extends('layouts.admin')

@section('title', 'Предпросмотр слайдшоу')
@section('header', '👁️ Предпросмотр: ' . $slideshow->title)

@section('content')
<div class="mb-4">
  <a href="{{ route('admin.slideshow.edit', $slideshow->id) }}"
     class="inline-flex items-center gap-2 px-3 py-2 rounded-md border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
    <i class="fa-regular fa-arrow-left"></i> Вернуться к редактированию
  </a>
</div>

<div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
  <p class="text-sm text-yellow-800 dark:text-yellow-200">
    <i class="fa-solid fa-info-circle"></i> Это предпросмотр того, как слайдшоу будет выглядеть на сайте.
    @if(!$slideshow->published)
      <strong>Слайдшоу не опубликовано</strong> и не будет видно посетителям.
    @endif
  </p>
</div>

{{-- Используем публичный шаблон для предпросмотра --}}
@include('Slideshow::public.slideshow', ['slideshow' => $slideshow])

@endsection




