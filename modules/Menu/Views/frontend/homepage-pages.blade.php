{{--
    Блок «Страницы» на главной. Выводит страницы, у которых включена галочка
    «Показать на главной» (порядок — по homepage_order).

    Показываем КАРТОЧКИ с выдержкой, а не полный контент: страниц может быть
    несколько, и их полный текст раньше растягивал главную на несколько экранов.
    Полный материал открывается по кнопке. Стиль — общий фронтовый (.fx-*).
--}}
@php use Illuminate\Support\Str; @endphp

<section class="my-8 sm:my-10 md:my-12 max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16">
    {{-- Заголовок секции: градиентный бейдж + название --}}
    <div class="mb-6 sm:mb-8 flex justify-center select-none">
        <div class="fx-section-head">
            <span class="fx-badge"><i class="fas fa-file-lines"></i></span>
            <div class="text-left">
                <h2 class="fx-section-title text-xl sm:text-2xl md:text-3xl leading-tight">Полезное</h2>
                <div class="fx-section-sub">Материалы о проекте и работе с системой</div>
            </div>
        </div>
    </div>

    <div class="grid gap-4 sm:gap-6 md:gap-8 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($pages as $page)
            @php
                $excerpt = Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $page->content))), 180);
            @endphp

            <article class="fx-card flex flex-col p-5 sm:p-6">
                <h3 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white leading-tight mb-2 break-words">
                    @if (!empty($page->slug))
                        <a href="{{ route('frontend.pages.show', $page->slug) }}"
                           class="hover:text-indigo-600 dark:hover:text-indigo-400 transition" title="Открыть страницу">
                            {{ $page->t('title') }}
                        </a>
                    @else
                        {{ $page->t('title') }}
                    @endif
                </h3>

                @if ($page->categories->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-1.5 mb-3">
                        @foreach ($page->categories as $category)
                            <a href="{{ url('/?category=' . $category->id) }}" class="fx-chip hover:brightness-95">
                                {{ $category->t('title') }}
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($excerpt !== '')
                    <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-4">{{ $excerpt }}</p>
                @endif

                @if (!empty($page->slug))
                    <a href="{{ route('frontend.pages.show', $page->slug) }}"
                       class="fx-btn mt-auto w-full py-2 sm:py-2.5 text-xs sm:text-sm">
                        Подробнее <i class="fas fa-arrow-right" style="font-size:.65rem"></i>
                    </a>
                @endif
            </article>
        @endforeach
    </div>
</section>
