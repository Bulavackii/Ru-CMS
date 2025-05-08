<section class="w-full bg-white p-8 md:p-12 shadow rounded-2xl mb-12">
    {{-- Заголовок --}}
    <h2 class="text-3xl font-extrabold text-center text-gray-800 mb-10 tracking-tight">
        📞 Связаться с нами
    </h2>

    {{-- Контактная информация + Форма --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-10">
        {{-- Контакты --}}
        <div>
            <h3 class="text-xl font-semibold mb-6 text-gray-700">Наши контакты</h3>
            <ul class="text-gray-600 space-y-4 text-base leading-relaxed">
                <li><span class="font-semibold text-gray-800">📍 Адрес:</span> г. Москва, ул. Примерная, д. 123</li>
                <li><span class="font-semibold text-gray-800">📞 Телефон:</span>
                    <a href="tel:+74951234567" class="text-blue-600 hover:underline">+7 (495) 123-45-67</a>
                </li>
                <li><span class="font-semibold text-gray-800">✉️ Email:</span>
                    <a href="mailto:info@example.com" class="text-blue-600 hover:underline">info@example.com</a>
                </li>
                <li><span class="font-semibold text-gray-800">🕘 Время работы:</span> Пн–Пт, с 9:00 до 18:00</li>
            </ul>
        </div>

        {{-- Форма обратной связи --}}
        <div>
            <h3 class="text-xl font-semibold mb-6 text-gray-700">Обратная связь</h3>
            <form method="POST" action="#" class="space-y-5">
                @csrf
                <input type="text" name="name" placeholder="Ваше имя"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-none" required autofocus>

                <input type="email" name="email" placeholder="Ваш Email"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-none" required>

                <textarea name="message" rows="4" placeholder="Сообщение"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none" required></textarea>

                <button type="submit"
                    class="w-full bg-blue-600 text-white font-semibold py-2.5 rounded-lg hover:bg-blue-700 transition-all shadow">
                    ✉️ Отправить сообщение
                </button>
            </form>
        </div>
    </div>

    {{-- Встраиваемая карта --}}
    <div class="w-full h-64 rounded-xl overflow-hidden shadow">
        <iframe
            src="https://yandex.ru/map-widget/v1/?um=constructor%3A08f5e9a0b44d8f2c0f3b7e1ae591a4fd2b7c2a0b3d1c70e1b1c3e2c9dfdfeb96&amp;source=constructor"
            width="100%" height="100%" frameborder="0" style="border:0;" allowfullscreen></iframe>
    </div>
</section>
