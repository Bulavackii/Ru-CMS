@extends('layouts.admin')
@section('title','Темы')

@section('content')
<h1 class="text-2xl font-bold mb-6">🎨 Темы</h1>

<a href="{{ route('admin.visual.themes.create') }}"
   class="bg-blue-600 text-white px-4 py-2 rounded">Создать</a>

@includeIf('layouts.partials.flash')

<table class="mt-4 w-full text-sm">
    <thead>
    <tr class="text-left text-gray-500">
        <th class="py-2 pr-4">#</th>
        <th class="py-2 pr-4">Название</th>
        <th class="py-2 pr-4">Slug</th>
        <th class="py-2 pr-4">Статус</th>
        <th class="py-2 pr-4 text-right">Действия</th>
    </tr>
    </thead>
    <tbody class="divide-y">
    @foreach($themes as $theme)
        <tr>
            <td class="py-3 pr-4">{{ $theme->id }}</td>
            <td class="py-3 pr-4 font-medium">
                {{ $theme->title }}
            </td>
            <td class="py-3 pr-4 text-gray-500">{{ $theme->slug }}</td>
            <td class="py-3 pr-4">
                @if($theme->is_default)
                    <span class="px-2 py-1 rounded bg-green-100 text-green-800">Активна</span>
                @else
                    <span class="px-2 py-1 rounded bg-gray-100 text-gray-600">Не активна</span>
                @endif
            </td>
            <td class="py-3 pr-4">
                <div class="flex items-center gap-2 justify-end">
                    <a href="{{ route('admin.visual.themes.edit', $theme) }}"
                       class="px-3 py-1.5 rounded border">Редактировать</a>

                    {{-- Применить (если не активна) --}}
                    @unless($theme->is_default)
                        <form method="POST"
                              action="{{ route('admin.visual.themes.apply', $theme) }}"
                              onsubmit="return confirm('Сделать тему «{{ $theme->title }}» активной?');">
                            @csrf
                            @method('PATCH')
                            <button class="px-3 py-1.5 rounded border bg-black text-white">
                                Применить
                            </button>
                        </form>
                    @endunless

                    {{-- Удалить (активную нельзя) --}}
                    <form method="POST"
                          action="{{ route('admin.visual.themes.destroy', $theme) }}"
                          onsubmit="return confirm('Удалить тему «{{ $theme->title }}»? Это действие необратимо.');">
                        @csrf
                        @method('DELETE')
                        <button class="px-3 py-1.5 rounded border text-red-600 hover:bg-red-50"
                                {{ $theme->is_default ? 'disabled' : '' }}
                                title="{{ $theme->is_default ? 'Сначала примените другую тему' : 'Удалить' }}">
                            Удалить
                        </button>
                    </form>
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
