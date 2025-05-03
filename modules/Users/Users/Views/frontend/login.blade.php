@extends('Users::layouts.app')

@section('content')
<div class="container mx-auto max-w-sm mt-10">
    <h2 class="text-xl font-bold mb-4">Вход в аккаунт</h2>
    @if($errors->any())
        <div class="mb-4 text-red-600">
            {{ $errors->first() }}
        </div>
    @endif
    <form method="POST" action="{{ route('login.post') }}">
        @csrf
        <div class="mb-4">
            <input type="email" name="email" required placeholder="Email" class="w-full border p-2 rounded">
        </div>
        <div class="mb-4">
            <input type="password" name="password" required placeholder="Пароль" class="w-full border p-2 rounded">
        </div>
        <button class="bg-blue-500 text-white p-2 rounded w-full">Войти</button>
    </form>
</div>
@endsection
