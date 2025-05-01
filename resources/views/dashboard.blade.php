@extends('layouts.app')

@section('content')
<div class="container mx-auto py-10">
    <h1 class="text-2xl font-bold mb-6">Привет, {{ $user->name }}!</h1>

    <div class="bg-white shadow rounded p-6">
        <p class="mb-2"><strong>Email:</strong> {{ $user->email }}</p>
        <p class="mb-2">
            <strong>Статус:</strong>
            @if ($user->is_admin)
                Администратор
            @else
                Обычный пользователь
            @endif
        </p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="mt-4 bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">
                Выйти
            </button>
        </form>
    </div>
</div>
@endsection
