<section class="mb-12">
    <h2 class="text-3xl font-bold mb-6 text-center">📞 Контакты</h2>

    <div class="grid gap-8 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($items as $news)
            <div class="bg-white border border-gray-200 rounded-2xl shadow hover:shadow-lg transition-all duration-300 p-6">
                <h3 class="text-xl font-semibold text-blue-600 mb-2">
                    {{ $news->title }}
                </h3>

                <div class="text-gray-700 leading-relaxed mb-4">
                    {!! $news->content !!}
                </div>

                <div class="text-sm text-gray-500">
                    Опубликовано: {{ $news->created_at->format('d.m.Y') }}
                </div>
            </div>
        @empty
            <p class="text-center text-gray-500 col-span-full">Контактная информация отсутствует.</p>
        @endforelse
    </div>
</section>
