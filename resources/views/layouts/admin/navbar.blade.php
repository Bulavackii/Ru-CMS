<nav class="bg-gray-800 text-white p-4">
    <div class="container mx-auto flex justify-between items-center">
        <div class="flex gap-4">
            <a href="/" class="font-bold hover:underline">🏠 На сайт</a>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="hover:underline">🚪 Выйти</button>
        </form>
    </div>
</nav>
