@extends('layouts.frontend')

@section('title', 'FAQ — Часто задаваемые вопросы')

@section('content')
    <div
        class="max-w-4xl mx-auto bg-white border border-gray-300 rounded-2xl p-8 md:p-10 shadow-xl text-[15px] text-gray-800 space-y-8">
        {{-- 🧠 Заголовок --}}
        <h1 class="text-3xl font-extrabold text-center text-blue-800">❓ Часто задаваемые вопросы (FAQ)</h1>
        <p class="text-center text-gray-600 text-sm -mt-3">Нужна помощь? Здесь собраны ответы на самые популярные вопросы по
            работе с Ru-CMS</p>

        {{-- 🔍 Вопросы --}}
        <div class="space-y-6">
            <div>
                <h2 class="font-semibold text-blue-700 text-lg">📌 Как зарегистрироваться на сайте?</h2>
                <p>Нажмите <a href="{{ route('register') }}" class="text-blue-600 hover:underline">«Регистрация»</a> в верхнем
                    меню. Заполните форму и подтвердите email.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">🛠 Я забыл(а) пароль. Что делать?</h2>
                <p>Перейдите на <a href="{{ route('password.request') }}" class="text-blue-600 hover:underline">страницу
                        восстановления пароля</a>, введите ваш email — и получите инструкцию на почту.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">🏢 Можно ли зарегистрироваться как организация?</h2>
                <p>Да, при регистрации выберите тип «Юридическое лицо» — появятся поля для ИНН, ОГРН и адреса.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">🧩 Где управлять модулями?</h2>
                <p>В админке на странице <a href="{{ url('/admin/modules') }}"
                        class="text-blue-600 hover:underline">Модули</a> вы можете включать, отключать, архивировать и
                    скачивать ZIP-архивы модулей.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">🎨 Как подключить свой шаблон?</h2>
                <p>Создайте файл шаблона в директории: <code
                        class="bg-gray-100 px-2 py-1 rounded text-xs">resources/views/frontend/templates/название.blade.php</code>.
                    Он автоматически появится в списке при создании новости.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">🖼️ Можно ли использовать видео и изображения?</h2>
                <p>Да! Вы можете загружать медиафайлы при создании записи (TinyMCE) или использовать <a
                        href="{{ url('/admin/files') }}" class="text-blue-600 hover:underline">менеджер файлов</a> в
                    админке.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">🔒 Насколько безопасна Ru-CMS?</h2>
                <p>Ru-CMS использует <strong>bcrypt</strong> для паролей, <strong>JWT</strong> для API-аутентификации и
                    политику разделения ролей.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">⚙️ Как обновить информацию о себе?</h2>
                <p>Зайдите в <a href="{{ route('dashboard.edit') }}" class="text-blue-600 hover:underline">личный
                        кабинет</a>, чтобы отредактировать имя, email, пароль и другие данные.</p>
            </div>

            <div>
                <h2 class="font-semibold text-blue-700 text-lg">📬 Как обратиться в поддержку?</h2>
                <p>Вы можете заполнить форму на <a href="{{ url('/contacts') }}"
                        class="text-blue-600 hover:underline">странице «Контакты»</a> или отправить сообщение через модуль
                    «Сообщения» в админке.</p>
            </div>
        </div>

        {{-- 📦 Инструкция по созданию шаблона --}}
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 mt-12 space-y-4 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                🛠 Как создать собственный кастомный шаблон с любым кодом?
            </h3>
            <ol class="list-decimal list-inside text-sm text-gray-700 space-y-2">
                <li>
                    Создайте Blade-файл в папке
                    <code class="bg-gray-100 px-1 py-0.5 rounded text-xs">resources/views/frontend/templates/</code>
                    с именем шаблона, например:
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs block mt-1">custom.blade.php</code>
                </li>
                <li>
                    Внутри шаблона используйте переменную:
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs">$templates['custom']</code>
                    для вывода контента из базы данных.
                </li>
                <li>
                    В файле <code class="text-xs">routes/web.php</code> добавьте имя шаблона в массив
                    <code class="text-xs">$templateKeys</code>, чтобы он отображался на главной:
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs block mt-1">'custom'</code>
                </li>
                <li>
                    В админке при создании новости выберите шаблон
                    <code class="text-xs">custom</code> — CMS подключит нужный файл автоматически.
                </li>
                <li>
                    В контроллере <code class="text-xs">NewsController@index()</code> добавьте строку в массив
                    шаблонов в $customLabels в private function loadTemplates():
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs block mt-1">'custom' => 'Пользовательский
                        шаблон'</code>
                </li>
                <li>
                    Для подключения шаблона на странице используйте:
                    <code
                        class="bg-gray-100 px-2 py-1 rounded text-xs block mt-1">&#64;includeIf('frontend.templates.custom',
                        ['templates' => ['custom' => $templates['custom'] ?? collect()]])</code>
                </li>
            </ol>

            <div class="bg-white border border-gray-100 rounded-lg p-4 text-sm text-gray-800">
                <p class="mb-1 font-semibold">🔧 Пример подключения шаблона на главной:</p>
                <pre class="bg-gray-100 text-xs rounded p-3 overflow-x-auto">
