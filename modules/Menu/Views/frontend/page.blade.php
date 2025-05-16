@extends('layouts.frontend')

@section('title', $title)

@section('content')
<main class="flex-grow py-10 relative">
    {{-- 🌄 Фон как в header --}}
    <div class="absolute inset-0 z-0 opacity-10 pointer-events-none"
         style="background-image: url('{{ asset('images/fon.jpg') }}'); background-repeat: repeat; background-size: auto;"></div>

    <div class="relative z-10">
        <div class="max-w-4xl mx-auto bg-white/80 dark:bg-gray-900/80 backdrop-blur-md rounded-2xl shadow-lg px-6 py-8 overflow-hidden">
            {{-- Заголовок --}}
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white leading-tight mb-6 text-center">
                {{ $page->title }}
            </h1>

            {{-- Категории --}}
            @if ($page->categories->isNotEmpty())
                <div class="mb-4 text-sm flex flex-wrap gap-2 justify-center">
                    @foreach ($page->categories as $cat)
                        <a href="{{ url('/?category=' . $cat->id) }}"
                           class="inline-block bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200 px-3 py-1 rounded-full text-xs font-medium hover:underline transition">
                            {{ $cat->title }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Контент --}}
            <div class="prose prose-sm sm:prose lg:prose-lg max-w-none text-gray-800 dark:text-gray-100 mb-8">
                {!! $page->content !!}
            </div>

            {{-- Назад --}}
            <div class="text-center mt-10">
                <a href="{{ url('/') }}"
                   class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-800 px-5 py-2.5 rounded-lg font-medium text-sm shadow-md transition">
                    ⬅️ На главную
                </a>
            </div>
        </div>
    </div>
</main>
@endsection
