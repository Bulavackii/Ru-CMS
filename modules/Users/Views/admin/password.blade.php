@extends('layouts.admin')

@section('title', 'Изменение пароля')

@section('content')
    <div class="max-w-xl mx-auto bg-white dark:bg-gray-900 p-6 rounded shadow">
        <h1 class="text-xl font-bold mb-6 text-gray-800 dark:text-white">🔒 Изменить пароль для: {{ $user->name }}</h1>

        <form action="{{ route('admin.users.password.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="password" class="block mb-1 text-sm font-medium">Новый пароль</label>
                <input type="password" name="password" id="password"
                       class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:border-blue-300"
                       required>
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="block mb-1 text-sm font-medium">Подтвердите пароль</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                       class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring focus:border-blue-300"
                       required>
            </div>

            <button type="submit"
                    class="bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 transition font-semibold">
                Обновить пароль
            </button>
        </form>
    </div>
@endsection
