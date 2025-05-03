<section class="mb-12">
    <h2 class="text-3xl font-bold mb-6 text-center">🖼️ Галерея</h2>

    <div class="grid gap-6 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse ($items as $news)
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden shadow hover:shadow-lg transition-all duration-300">
                @if ($news->cover)
                    <a href="{{ route('news.show', $news->slug) }}" target="_blank">
                        <img src="{{ asset('storage/' . $news->cover) }}" alt="{{ $news->title }}" class="w-full h-48 object-cover">
                    </a>
                @endif

                <div class="p-4">
                    <h3 class="text-lg font-semibold mb-1 text-gray-800">
                        {{ $news->title }}
                    </h3>
                    <div class="text-sm text-gray-600 mb-2">
                        {{ \Illuminate\Support\Str::limit(strip_tags($news->content), 80) }}
                    </div>
                    <a href="{{ route('news.show', $news->slug) }}" class="text-blue-500 text-sm hover:underline">
                        Смотреть →
                    </a>
                </div>
            </div>
        @empty
            <p class="text-center text-gray-500 col-span-full">Галерея пуста.</p>
        @endforelse
    </div>
</section>
