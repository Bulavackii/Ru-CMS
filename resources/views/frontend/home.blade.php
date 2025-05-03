@extends('layouts.frontend')

@section('title', 'Главная')

@section('content')
    <h1 class="text-3xl font-bold mb-6 text-center">Новости</h1>

    {{-- Фильтр по категориям --}}
    <form method="GET" class="mb-6 flex flex-wrap gap-4 justify-center items-center">
        <select name="category" class="border px-3 py-2 rounded">
            <option value="">Все категории</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->title }}
                </option>
            @endforeach
        </select>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
            Фильтровать
        </button>
    </form>

    {{-- Список новостей --}}
    @if ($newsList->count() > 0 && $newsList->count() < 4)
        <div class="flex justify-center flex-wrap gap-6">
            @foreach ($newsList as $news)
                <div class="w-full sm:w-2/3 md:w-1/2 lg:w-1/3">
                    @include('frontend.partials.news-card', ['news' => $news])
                </div>
            @endforeach
        </div>
    @elseif ($newsList->count() >= 4)
        <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($newsList as $news)
                @include('frontend.partials.news-card', ['news' => $news])
            @endforeach
        </div>
    @else
        <p class="text-center text-gray-500 col-span-full">Новостей пока нет.</p>
    @endif

    {{-- Пагинация --}}
    <div class="mt-8">
        {{ $newsList->withQueryString()->links() }}
    </div>
@endsection
