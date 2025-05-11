@extends('layouts.frontend')

@section('title', 'Контакты')

@section('content')
    <section class="max-w-4xl mx-auto bg-white border border-black rounded-xl shadow-lg p-8 md:p-10 space-y-8">
        <h1 class="text-4xl font-bold text-center text-blue-900 mb-4">📞 Связаться с нами</h1>
        <p class="text-center text-gray-600 text-sm">Будем рады ответить на ваши вопросы и предложения</p>

        <div class="grid sm:grid-cols-2 gap-8 text-sm text-gray-800">
            {{-- 📍 Адрес --}}
            <div class="flex items-start gap-4">
                <i class="fas fa-map-marker-alt text-blue-600 text-xl mt-1"></i>
                <div>
                    <h2 class="font-semibold text-blue-700 mb-1">Адрес офиса</h2>
                    <p>г. Москва, ул. Примерная, д. 123, офис 45</p>
                </div>
            </div>

            {{-- 📧 Email --}}
            <div class="flex items-start gap-4">
                <i class="fas fa-envelope text-blue-600 text-xl mt-1"></i>
                <div>
                    <h2 class="font-semibold text-blue-700 mb-1">Электронная почта</h2>
                    <p>
                        <a href="mailto:support@example.com" class="text-blue-600 hover:underline">
                            support@example.com
                        </a>
                    </p>
                </div>
            </div>

            {{-- 📞 Телефон --}}
            <div class="flex items-start gap-4">
                <i class="fas fa-phone-alt text-blue-600 text-xl mt-1"></i>
                <div>
                    <h2 class="font-semibold text-blue-700 mb-1">Телефон</h2>
                    <p>
                        <a href="tel:+74951234567" class="text-blue-600 hover:underline">
                            +7 (495) 123-45-67
                        </a>
                    </p>
                </div>
            </div>

            {{-- ⏰ Время работы --}}
            <div class="flex items-start gap-4">
                <i class="fas fa-clock text-blue-600 text-xl mt-1"></i>
                <div>
                    <h2 class="font-semibold text-blue-700 mb-1">Время работы</h2>
                    <p>Пн–Пт: с 9:00 до 18:00</p>
                    <p class="text-gray-500">Выходные: Сб, Вс</p>
                </div>
            </div>
        </div>

        {{-- 🔙 Кнопка назад --}}
        <div class="text-center pt-6">
            <a href="{{ url('/') }}"
               class="inline-flex items-center px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded shadow transition-transform transform hover:scale-105">
                <i class="fas fa-arrow-left mr-2"></i> На главную
            </a>
        </div>
    </section>
@endsection
