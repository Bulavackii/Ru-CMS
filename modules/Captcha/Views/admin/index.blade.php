@extends('layouts.admin')

@section('title', 'Настройки каптчи')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">🛡️ Настройки каптчи</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Типы каптчи --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">Доступные типы</h2>
            <ul class="space-y-3">
                <li class="flex items-center gap-2">
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm">Image</span>
                    <span>Классическая картинка с кодом</span>
                </li>
                <li class="flex items-center gap-2">
                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">Slider</span>
                    <span>Перетаскивание слайдера</span>
                </li>
                <li class="flex items-center gap-2">
                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-sm">Math</span>
                    <span>Математические выражения</span>
                </li>
                <li class="flex items-center gap-2">
                    <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-sm">Question</span>
                    <span>Вопрос-ответ</span>
                </li>
            </ul>
        </div>

        {{-- Примеры --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">Демонстрация</h2>

            <div class="space-y-4">
                <div>
                    <label class="font-semibold">Image:</label>
                    <div id="demo-image"></div>
                </div>

                <div>
                    <label class="font-semibold">Slider:</label>
                    <div id="demo-slider"></div>
                </div>

                <div>
                    <label class="font-semibold">Math:</label>
                    <div id="demo-math"></div>
                </div>

                <div>
                    <label class="font-semibold">Question:</label>
                    <div id="demo-question"></div>
                </div>
            </div>

            <button onclick="loadDemos()" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded">
                Обновить примеры
            </button>
        </div>
    </div>

    {{-- Как использовать --}}
    <div class="mt-6 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Как использовать</h2>

        <div class="space-y-4">
            <div>
                <h3 class="font-semibold mb-2">1. В Blade шаблоне:</h3>
                <pre class="bg-gray-100 dark:bg-gray-900 p-3 rounded text-sm overflow-x-auto">
<!-- Простой вызов -->
{!! captcha_img('image') !!}

<!-- С параметрами -->
{!! captcha_img('slider', ['width' => 250]) !!}

<!-- В форме -->
<form method="POST">
    @csrf
    {!! captcha_img('math') !!}
    <input type="text" name="captcha" required>
    <button type="submit">Отправить</button>
</form>
                </pre>
            </div>

            <div>
                <h3 class="font-semibold mb-2">2. Валидация формы:</h3>
                <pre class="bg-gray-100 dark:bg-gray-900 p-3 rounded text-sm overflow-x-auto">
// В контроллере
$request->validate([
    'captcha' => 'required|captcha:image', // или slider, math, question
]);

// Или через сервис
$service = app('captcha');
if (!$service->verify($request->captcha, 'image')) {
    return back()->withErrors(['captcha' => 'Неверный код']);
}
                </pre>
            </div>

            <div>
                <h3 class="font-semibold mb-2">3. API (AJAX):</h3>
                <pre class="bg-gray-100 dark:bg-gray-900 p-3 rounded text-sm overflow-x-auto">
// Генерация
fetch('/api/captcha/generate/slider')
    .then(r => r.json())
    .then(data => {
        document.getElementById('captcha-container').innerHTML = data.html;
    });

// Проверка
fetch('/api/captcha/verify', {
    method: 'POST',
    body: JSON.stringify({
        captcha: '12345',
        type: 'slider'
    })
})
.then(r => r.json())
.then(data => {
    if (data.success) {
        // Каптча верна
    }
});
                </pre>
            </div>

            <div>
                <h3 class="font-semibold mb-2">4. Произвольное встраивание:</h3>
                <pre class="bg-gray-100 dark:bg-gray-900 p-3 rounded text-sm overflow-x-auto">
// В любом месте проекта
<div id="my-captcha"></div>

<script>
// Динамическая загрузка
const container = document.getElementById('my-captcha');
fetch('/api/captcha/generate/math')
    .then(r => r.json())
    .then(data => {
        container.innerHTML = data.html +
            '<input type="text" name="my_captcha" required>';
    });
</script>
                </pre>
            </div>
        </div>
    </div>

    {{-- Дополнительные возможности --}}
    <div class="mt-6 bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Дополнительные возможности</h2>
        <ul class="list-disc list-inside space-y-2">
            <li>✅ Поддержка нескольких типов каптчи</li>
            <li>✅ Гибкая настройка параметров</li>
            <li>✅ Валидация через Laravel Validator</li>
            <li>✅ API для AJAX запросов</li>
            <li>✅ Возможность встраивания в любое место</li>
            <li>✅ Автоочистка старых кодов</li>
            <li>✅ Поддержка кастомных шрифтов</li>
            <li>✅ Защита от ботов</li>
        </ul>
    </div>
</div>

<script>
function loadDemos() {
    // Image
    fetch('/api/captcha/generate/image')
        .then(r => r.json())
        .then(data => {
            document.getElementById('demo-image').innerHTML = data.html;
        });

    // Slider
    fetch('/api/captcha/generate/slider')
        .then(r => r.json())
        .then(data => {
            document.getElementById('demo-slider').innerHTML = data.html;
        });

    // Math
    fetch('/api/captcha/generate/math')
        .then(r => r.json())
        .then(data => {
            document.getElementById('demo-math').innerHTML = data.html;
        });

    // Question
    fetch('/api/captcha/generate/question')
        .then(r => r.json())
        .then(data => {
            document.getElementById('demo-question').innerHTML = data.html;
        });
}

// Загрузить при открытии
document.addEventListener('DOMContentLoaded', loadDemos);
</script>
@endsection
