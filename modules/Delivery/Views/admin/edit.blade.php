@extends('layouts.admin')

@section('title', 'Редактировать метод доставки')

@section('content')
    <h1 class="text-2xl font-bold mb-6">✏️ Редактировать метод доставки</h1>

    <form method="POST" action="{{ route('admin.delivery.update', $delivery) }}" class="space-y-6">
        @csrf
        @method('PUT')
        @include('Delivery::admin.form', ['method' => $delivery])
        <button type="submit"
                class="bg-blue-600 text-white px-6 py-2 rounded shadow hover:bg-blue-700 transition">
            💾 Обновить
        </button>
    </form>
@endsection