&#64;includeIf('frontend.templates.custom', ['templates' => ['custom' => $templates['custom'] ?? collect()]])
        </pre>
            </div>

            <p class="text-sm text-gray-500">
                🧩 Имя файла <code class="text-xs">custom.blade.php</code> должно совпадать с полем
                <code class="text-xs">template</code> в таблице <code class="text-xs">news</code>.
                Можно создавать шаблоны для любых целей — «Отзывы», «Галерея», «Портфолио», «Контакты» и др. Если вам до сих
                пор непонятно - проанализируйте уже созданные и подключенные шаблоны. Они все унифицированы и писались
                однотипно.
            </p>
        </div>

        <div>
            <h2 class="font-semibold text-blue-700 text-lg">🛠 Полная инструкция по установке Ru-CMS через веб-интерфейс
            </h2>
            <ol class="list-decimal list-inside mt-2 text-sm text-gray-800 space-y-2">
                <li>
                    <strong>📁 Размещение проекта</strong><br>
                    Разместите содержимое папки <code class="text-xs">Ru-CMS-main</code> в директории, обслуживаемой
                    сервером:<br>
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs block mt-1">/var/www/html/Ru-CMS/</code><br>
                    Или в корневую папку домена на хостинге.
                </li>
                <li>
                    <strong>🌐 Настройка виртуального хоста (локально)</strong><br>
                    Для Apache добавьте в конфигурацию:
                    <pre class="bg-gray-100 p-3 rounded text-xs overflow-x-auto">
&lt;VirtualHost *:80&gt;
    ServerName ru-cms.local
    DocumentRoot /var/www/html/Ru-CMS/public

    &lt;Directory /var/www/html/Ru-CMS/public&gt;
        AllowOverride All
        Require all granted
    &lt;/Directory&gt;
&lt;/VirtualHost&gt;
            </pre>
                    И пропишите в <code class="text-xs">/etc/hosts</code>:
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs block mt-1">127.0.0.1 ru-cms.local</code>
                </li>
                <li>
                    <strong>📦 Установка зависимостей</strong><br>
                    В корне проекта выполните:
                    <pre class="bg-gray-100 p-3 rounded text-xs overflow-x-auto">
composer install
npm install
npm run build
            </pre>
                    Если нет Node.js — можно использовать уже скомпилированные стили из <code
                        class="text-xs">public/build</code>.
                </li>
                <li>
                    <strong>🗝️ Настройка файла .env</strong><br>
                    Создайте <code class="text-xs">.env</code> из <code class="text-xs">.env backup</code> и укажите
                    параметры БД:
                    <pre class="bg-gray-100 p-3 rounded text-xs overflow-x-auto">
APP_NAME="Ru CMS"
APP_URL=http://ваш-домен
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=имя_бд
DB_USERNAME=юзер
DB_PASSWORD=пароль
            </pre>
                    ⚠️ <strong>Не запускайте</strong> <code class="text-xs">php artisan migrate</code> — всё выполнит
                    веб-установщик.
                </li>
                <li>
                    <strong>🌐 Запуск установщика</strong><br>
                    Перейдите в браузере:
                    <code class="bg-gray-100 px-2 py-1 rounded text-xs block mt-1">http://ваш-домен/install</code><br>
                    Установщик проведёт вас по шагам:
                    <ul class="list-disc list-inside mt-1 ml-4 space-y-1">
                        <li>Проверка системных требований</li>
                        <li>Подключение к базе данных</li>
                        <li>Создание администратора</li>
                        <li>Финальная настройка</li>
                    </ul>
                </li>
                <li>
                    <strong>✅ После установки</strong><br>
                    Будет удалён флаг установки, установщик станет недоступен, вы попадёте в админку:
                    <a href="{{ url('/admin') }}" class="text-blue-600 hover:underline">/admin</a>.
                </li>
            </ol>

            <p class="mt-4 text-sm text-gray-600">
                🧼 Убедитесь, что папки <code class="text-xs">storage/</code> и <code class="text-xs">bootstrap/cache</code>
                доступны для записи:
            </p>
            <pre class="bg-gray-100 p-3 rounded text-xs overflow-x-auto">
chmod -R 775 storage bootstrap/cache
    </pre>
            <p class="text-sm text-gray-600">
                Файл <code class="text-xs">.htaccess</code> уже настроен и находится в папке <code
                    class="text-xs">public/</code>.
            </p>
        </div>

        {{-- 📚 База знаний --}}
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-6 mt-12 space-y-4 shadow-sm">
            <h3 class="text-lg font-semibold text-blue-700 flex items-center gap-2">
                📚 База знаний и документация
            </h3>
            <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                <li><a href="{{ url('/about') }}" class="text-blue-600 hover:underline">Что такое Ru-CMS и как она
                        работает?</a></li>
                <li><a href="{{ url('/faq') }}" class="text-blue-600 hover:underline">Настройка шаблонов, блоков и
                        категорий</a></li>
                <li><a href="{{ url('/contacts') }}" class="text-blue-600 hover:underline">Как получить помощь и
                        поддержку</a></li>
            </ul>
        </div>

        {{-- 🔙 Кнопка назад --}}
        <div class="text-center pt-10">
            <a href="{{ url('/') }}"
                class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow hover:scale-105 transition-all">
                <i class="fas fa-arrow-left mr-2"></i> На главную
            </a>
        </div>
    </div>
@endsection
