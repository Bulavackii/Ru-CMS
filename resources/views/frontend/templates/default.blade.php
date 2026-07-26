<div class="my-8 sm:my-10 md:my-12 max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16">
    {{-- Заголовок раздела: градиентный бейдж-иконка + название + подзаголовок --}}
    <div class="mb-6 sm:mb-8 md:mb-10 flex items-center justify-center gap-3 select-none">
        <span class="fx-badge"><i class="fas fa-newspaper"></i></span>
        <div class="text-left">
            <h2 class="fx-section-title text-xl sm:text-2xl md:text-3xl leading-tight">{{ $title ?? 'Новости' }}</h2>
            <div class="fx-section-sub">{{ __('frontend.news.latest') }}</div>
        </div>
    </div>

    @if ($newsList->count())
        {{-- Контейнер карточек новостей: flex с переносом и отступами --}}
        <div class="flex flex-wrap justify-center gap-4 sm:gap-6 md:gap-8">
            @foreach ($newsList as $news)
                @php
                    // ==== утилиты ====
                    $IMG_EXT = ['jpg','jpeg','png','gif','webp','bmp','svg','avif'];
                    $VID_EXT = ['mp4','webm','ogg','ogv','mov','m4v','mkv','avi','3gp','3g2'];

                    $extOf = function (?string $url): string {
                        if (!$url) return '';
                        $path = parse_url($url, PHP_URL_PATH) ?? '';
                        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    };

                    // cover абсолютным URL (для poster)
                    $coverAbs = null;
                    if (!empty($news->cover)) {
                        $raw = (string) $news->cover;
                        $isHttp = (bool) preg_match('~^https?://~i', $raw);
                        $rel    = ltrim(preg_replace('~^storage/~','',$raw),'/');
                        $exists = $isHttp ? true : \Illuminate\Support\Facades\Storage::disk('public')->exists($rel);
                        if ($exists) $coverAbs = $isHttp ? $raw : asset('storage/'.$rel);
                    }

                    // достаём видео из контента
                    $videoSrc = null;

                    // <video src="...">
                    if (!$videoSrc && preg_match('~<video[^>]*\bsrc\s*=\s*[\'"]([^\'">]+)[\'"]~i', $news->content, $m)) {
                        $videoSrc = $m[1];
                    }
                    // <source src="..."> (берём первый видеотип или по расширению)
                    if (!$videoSrc && preg_match_all('~<source[^>]*\bsrc\s*=\s*[\'"]([^\'">]+)[\'"][^>]*>~i', $news->content, $mm)) {
                        foreach ($mm[0] as $i => $full) {
                            $src = $mm[1][$i] ?? null;
                            if (!$src) continue;
                            $type = null;
                            if (preg_match('~\btype\s*=\s*[\'"]([^\'">]+)[\'"]~i', $full, $tt)) {
                                $type = strtolower($tt[1] ?? '');
                            }
                            if ($type ? str_starts_with($type, 'video/') : in_array($extOf($src), $VID_EXT, true)) {
                                $videoSrc = $src; break;
                            }
                        }
                    }
                    // прямая ссылка на видео в тексте
                    if (!$videoSrc && preg_match('~https?://[^\s"\']+\.(mp4|webm|ogg|ogv|mov|m4v|mkv|avi|3gp|3g2)(\?.*)?~i', $news->content, $m)) {
                        $videoSrc = $m[0];
                    }
                    // если cover — видео и в контенте не нашли, берём его
                    if (!$videoSrc && $coverAbs && in_array($extOf($coverAbs), $VID_EXT, true)) {
                        $videoSrc = $coverAbs;
                    }

                    // картинка (или заглушка)
                    $imageSrc = null;
                    if ($coverAbs && in_array($extOf($coverAbs), $IMG_EXT, true)) {
                        $imageSrc = $coverAbs;
                    } elseif (preg_match('~<img[^>]+src=[\'"]([^\'">]+)[\'"]~i', $news->content, $m)) {
                        $imageSrc = $m[1];
                    } else {
                        $imageSrc = null; // нет картинки → покажем стеклянную заглушку .fx-noimg
                    }

                    $isVideo = (bool) $videoSrc;

                    // MIME для <source>
                    $mimeMap = [
                        'mp4'=>'video/mp4','m4v'=>'video/mp4',
                        'webm'=>'video/webm',
                        'ogg'=>'video/ogg','ogv'=>'video/ogg',
                        'mov'=>'video/quicktime',
                        'mkv'=>'video/x-matroska',
                        'avi'=>'video/x-msvideo',
                        '3gp'=>'video/3gpp','3g2'=>'video/3gpp2',
                    ];
                    $vExt  = $extOf($videoSrc);
                    $vMime = $mimeMap[$vExt] ?? 'video/mp4';
                @endphp

                {{-- Карточка новости (стеклянная, indigo-акцент) --}}
                <div class="news-card fx-card relative flex flex-col p-4 sm:p-5 w-full max-w-xs sm:max-w-sm">

                    {{-- Обложка/видео + чипы поверх неё --}}
                    <div class="relative w-full h-40 sm:h-44 md:h-48 lg:h-52 overflow-hidden mb-3 sm:mb-4 rounded-xl border border-gray-100 dark:border-gray-700">
                        @if ($isVideo)
                            <video class="w-full h-full object-cover" muted autoplay loop playsinline controls
                                   @if($coverAbs && in_array($extOf($coverAbs), $IMG_EXT, true)) poster="{{ $coverAbs }}" @endif>
                                <source src="{{ $videoSrc }}" type="{{ $vMime }}">
                                Ваш браузер не поддерживает видео.
                            </video>
                        @elseif ($imageSrc)
                            <img src="{{ $imageSrc }}" alt="{{ $news->title }}" class="w-full h-full object-cover" loading="lazy" />
                        @else
                            <div class="fx-noimg"><i class="fas fa-image fx-noimg-ico"></i><span>{{ __('frontend.news.no_image') }}</span></div>
                        @endif

                        {{-- Категории (слева сверху) --}}
                        @if ($news->categories->count())
                            <div class="absolute top-2.5 left-2.5 z-10 flex flex-wrap gap-1">
                                @foreach ($news->categories as $category)
                                    <a href="{{ url('/?category_' . $news->template . '=' . $category->id) }}"
                                       class="fx-chip hover:brightness-95 select-none" title="{{ $category->title }}">
                                        {{ $category->title }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        {{-- Бейдж «NEWS» (справа сверху) --}}
                        <div class="absolute top-2.5 right-2.5 z-10 fx-chip select-none" title="Новости">
                            <i class="fas fa-newspaper" style="font-size:.65rem"></i> NEWS
                        </div>
                    </div>

                    {{-- Заголовок новости с ссылкой --}}
                    <h3 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white mb-1 sm:mb-2 leading-tight break-words line-clamp-2">
                        <a href="{{ route('news.show', $news->slug) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition" title="{{ $news->title }}">
                            {{ $news->title }}
                        </a>
                    </h3>

                    {{-- Дата публикации --}}
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-2 sm:mb-3 flex items-center gap-1.5 select-none" title="Дата публикации">
                        <i class="far fa-calendar-alt fx-ico"></i> {{ $news->created_at->format('d.m.Y') }}
                    </p>

                    {{-- Краткое содержание --}}
                    <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-300 mb-3 sm:mb-4 line-clamp-3 sm:line-clamp-4 break-words" title="Превью новости">
                        {!! Str::limit(strip_tags($news->content), 200) !!}
                    </div>

                    {{-- Кнопка «Читать далее» --}}
                    <a href="{{ route('news.show', $news->slug) }}"
                       class="fx-btn mt-auto w-full py-2 sm:py-2.5 text-xs sm:text-sm select-none" aria-label="Читать подробнее новость {{ $news->title }}">
                        Читать далее <i class="fas fa-arrow-right" style="font-size:.65rem"></i>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Пагинация --}}
        @if ($newsList->hasPages())
            <div class="mt-10 w-full flex flex-col items-center justify-center gap-2 select-none" aria-label="Пагинация новостей">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Показано с <span class="font-semibold">{{ $newsList->firstItem() }}</span>
                    по <span class="font-semibold">{{ $newsList->lastItem() }}</span>
                    из <span class="font-semibold">{{ $newsList->total() }}</span> записей
                </div>
                <nav class="flex items-center space-x-2 rtl:space-x-reverse" role="navigation" aria-label="Навигация по страницам">
                    @if ($newsList->onFirstPage())
                        <span class="px-3 py-1.5 bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-md text-sm cursor-not-allowed"> ← {{ __('frontend.news.prev') }} </span>
                    @else
                        <a href="{{ $newsList->previousPageUrl() }}" class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 rounded-md text-sm transition" rel="prev"> ← {{ __('frontend.news.prev') }} </a>
                    @endif

                    @foreach ($newsList->getUrlRange(1, $newsList->lastPage()) as $page => $url)
                        @if ($page == $newsList->currentPage())
                            <span class="px-3 py-1.5 text-white rounded-md text-sm font-semibold shadow" style="background:var(--fx-grad)">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 rounded-md text-sm transition">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if ($newsList->hasMorePages())
                        <a href="{{ $newsList->nextPageUrl() }}" class="px-3 py-1.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-indigo-400 hover:text-indigo-600 rounded-md text-sm transition" rel="next"> {{ __('frontend.news.next') }} → </a>
                    @else
                        <span class="px-3 py-1.5 bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-md text-sm cursor-not-allowed"> {{ __('frontend.news.next') }} → </span>
                    @endif
                </nav>
            </div>
        @endif
    @else
        {{-- Сообщение, если новостей нет --}}
        <p class="text-center text-gray-500 dark:text-gray-400 select-none">{{ __('frontend.news.empty') }}</p>
    @endif
</div>
