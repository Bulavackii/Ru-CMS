📘 Инструкция: как добавить кастомный шаблон
✅ 1. Создай Blade-шаблон
📁 Перейди в папку:
resources/views/frontend/templates

📝 Создай файл с именем шаблона (например, products.blade.php):

📄 products.blade.php → это и будет твой кастомный шаблон

✍️ 2. Вставь в него нужную структуру
📦 Пример для “Товары” (красивые карточки):

<div class="mb-10">
    <h2 class="text-3xl font-bold mb-6 text-center">Товары</h2>
    <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($newsList as $news)
            <div class="bg-white shadow rounded-lg overflow-hidden flex flex-col">
                <img src="{{ asset('storage/' . $news->cover) }}" class="h-48 w-full object-cover" alt="">
                <div class="p-4 flex flex-col flex-grow">
                    <h3 class="text-lg font-bold mb-1">{{ $news->title }}</h3>
                    <p class="text-gray-500 text-sm mb-2">{{ $news->created_at->format('d.m.Y') }}</p>
                    <p class="text-gray-700 text-sm flex-grow">{{ Str::limit(strip_tags($news->content), 100) }}</p>
                    <a href="{{ route('news.show', $news->slug) }}"
                       class="mt-4 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded text-center">
                        Подробнее →
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
📌 Название файла = products
📌 Выводится в блоке @include('frontend.templates.products')

📤 3. Назначь этот шаблон при создании новости
🔧 Перейди в админку → “Создать новость”
📝 В поле “Шаблон” выбери Товары или products

🎉 Этот шаблон появится автоматически, потому что ты его создал как .blade.php!

💡 4. Где CMS его подхватывает?
✔️ Laravel-контроллер читает шаблоны с помощью кода:

$templateFiles = File::files(resource_path('views/frontend/templates'));
✔️ Названия шаблонов подставляются в выпадающий список в админке через @foreach($templates as ...)

✔️ При отображении главной страницы (/) шаблон используется через:


@include("frontend.templates.$key")
🚀 Готово!
После этого:

✅ шаблон виден в админке
✅ можно создать новость с этим шаблоном
✅ CMS сама подгрузит и отобразит блок на главной 🎯


 5. Не забудь добавить в routes->web.php 

  // Список шаблонов
    $allTemplates = ['default', 'products', 'contacts', 'gallery', 'test', 'slideshow', 'test2', 'example'];
