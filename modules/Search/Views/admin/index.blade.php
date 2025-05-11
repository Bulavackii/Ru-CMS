@extends('layouts.admin')

@section('title', 'Поиск')
@section('header', 'Поиск по системе')

@section('content')
    {{-- 🔍 Форма поиска --}}
    <form method="GET" action="{{ route('admin.search.index') }}" class="mb-6 flex flex-wrap items-center gap-3">
        <input type="text" name="q" value="{{ request('q') }}"
               class="border border-gray-300 dark:border-gray-700 px-4 py-2 rounded-md text-sm w-full md:w-72 bg-white dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-black"
               placeholder="🔎 Введите запрос...">
        <button type="submit"
                class="inline-flex items-center gap-2 bg-black hover:bg-gray-800 text-white px-4 py-2 rounded-md text-sm font-semibold shadow transition">
            <i class="fas fa-search"></i> Поиск
        </button>
    </form>

    @if ($query)
        <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
            🔍 Результаты для: <span class="italic text-blue-600">"{{ $query }}"</span>
        </h2>

        {{-- 📦 Модули --}}
        @if ($modules->count())
            <h3 class="text-sm font-bold text-gray-600 dark:text-gray-300 mt-6 mb-2 uppercase">Модули</h3>
            <ul class="pl-5 list-disc text-gray-700 dark:text-gray-200 text-sm space-y-1">
                @foreach ($modules as $module)
                    <li>{{ $module->name }} <span class="text-gray-400">(v{{ $module->version }})</span></li>
                @endforeach
            </ul>
        @endif

        {{-- 👤 Пользователи --}}
        @if ($users->count())
            <h3 class="text-sm font-bold text-gray-600 dark:text-gray-300 mt-6 mb-2 uppercase">Пользователи</h3>
            <ul class="pl-5 list-disc text-gray-700 dark:text-gray-200 text-sm space-y-1">
                @foreach ($users as $user)
                    <li>{{ $user->name }} <span class="text-gray-400">({{ $user->email }})</span></li>
                @endforeach
            </ul>
        @endif

        {{-- 🏷️ Категории --}}
        @if ($categories->count())
            <h3 class="text-sm font-bold text-gray-600 dark:text-gray-300 mt-6 mb-2 uppercase">Категории</h3>
            <ul class="pl-5 list-disc text-gray-700 dark:text-gray-200 text-sm space-y-1">
                @foreach ($categories as $cat)
                    <li>{{ $cat->title }}</li>
                @endforeach
            </ul>
        @endif

        {{-- 🛒 Товары --}}
        @if ($products->count())
            <h3 class="text-sm font-bold text-gray-600 dark:text-gray-300 mt-6 mb-2 uppercase">Товары</h3>
            <ul class="pl-5 list-disc text-gray-700 dark:text-gray-200 text-sm space-y-1">
                @foreach ($products as $product)
                    <li>{{ $product->name }}</li>
                @endforeach
            </ul>
        @endif

        {{-- 🚫 Ничего не найдено --}}
        @if (
            !$modules->count() &&
            !$users->count() &&
            !$categories->count() &&
            !$products->count()
        )
            <div class="mt-6 text-gray-500 dark:text-gray-400 italic">
                🕵️ Ничего не найдено по вашему запросу.
            </div>
        @endif
    @endif
@endsection
