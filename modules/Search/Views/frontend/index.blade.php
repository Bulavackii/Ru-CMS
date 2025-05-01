@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <form method="GET" action="{{ route('search.index') }}" class="mb-6">
        <input type="text" name="q" value="{{ request('q') }}" class="border p-2 rounded w-full" placeholder="Поиск по сайту...">
    </form>

    @if ($query)
        <h2 class="text-xl font-semibold mb-2">Результаты для: "{{ $query }}"</h2>

        @if ($posts->count())
            <h3 class="font-bold mt-4 mb-2">Статьи</h3>
            @foreach ($posts as $post)
                <div class="mb-4">
                    <h4 class="text-lg font-bold">{{ $post->title }}</h4>
                    <p class="text-gray-600">{{ Str::limit(strip_tags($post->content), 150) }}</p>
                </div>
            @endforeach
        @endif

        @if ($products->count())
            <h3 class="font-bold mt-4 mb-2">Товары</h3>
            @foreach ($products as $product)
                <div class="mb-4">
                    <h4 class="text-lg font-bold">{{ $product->name }}</h4>
                    <p class="text-gray-600">{{ Str::limit(strip_tags($product->description), 150) }}</p>
                </div>
            @endforeach
        @endif

        @if (!$posts->count() && !$products->count())
            <p>Ничего не найдено.</p>
        @endif
    @endif
</div>
@endsection
