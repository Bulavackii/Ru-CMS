@extends('layouts.frontend')

@section('title', 'Поддержка и пожертвования — RU CMS')

@section('content')
<section class="max-w-4xl mx-auto bg-white border border-gray-300 rounded-2xl shadow-xl p-8 md:p-12 text-[15px] leading-relaxed text-gray-800 space-y-8">
    <h1 class="text-3xl font-extrabold text-blue-800 text-center">💙 Поддержка и пожертвования</h1>

    {{-- Введение --}}
    <div class="space-y-4">
        <p>
            <strong>RU CMS</strong> — открытый проект, развивающийся за счёт сообщества и добровольной поддержки.
            Мы не продаём лицензии и не скрываем функции за платными подписками. Любая ваша помощь ускоряет
            разработку модулей, документации и новых релизов.
        </p>
        <p class="text-gray-600">
            Пожертвование — это добровольный вклад в развитие проекта, оно не является покупкой товара/услуги и
            не накладывает на вас никаких обязательств.
        </p>
    </div>

    {{-- Как помочь --}}
    <div>
        <h2 class="text-xl font-bold text-blue-700">🤝 Как вы можете помочь</h2>
        <ul class="list-disc pl-6 space-y-2 text-sm mt-2">
            <li>📣 Расскажите о RU CMS в соцсетях и на форумах.</li>
            <li>🐞 Присылайте багрепорты и идеи развития (см. контакты ниже).</li>
            <li>🧩 Участвуйте в разработке модулей и шаблонов, улучшайте документацию.</li>
            <li>💚 Поддержите проект финансово — даже небольшой взнос важен.</li>
        </ul>
    </div>

    {{-- Формы поддержки --}}
    <div>
        <h2 class="text-xl font-bold text-blue-700">💳 Формы поддержки</h2>

        <div class="grid sm:grid-cols-2 gap-6 text-sm mt-3">
            {{-- Платформы донатов --}}
            <div class="bg-gray-50 border rounded-lg p-4 space-y-2 shadow-sm">
                <h3 class="font-semibold text-blue-700">☕ Платформы донатов</h3>
                <p>Удобно для разовых «спасибо» и регулярной поддержки.</p>
                <ul class="list-disc pl-5 space-y-1 text-gray-700">
                    <li><a href="#" class="text-blue-600 hover:underline">Boosty</a> (добавим ссылку позже)</li>
                    <li><a href="#" class="text-blue-600 hover:underline">Donatty</a> (добавим ссылку позже)</li>
                </ul>
            </div>

            {{-- Перевод по реквизитам --}}
            <div class="bg-gray-50 border rounded-lg p-4 space-y-2 shadow-sm">
                <h3 class="font-semibold text-blue-700">🏦 Перевод по реквизитам</h3>
                <p>Для получения реквизитов напишите нам на e-mail.</p>
                <p class="text-gray-700">
                    ✉️ <a href="mailto:visitorsec@internet.ru" class="text-blue-600 hover:underline">visitorsec@internet.ru</a>
                </p>
                <p class="text-gray-600 text-xs">Для юр. лиц подготовим договор/акт при необходимости.</p>
            </div>
        </div>
    </div>

    {{-- Контакты разработчиков --}}
    <div>
        <h2 class="text-xl font-bold text-blue-700">🧑‍💻 Связь с разработчиками</h2>
        <ul class="list-disc pl-6 space-y-2 text-sm mt-2">
            <li>VK: <a href="https://vk.com/ru_cms" target="_blank" rel="noopener" class="text-blue-600 hover:underline">vk.com/ru_cms</a></li>
            <li>Telegram: <a href="https://t.me/ru_cms" target="_blank" rel="noopener" class="text-blue-600 hover:underline">@ru_cms</a></li>
            <li>E-mail: <a href="mailto:visitorsec@internet.ru" class="text-blue-600 hover:underline">visitorsec@internet.ru</a></li>
        </ul>
    </div>

    {{-- Юридические заметки --}}
    <div class="bg-gray-50 border rounded-lg p-4 shadow-sm">
        <h3 class="font-semibold text-blue-700 mb-2">⚖️ Важно</h3>
        <ul class="list-disc pl-6 space-y-1 text-xs text-gray-600">
            <li>Пожертвования добровольные и, как правило, невозвратные. Если внесли взнос по ошибке — напишите на e-mail в течение 24 часов, постараюсь помочь.</li>
            <li>Пожертвование не является оплатой услуг и не предоставляет эксклюзивных прав на проект.</li>
            <li>Мы не храним платёжные данные — операции проводятся через платёжные сервисы.</li>
        </ul>
    </div>

    {{-- Назад --}}
    <div class="text-center mt-10">
        <a href="{{ url('/') }}" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg shadow hover:bg-blue-700 transition">
            ← На главную
        </a>
    </div>

    <p class="text-center text-gray-500 text-sm">Последнее обновление: {{ date('d.m.Y') }}</p>
</section>
@endsection
