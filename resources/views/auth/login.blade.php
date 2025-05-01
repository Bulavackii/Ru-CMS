@extends('layouts.guest')

@section('title', 'Вход')

@section('content')
<h2 class="text-2xl font-bold mb-4">Вход</h2>

@if ($errors->any())
    <div class="mb-4 text-red-600">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf
    <input type="email" name="email" placeholder="Email" required autofocus
           class="w-full p-2 border rounded mb-4" value="{{ old('email') }}">

    <input type="password" name="password" placeholder="Пароль" required
           class="w-full p-2 border rounded mb-4">

    <div class="flex items-center justify-between mb-4">
        <label class="text-sm">
            <input type="checkbox" name="remember" class="mr-1">
            Запомнить меня
        </label>
        <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:underline">Забыли пароль?</a>
    </div>

    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
        Войти
    </button>
</form>

<div class="mt-4 text-sm text-center">
    <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Нет аккаунта? Зарегистрироваться</a>
</div>
@endsection
