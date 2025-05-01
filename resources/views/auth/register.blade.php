@extends('layouts.guest')

@section('title', 'Регистрация')

@section('content')
<h2 class="text-2xl font-bold mb-4">Регистрация</h2>

@if ($errors->any())
    <div class="mb-4 text-red-600">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ route('register') }}">
    @csrf
    <input type="text" name="name" placeholder="Имя" required
           class="w-full p-2 border rounded mb-4" value="{{ old('name') }}">

    <input type="email" name="email" placeholder="Email" required
           class="w-full p-2 border rounded mb-4" value="{{ old('email') }}">

    <input type="password" name="password" placeholder="Пароль" required
           class="w-full p-2 border rounded mb-4">

    <input type="password" name="password_confirmation" placeholder="Повторите пароль" required
           class="w-full p-2 border rounded mb-4">

    <button type="submit" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">
        Зарегистрироваться
    </button>
</form>

<div class="mt-4 text-sm text-center">
    <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Уже есть аккаунт? Войти</a>
</div>
@endsection
