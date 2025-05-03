<section class="mb-12">
    <h2 class="text-3xl font-bold mb-6 text-center">🛒 Наши товары</h2>

    <div class="grid gap-8 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($items as $news)
            <div class="bg-white border border-gray-200 rounded-2xl shadow hover:shadow-lg transition-all duration-300 overflow-hidden">
                @if ($news->cover)
                    <a href="{{ route('news.show', $news->slug) }}">
                        <img src="{{ asset('storage/' . $news->cover) }}" alt="{{ $news->title }}" class="w-full h-48 object-cover">
                    </a>
                @endif

                <div class="p-4 flex flex-col justify-between h-full">
                    <div>
                        {{-- Категории --}}
                        @if ($news->categories->isNotEmpty())
                            <div class="mb-2 text-xs text-gray-500 space-x-2">
                                @foreach ($news->categories as $cat)
                                    <a href="{{ route('news.index', ['category' => $cat->id]) }}"
                                       class="inline-block bg-gray-100 hover:bg-gray-200 px-2 py-1 rounded">
                                        {{ $cat->title }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        {{-- Заголовок --}}
                        <h3 class="text-lg font-semibold mb-2">
                            <a href="{{ route('news.show', $news->slug) }}" class="hover:underline text-blue-600">
                                {{ $news->title }}
                            </a>
                        </h3>

                        {{-- Описание --}}
                        <div class="text-sm text-gray-700 mb-4 leading-relaxed">
                            {!! \Illuminate\Support\Str::of(strip_tags($news->content))->limit(120) !!}
                        </div>
                    </div>

                    {{-- Подробнее + дата --}}
                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span>{{ $news->created_at->format('d.m.Y') }}</span>
                        <a href="{{ route('news.show', $news->slug) }}" class="text-blue-500 hover:underline">
                            Подробнее →
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center text-gray-500 col-span-full">Товары не найдены.</p>
        @endforelse
    </div>
</section>
