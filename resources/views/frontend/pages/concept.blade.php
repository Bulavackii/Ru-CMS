@extends('layouts.frontend')

@section('title', 'Концепция RU CMS')

@section('content')
<section class="max-w-4xl mx-auto bg-white border border-gray-300 rounded-2xl shadow-xl p-8 md:p-12 text-[15px] leading-relaxed text-gray-800 space-y-8">
    <h1 class="text-3xl font-extrabold text-blue-800 text-center">📄 Концепция RU CMS</h1>

    {{-- 💡 Миссия --}}
    <div class="space-y-4">
        <h2 class="text-xl font-semibold text-blue-700">🎯 Наша миссия</h2>
        <p>
            Я создал <strong>RU CMS</strong>, чтобы каждый — от индивидуального предпринимателя до государственной организации — мог легко запускать сайты, управлять контентом и масштабировать инфраструктуру
            <span class="text-blue-700 font-medium">без сложных фреймворков и чужих ограничений</span>.
        </p>
    </div>

    {{-- 🚀 Основные принципы --}}
    <div class="space-y-4">
        <h2 class="text-xl font-semibold text-blue-700">🚀 Принципы разработки</h2>
        <ul class="list-disc pl-6 space-y-2">
            <li>🔓 <strong>Открытость</strong> — исходный код доступен не всем: можно изменять, адаптировать и развивать платформу только после согласования со мной;</li>
            <li>⚡ <strong>Производительность</strong> — быстрая работа даже на минимальных конфигурациях хостинга;</li>
            <li>🧩 <strong>Модульность</strong> — система как конструктор: от лендинга до каталога продукции;</li>
            <li>👥 <strong>Прозрачность</strong> — без слежки, скрытого сбора данных и обязательных внешних API;</li>
            <li>🛠️ <strong>Простота доработки</strong> — понятная архитектура на Laravel с чётким разделением модулей.</li>
        </ul>
    </div>

    {{-- 🌍 Модель распространения --}}
    <div class="space-y-4">
        <h2 class="text-xl font-semibold text-blue-700">🌍 Модель распространения</h2>
        <p>RU CMS распространяется по <strong>условно-бесплатной модели</strong>:</p>
        <ul class="list-disc pl-6 space-y-1">
            <li>✅ <strong>Базовая версия</strong> бесплатна — скачивайте, устанавливайте и используйте без ограничений;</li>
            <li>🌟 <strong>Премиум-модули</strong> (опционально): SEO-анализ, аналитика, отчёты, платёжные системы и др.;</li>
            <li>🤝 <strong>Поддержка проекта</strong> — можно внести вклад через <a href="{{ url('/donate') }}" class="text-blue-600 hover:underline">страницу пожертвований</a>;</li>
            <li>🧠 <strong>Внедрение и консалтинг</strong> — помощь командам, организациям и госструктурам в установке и сопровождении.</li>
        </ul>
    </div>

    {{-- 💼 Кому подходит --}}
    <div class="space-y-4">
        <h2 class="text-xl font-semibold text-blue-700">💼 Для кого создаётся RU CMS</h2>
        <ul class="list-disc pl-6 space-y-2">
            <li>📦 Малый и средний бизнес — сайт компании, каталог товаров, формы заявок;</li>
            <li>🏛️ Бюджетные и муниципальные организации — информационные порталы и внутренние системы;</li>
            <li>📸 Креативные студии — управление контентом, портфолио, лендинги;</li>
            <li>👨‍💻 Разработчики — гибкие шаблоны, расширяемые модули, API-интеграции.</li>
        </ul>
    </div>

    {{-- 💬 Поддержка сообщества --}}
    <div class="space-y-4">
        <h2 class="text-xl font-semibold text-blue-700">💬 Поддержка и сообщество</h2>
        <p>Присоединяйтесь к каналам RU CMS:</p>
        <ul class="list-disc pl-6 space-y-1">
            <li>🌐 VK: <a href="https://vk.com/ru_cms" target="_blank" rel="noopener" class="text-blue-600 hover:underline">vk.com/ru_cms</a></li>
            <li>📢 Telegram: <a href="https://t.me/ru_cms" target="_blank" rel="noopener" class="text-blue-600 hover:underline">@ru_cms</a></li>
            <li>✉️ E-mail (разработчики): <a href="mailto:visitorsec@internet.ru" class="text-blue-600 hover:underline">visitorsec@internet.ru</a></li>
        </ul>
        <p class="text-gray-600">GitHub будет опубликован дополнительно.</p>
    </div>

    {{-- 🔙 Назад --}}
    <div class="text-center mt-10">
        <a href="{{ url('/') }}" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg shadow hover:bg-blue-700 transition">
            ← На главную
        </a>
    </div>
</section>
@endsection
