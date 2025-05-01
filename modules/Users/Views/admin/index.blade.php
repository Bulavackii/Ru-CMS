@extends('layouts.admin')

@section('title', 'Пользователи')

@section('header', 'Управление пользователями')

@section('content')

@if (session('success'))
    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
@endif

<table class="min-w-full bg-white shadow rounded mb-10">
    <thead>
        <tr class="border-b bg-gray-100">
            <th class="px-4 py-2 text-left">Имя</th>
            <th class="px-4 py-2 text-left">Email</th>
            <th class="px-4 py-2 text-left">Роль</th>
            <th class="px-4 py-2 text-left">Действия</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($users as $user)
            <tr class="border-b">
                <td class="px-4 py-2">{{ $user->name }}</td>
                <td class="px-4 py-2">{{ $user->email }}</td>
                <td class="px-4 py-2">{{ $user->is_admin ? 'Админ' : 'Клиент' }}</td>
                <td class="px-4 py-2">
                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">Удалить</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

@endsection
