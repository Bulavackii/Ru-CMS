@extends('layouts.frontend')

@section('title', 'Добро пожаловать в RU CMS')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50">
    {{-- Hero секция --}}
    <div class="relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold text-gray-900 mb-6">
                    🎉 Добро пожаловать в <span class="text-blue-600">RU CMS</span>!
                </h1>
                <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                    Модульная система управления контентом для России и СНГ
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('admin.dashboard') }}" 
                       class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-lg font-semibold">
                        <i class="fas fa-gauge-high mr-2"></i>
                        Перейти в админ-панель
                    </a>
                    <a href="{{ route('login') }}" 
                       class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-800 rounded-lg hover:bg-gray-200 transition text-lg font-semibold">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Войти в систему
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Возможности системы --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h2 class="text-3xl font-bold text-center mb-12 text-gray-900">
            ✨ Возможности системы
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Безопасность --}}
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition">
                <div class="text-4xl mb-4">🔒</div>
                <h3 class="text-xl font-bold mb-2">Безопасность</h3>
                <p class="text-gray-600">
                    2FA аутентификация, защита от брутфорса, автоматическая блокировка подозрительных IP, защита от SQL injection и XSS
                </p>
            </div>

            {{-- Модульность --}}
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition">
                <div class="text-4xl mb-4">🧩</div>
                <h3 class="text-xl font-bold mb-2">Модульная архитектура</h3>
                <p class="text-gray-600">
                    HMVC архитектура, независимые модули, легкое подключение/отключение, создание собственных модулей
                </p>
            </div>

            {{-- Производительность --}}
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition">
                <div class="text-4xl mb-4">⚡</div>
                <h3 class="text-xl font-bold mb-2">Производительность</h3>
                <p class="text-gray-600">
                    Оптимизация БД, расширенное кэширование, оптимизация изображений, Gzip сжатие, устранение N+1 проблем
                </p>
            </div>

            {{-- Бэкапы --}}
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition">
                <div class="text-4xl mb-4">💾</div>
                <h3 class="text-xl font-bold mb-2">Автоматические бэкапы</h3>
                <p class="text-gray-600">
                    Ежедневное резервное копирование БД, еженедельное копирование файлов, загрузка в облако, управление через админ-панель
                </p>
            </div>

            {{-- Мультиязычность --}}
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition">
                <div class="text-4xl mb-4">🌍</div>
                <h3 class="text-xl font-bold mb-2">Мультиязычность</h3>
                <p class="text-gray-600">
                    Поддержка русского и английского, адаптация для СНГ, автоматическое определение языка, форматирование валют и дат
                </p>
            </div>

            {{-- Обновления --}}
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition">
                <div class="text-4xl mb-4">🔄</div>
                <h3 class="text-xl font-bold mb-2">Централизованные обновления</h3>
                <p class="text-gray-600">
                    Автоматическая проверка обновлений, безопасная установка, проверка целостности, автоматические бэкапы, откат при ошибках
                </p>
            </div>

            {{-- Подписки --}}
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition">
                <div class="text-4xl mb-4">💳</div>
                <h3 class="text-xl font-bold mb-2">Система подписок</h3>
                <p class="text-gray-600">
                    Гибкие тарифы (Basic, Pro, Enterprise), промокоды со скидками, лицензионные ключи, ограничения по тарифам
                </p>
            </div>

            {{-- Аналитика --}}
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition">
                <div class="text-4xl mb-4">📊</div>
                <h3 class="text-xl font-bold mb-2">Аналитика</h3>
                <p class="text-gray-600">
                    Отслеживание просмотров, статистика посетителей, популярный контент, интеграция с Яндекс.Метрикой, графики и отчеты
                </p>
            </div>

            {{-- API --}}
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition">
                <div class="text-4xl mb-4">🔌</div>
                <h3 class="text-xl font-bold mb-2">REST API</h3>
                <p class="text-gray-600">
                    Полноценный REST API, JWT аутентификация, Swagger документация, версионирование API, rate limiting
                </p>
            </div>
        </div>
    </div>

    {{-- Быстрый старт --}}
    <div class="bg-blue-600 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold mb-8">🚀 Быстрый старт</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-left">
                    <div class="bg-white/10 rounded-lg p-6 backdrop-blur">
                        <div class="text-2xl font-bold mb-2">1️⃣</div>
                        <h3 class="text-xl font-semibold mb-2">Войдите в админ-панель</h3>
                        <p>Используйте данные администратора, созданные при установке</p>
                    </div>
                    <div class="bg-white/10 rounded-lg p-6 backdrop-blur">
                        <div class="text-2xl font-bold mb-2">2️⃣</div>
                        <h3 class="text-xl font-semibold mb-2">Настройте модули</h3>
                        <p>Активируйте нужные модули и настройте их параметры</p>
                    </div>
                    <div class="bg-white/10 rounded-lg p-6 backdrop-blur">
                        <div class="text-2xl font-bold mb-2">3️⃣</div>
                        <h3 class="text-xl font-semibold mb-2">Создайте контент</h3>
                        <p>Начните создавать новости, страницы и другой контент</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Документация и полезные ссылки --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="bg-gray-50 rounded-xl p-8">
            <h2 class="text-2xl font-bold mb-6 text-center">📚 Документация и ресурсы</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="/api/docs" class="flex items-center p-4 bg-white rounded-lg hover:shadow-md transition border border-gray-200">
                    <i class="fas fa-code text-2xl text-green-600 mr-4"></i>
                    <div>
                        <div class="font-semibold text-gray-900">API Документация</div>
                        <div class="text-sm text-gray-600">Swagger документация REST API</div>
                    </div>
                </a>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center p-4 bg-white rounded-lg hover:shadow-md transition border border-gray-200">
                    <i class="fas fa-gauge-high text-2xl text-blue-600 mr-4"></i>
                    <div>
                        <div class="font-semibold text-gray-900">Админ-панель</div>
                        <div class="text-sm text-gray-600">Управление контентом и настройками</div>
                    </div>
                </a>
                <a href="https://github.com/Bulavackii/Ru-CMS" target="_blank" class="flex items-center p-4 bg-white rounded-lg hover:shadow-md transition border border-gray-200">
                    <i class="fab fa-github text-2xl text-gray-800 mr-4"></i>
                    <div>
                        <div class="font-semibold text-gray-900">GitHub</div>
                        <div class="text-sm text-gray-600">Исходный код и issues</div>
                    </div>
                </a>
            </div>
            
            {{-- Быстрые ссылки на модули в админке --}}
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">🚀 Быстрый старт в админ-панели:</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    <a href="{{ route('admin.modules.index') }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                        📦 Модули
                    </a>
                    <a href="{{ route('admin.news.index') }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                        📰 Новости
                    </a>
                    <a href="{{ route('admin.categories.index') }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                        🏷️ Категории
                    </a>
                    <a href="{{ route('admin.files.index') }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                        📁 Файлы
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Скрыть приветствие --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16 text-center">
        <button onclick="hideWelcome()" 
                class="text-gray-500 hover:text-gray-700 text-sm">
            <i class="fas fa-times mr-1"></i> Скрыть это приветствие
        </button>
    </div>
</div>

<script>
function hideWelcome() {
    localStorage.setItem('rucms_welcome_hidden', 'true');
    window.location.href = '/';
}
</script>
@endsection

