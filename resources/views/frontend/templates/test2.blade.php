<section class="w-full bg-white p-8 md:p-12 shadow rounded-lg mb-12">
    {{-- Заголовок --}}
    <h2 class="text-3xl font-bold text-center text-gray-800 mb-8">📞 Связаться с нами</h2>

    {{-- Инфо --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
        <div>
            <h3 class="text-xl font-semibold mb-4 text-gray-700">Наши контакты</h3>
            <ul class="text-gray-600 space-y-2">
                <li><strong>Адрес:</strong> г. Москва, ул. Примерная, д. 123</li>
                <li><strong>Телефон:</strong> <a href="tel:+74951234567" class="text-blue-600 hover:underline">+7 (495) 123-45-67</a></li>
                <li><strong>Email:</strong> <a href="mailto:info@example.com" class="text-blue-600 hover:underline">info@example.com</a></li>
                <li><strong>Время работы:</strong> Пн–Пт, с 9:00 до 18:00</li>
            </ul>
        </div>

        {{-- Обратная связь --}}
        <div>
            <h3 class="text-xl font-semibold mb-4 text-gray-700">Обратная связь</h3>
            <form method="POST" action="#" class="space-y-4">
                @csrf
                <input type="text" name="name" placeholder="Ваше имя" class="w-full border rounded px-4 py-2" required>
                <input type="email" name="email" placeholder="Ваш Email" class="w-full border rounded px-4 py-2" required>
                <textarea name="message" rows="4" placeholder="Сообщение" class="w-full border rounded px-4 py-2" required></textarea>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    Отправить
                </button>
            </form>
        </div>
    </div>

    {{-- Карта --}}
    <div class="w-full h-64">
        <iframe
            src="https://yandex.ru/map-widget/v1/?um=constructor%3A08f5e9a0b44d8f2c0f3b7e1ae591a4fd2b7c2a0b3d1c70e1b1c3e2c9dfdfeb96&amp;source=constructor"
            width="100%" height="100%" frameborder="0"
            style="border: none;"></iframe>
    </div>
</section>
