# 🛠️ Инструкция: как добавить кастомный шаблон в Nexum Core

> Эта инструкция позволит тебе создать собственный шаблон и подключить его к системе,  
> чтобы он отображался и в админке, и на клиентском сайте.

---

## ✅ 1. Создайте файл шаблона

📁 **Путь:** `resources/views/frontend/templates/test.blade.php`

📄 **Пример содержимого (test.blade.php):**
```blade
<section class="w-full bg-white p-8 md:p-12 shadow rounded-2xl mb-12">
    <h2 class="text-3xl font-extrabold text-center text-gray-800 mb-10">📞 Связаться с нами</h2>
    <p class="text-center text-gray-600">Контактная форма и карта</p>
</section>
```

---

## ✅ 2. Проверь, что шаблон читается системой

🔧 В `NewsController.php` уже реализован метод `loadTemplates()`:

```php
private function loadTemplates(): array
{
    $templates = ['default' => 'Новости'];

    $customLabels = [
        'products'  => 'Товары',
        'contacts'  => 'Контакты',
        'faq'       => 'Вопросы',
        'reviews'   => 'Отзывы',
        'slideshow' => 'Слайдшоу',
        'test'      => 'Тест', // 👈 Добавь здесь свое название
    ];

    $templatePath = resource_path('views/frontend/templates');

    if (\File::exists($templatePath)) {
        foreach (\File::files($templatePath) as $file) {
            $key = basename($file->getFilename(), '.blade.php');
            $templates[$key] = $customLabels[$key] ?? ucfirst($key);
        }
    }

    return $templates;
}
```

🧠 Этот метод автоматически подхватит все `.blade.php` файлы из папки `templates`.

---

## ✅ 3. Шаблон появится при создании новости

🖊️ В админке `/admin/news/create` в поле **Шаблон** появится твой `test` с названием **"Тест"** — он подтягивается из метода `loadTemplates()`.

---

## ✅ 4. Шаблон будет отображаться на клиентской части

📄 В `routes/web.php` уже есть логика загрузки шаблонов:

```php
$templateKeys = [
    'default', 'products', 'reviews', 'faq', 'gallery', 'slideshow', 'test'
];
```

📦 Под каждую новость с этим шаблоном CMS подтянет записи в массив `$templates['test']`.

---

## ✅ 5. Вывод на главной (home.blade.php)

📂 В `resources/views/frontend/home.blade.php` используется:
```blade
@foreach($templates as $key => $items)
    @if(View::exists("frontend.templates.$key"))
        @include("frontend.templates.$key", ['items' => $items])
    @endif
@endforeach
```

✨ Это магический цикл — он сам подставит и выведет ваш шаблон с нужными данными.

---

✅ **Готово! Теперь ты можешь легко создавать и использовать собственные шаблоны!**
