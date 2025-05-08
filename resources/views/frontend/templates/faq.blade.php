<div class="my-12 max-w-screen-xl mx-auto px-4">
    <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 tracking-tight">
        {{ $title ?? 'Часто задаваемые вопросы' }}
    </h2>

    @php
        $faqList = $templates['faq'] ?? collect();
    @endphp

    @if ($faqList->count())
        <div class="flex flex-wrap justify-center gap-8">
            @foreach ($faqList as $faq)
                <div class="faq-card relative flex flex-col border border-gray-100 hover:border-gray-200 p-6 shadow-md hover:shadow-xl transition-all bg-white rounded-2xl max-w-xs w-full">

                    {{-- Бейдж в углу --}}
                    <div class="faq-badge animate-pulse z-10 absolute -top-3 right-3 bg-white border-2 border-blue-600 text-blue-600 text-xs font-bold px-3 py-1 rounded-full shadow-md">
                        ❓ FAQ
                    </div>

                    {{-- Заголовок --}}
                    <div class="faq-question text-gray-900 text-lg font-semibold mb-3 leading-snug">
                        {{ $faq->title }}
                    </div>

                    {{-- Ответ --}}
                    <div class="faq-answer text-gray-700 text-sm leading-relaxed">
                        💬 {!! Str::limit(strip_tags($faq->content), 200) !!}
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-center text-gray-500">Нет вопросов.</p>
    @endif
</div>
