@extends('layouts.admin')

@section('title', 'Редактировать способ оплаты')

@section('content')
    <h1 class="text-2xl font-bold mb-6">✏️ Редактирование: {{ $method->title }}</h1>

    <form action="{{ route('admin.payments.update', $method->id) }}" method="POST" class="space-y-6 max-w-xl">
        @csrf @method('PUT')
        @include('Payments::admin.partials.form', ['method' => $method])
        <button type="submit" class="bg-black hover:bg-gray-800 text-white px-6 py-2 rounded shadow">💾 Обновить</button>
    </form>
@endsection
