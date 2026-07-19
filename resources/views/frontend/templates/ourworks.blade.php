@once
@push('styles')
<style>
  /* Русские переносы + аккуратное распределение строк */
  .card-title{
    word-break: normal;          /* не ломаем слова где попало */
    overflow-wrap: anywhere;     /* длинные куски всё-таки переносятся */
    hyphens: auto;               /* переносы по слогам */
    -webkit-hyphens: auto;
    text-wrap: balance;          /* красивый баланс строк в заголовке */
  }
  .card-teaser{
    word-break: normal;
    hyphens: auto;
    -webkit-hyphens: auto;
  }
</style>
@endpush
@endonce

<div class="my-12 max-w-screen-2xl mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16">
    {{-- Заголовок раздела с иконкой --}}
    <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 tracking-tight flex items-center justify-center gap-2 select-none">
        <i class="fas fa-briefcase text-blue-600"></i>
        {{ $title ?? 'Наши услуги' }}
    </h2>

    @php
        /**
         * Берём коллекцию элементов:
         * - если передали $ourworksList/$serviceList, используем её,
         * - иначе берём из $templates['ourworks'] (как на главной).
         */
        $newsList = $newsList
            ?? ($ourworksList ?? null)
            ?? ($serviceList ?? null)
            ?? ($templates['ourworks'] ?? collect());
    @endphp

    @if ($newsList->count())
        {{-- Сетка карточек --}}
        <div class="flex flex-wrap justify-center gap-8">
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
                    if (!$videoSrc && preg_match('~<video[^>]*\bsrc\s*=\s*[\'"]([^\'">]+)[\'"]~i', $news->content, $m)) {
                        $videoSrc = $m[1];
                    }
                    if (!$videoSrc && preg_match_all('~<source[^>]*\bsrc\s*=\s*[\'"]([^\'">]+)[\'"][^>]*>~i', $news->content, $mm)) {
                        foreach ($mm[0] as $i => $full) {
                            $src = $mm[1][$i] ?? null; if (!$src) continue;
                            $type = null;
                            if (preg_match('~\btype\s*=\s*[\'"]([^\'">]+)[\'"]~i', $full, $tt)) $type = strtolower($tt[1] ?? '');
                            if ($type ? str_starts_with($type, 'video/') : in_array($extOf($src), $VID_EXT, true)) { $videoSrc = $src; break; }
                        }
                    }
                    if (!$videoSrc && preg_match('~https?://[^\s"\']+\.(mp4|webm|ogg|ogv|mov|m4v|mkv|avi|3gp|3g2)(\?.*)?~i', $news->content, $m)) {
                        $videoSrc = $m[0];
                    }
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
                        $imageSrc = asset('images/no-image.png');
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

                {{-- Карточка услуги --}}
                <div class="news-card relative flex flex-col p-5 border border-gray-100 hover:border-gray-300 shadow-md hover:shadow-lg transition-all bg-white rounded-2xl max-w-xs w-full">
                    {{-- 🧰 Бейдж "УСЛУГА" --}}
                    <div class="absolute -top-3 right-3 z-10 bg-white border-2 border-blue-600 text-blue-600 text-xs font-bold px-3 py-1 rounded-full shadow-md select-none" title="Услуга">
                        🧰 УСЛУГА
                    </div>

                    {{-- Категории --}}
                    @if ($news->categories->count())
                        <div class="absolute top-3 left-3 z-10 flex flex-wrap gap-1">
                            @foreach ($news->categories as $category)
                                <a href="{{ url('/?category_' . ($news->template ?? 'ourworks') . '=' . $category->id) }}"
                                   class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded-full hover:underline select-none"
                                   title="{{ $category->title }}">
                                    {{ $category->title }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Обложка/видео --}}
                    <div class="w-full h-48 overflow-hidden mb-4 rounded-xl border border-gray-200 pt-6 relative">
                        @if ($isVideo)
                            <video class="w-full h-full object-cover rounded-xl" muted autoplay loop playsinline controls
                                   @if($coverAbs && in_array(pathinfo(parse_url($coverAbs, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION), $IMG_EXT, true)) poster="{{ $coverAbs }}" @endif>
                                <source src="{{ $videoSrc }}" type="{{ $vMime }}">
                                Ваш браузер не поддерживает видео.
                            </video>
                        @else
                            <img src="{{ $imageSrc }}" alt="{{ $news->title }}" class="w-full h-full object-cover rounded-xl" loading="lazy" />
                        @endif
                    </div>

                    {{-- Заголовок --}}
                    <h3 class="card-title text-xl font-semibold text-gray-900 mb-1 leading-tight break-words line-clamp-2">
                        <a href="{{ route('news.show', $news->slug) }}" class="hover:text-blue-600 transition" title="{{ $news->title }}">
                            {{ $news->title }}
                        </a>
                    </h3>

                    {{-- Дата (оставим, если используете как новости-услуги) --}}
                    <p class="text-sm text-gray-500 mb-2 flex items-center gap-1 select-none" title="Дата публикации">
                        <i class="far fa-calendar-alt"></i> {{ $news->created_at->format('d.m.Y') }}
                    </p>

                    {{-- Краткое описание --}}
                    <div class="card-teaser text-sm text-gray-600 mb-3 line-clamp-4 break-words" title="Кратко об услуге">
                        💬 {!! Str::limit(strip_tags($news->content), 220) !!}
                    </div>

                    {{-- Кнопка --}}
                    <a href="{{ route('news.show', $news->slug) }}"
                       class="mt-auto block text-center text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition shadow select-none"
                       aria-label="Подробнее об услуге {{ $news->title }}">
                        Подробнее →
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Пагинация --}}
        @if ($newsList->hasPages())
            <div class="mt-10 w-full flex коло items-center justify-center gap-2 select-none" aria-label="Пагинация услуг">
                <div class="text-sm text-gray-500">
                    Показано с <span class="font-semibold">{{ $newsList->firstItem() }}</span>
                    по <span class="font-semibold">{{ $newsList->lastItem() }}</span>
                    из <span class="font-semibold">{{ $newsList->total() }}</span> записей
                </div>
                <nav class="flex items-center space-x-2 rtl:space-x-reverse" role="navigation" aria-label="Навигация по страницам">
                    @if ($newsList->onFirstPage())
                        <span class="px-3 py-1.5 bg-gray-200 text-gray-500 rounded-md text-sm cursor-not-allowed"> ← Назад </span>
                    @else
                        <a href="{{ $newsList->previousPageUrl() }}" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-md text-sm transition" rel="prev"> ← Назад </a>
                    @endif

                    @foreach ($newsList->getUrlRange(1, $newsList->lastPage()) as $page => $url)
                        @if ($page == $newsList->currentPage())
                            <span class="px-3 py-1.5 bg-blue-600 text-white rounded-md text-sm font-semibold shadow">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-md text-sm transition">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if ($newsList->hasMorePages())
                        <a href="{{ $newsList->nextPageUrl() }}" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 rounded-md text-sm transition" rel="next"> Вперёд → </a>
                    @else
                        <span class="px-3 py-1.5 bg-gray-200 text-gray-500 rounded-md text-sm cursor-not-allowed"> Вперёд → </span>
                    @endif
                </nav>
            </div>
        @endif
    @else
        <p class="text-center text-gray-500 select-none">Пока нет опубликованных услуг.</p>
    @endif
</div>
