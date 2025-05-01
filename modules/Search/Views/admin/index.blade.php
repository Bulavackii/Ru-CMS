@extends('layouts.admin')

@section('title', 'Поиск')

@section('header', 'Поиск по системе')

@section('content')
    <form method="GET" action="{{ route('admin.search.index') }}" class="mb-6">
        <input type="text" name="q" value="{{ request('q') }}" class="border p-2 rounded w-64" placeholder="Введите запрос...">
        <button class="bg-blue-500 text-white px-4 py-2 rounded">Поиск</button>
    </form>

    @if ($query)
        <h2 class="text-xl font-semibold mb-2">Результаты для: "{{ $query }}"</h2>

        @if ($modules->count())
            <h3 class="font-bold mt-6 mb-2">Модули</h3>
            <ul class="list-disc list-inside">
                @foreach ($modules as $module)
                    <li>{{ $module->name }} (v{{ $module->version }})</li>
                @endforeach
            </ul>
        @endif

        @if ($users->count())
            <h3 class="font-bold mt-6 mb-2">Пользователи</h3>
            <ul class="list-disc list-inside">
                @foreach ($users as $user)
                    <li>{{ $user->name }} ({{ $user->email }})</li>
                @endforeach
            </ul>
        @endif

        @if ($categories->count())
            <h3 class="font-bold mt-6 mb-2">Категории</h3>
            <ul class="list-disc list-inside">
                @foreach ($categories as $cat)
                    <li>{{ $cat->title }}</li>
                @endforeach
            </ul>
        @endif

        @if ($products->count())
            <h3 class="font-bold mt-6 mb-2">Товары</h3>
            <ul class="list-disc list-inside">
                @foreach ($products as $product)
                    <li>{{ $product->name }}</li>
                @endforeach
            </ul>
        @endif

        @if (
            !$modules->count() &&
            !$users->count() &&
            !$categories->count() &&
            !$products->count()
        )
            <p class="text-gray-600 mt-4">Ничего не найдено.</p>
        @endif
    @endif
@endsection
