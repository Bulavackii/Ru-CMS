@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4">
    <h1 class="text-xl font-bold mb-4">Управление модулями</h1>
    <table class="min-w-full bg-white shadow rounded">
        <thead>
            <tr>
                <th class="py-2 px-4 border">Название</th>
                <th class="py-2 px-4 border">Версия</th>
                <th class="py-2 px-4 border">Активен</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($modules as $module)
            <tr>
                <td class="py-2 px-4 border">{{ $module->name }}</td>
                <td class="py-2 px-4 border">{{ $module->version }}</td>
                <td class="py-2 px-4 border">
                    @if ($module->active)
                        <span class="text-green-600">Да</span>
                    @else
                        <span class="text-red-600">Нет</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
