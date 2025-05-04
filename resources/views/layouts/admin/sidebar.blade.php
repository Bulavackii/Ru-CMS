<aside class="w-64 bg-white h-screen shadow-md">
    <div class="p-6 font-bold text-lg border-b">RuShop Admin</div>
    <nav class="mt-4 space-y-2">
        <a href="/admin/modules" class="block px-4 py-2 hover:bg-gray-100">Модули</a>
        <a href="/admin/users" class="block px-4 py-2 hover:bg-gray-100">Пользователи</a>
        <a href="/admin/search" class="block px-4 py-2 hover:bg-gray-100">Поиск</a>
        <a href="{{ route('admin.news.index') }}" class="block px-4 py-2 hover:bg-gray-100">Новости</a>
        <a href="{{ route('admin.categories.index') }}" class="block px-4 py-2 hover:bg-gray-100">Категории</a>
        <a href="{{ route('admin.slideshow.index') }}" class="block px-4 py-2 hover:bg-gray-100">Слайдшоу</a> 
    </nav>
</aside>
