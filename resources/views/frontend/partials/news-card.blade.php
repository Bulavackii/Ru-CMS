@php
    use Illuminate\Support\Str;

    // Какие расширения считаем картинками/видео
    $IMG = ['jpg','jpeg','png','gif','webp','bmp','svg','avif'];
    $VID = ['mp4','webm','ogg','ogv','mov','m4v','mkv','avi','3gp','3g2'];

    $extOf = function (?string $url): string {
        if (!$url) return '';
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    };

    // === cover -> абсолютный URL ===
    $coverAbs = null;
    if (!empty($news->cover)) {
        $raw = (string)$news->cover;                         // может быть '/uploads/x.mp4' или 'storage/uploads/x.mp4' или http...
        $isHttp = (bool)preg_match('~^https?://~i', $raw);
        $rel    = ltrim(preg_replace('~^storage/~','', $raw), '/');
        $exists = $isHttp ? true : \Illuminate\Support\Facades\Storage::disk('public')->exists($rel);
        if ($exists) {
            $coverAbs = $isHttp ? $raw : asset('storage/'.$rel);
        }
    }

    // === достаём видео из контента ===
    $videoSrc = null;

    // <video src="...">
    if (!$videoSrc && preg_match('~<video[^>]*\bsrc\s*=\s*[\'"]([^\'">]+)[\'"]~i', $news->content, $m)) {
        $videoSrc = $m[1];
    }
    // <source src="...">
    if (!$videoSrc && preg_match_all('~<source[^>]*\bsrc\s*=\s*[\'"]([^\'">]+)[\'"][^>]*>~i', $news->content, $mm)) {
        foreach ($mm[1] as $src) {
            if (in_array($extOf($src), $VID, true)) { $videoSrc = $src; break; }
        }
    }
    // голая ссылка на видео
    if (!$videoSrc && preg_match('~https?://[^\s"\']+\.(mp4|webm|ogg|ogv|mov|m4v|mkv|avi|3gp|3g2)(\?.*)?~i', $news->content, $m)) {
        $videoSrc = $m[0];
    }
    // cover сам по себе видео — используем его как превью
    if (!$videoSrc && $coverAbs && in_array($extOf($coverAbs), $VID, true)) {
        $videoSrc = $coverAbs;
    }

    // === картинка (poster) ===
    $imageSrc = null;
    if ($coverAbs && in_array($extOf($coverAbs), $IMG, true)) {
        $imageSrc = $coverAbs;
    } elseif (preg_match('~<img[^>]*\bsrc\s*=\s*[\'"]([^\'">]+)[\'"]~i', $news->content, $m)) {
        $imageSrc = $m[1];
    } else {
        $imageSrc = asset('images/no-image.png');
    }

    $isVideo = (bool)$videoSrc;

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
    $vMime = $mimeMap[$extOf($videoSrc)] ?? 'video/mp4';
@endphp

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md dark:shadow-gray-900/50 hover:shadow-lg dark:hover:shadow-gray-900/70 transition flex flex-col w-full h-full overflow-hidden border border-gray-200 dark:border-gray-700">
    <div class="w-full h-40 sm:h-44 md:h-48 lg:h-52 xl:h-56 bg-gray-100 dark:bg-gray-700 rounded-t-md overflow-hidden flex items-center justify-center">
        @if ($isVideo)
            <video
                controls
                preload="metadata"
                playsinline
                muted
                class="w-full h-full object-cover"
                @if($imageSrc) poster="{{ $imageSrc }}" @endif
            >
                <source src="{{ $videoSrc }}" type="{{ $vMime }}">
                Ваш браузер не поддерживает видео.
            </video>
        @else
            <img src="{{ $imageSrc }}" alt="{{ $news->title }}" class="w-full h-full object-cover">
        @endif
    </div>

    <div class="flex flex-col flex-grow p-3 sm:p-4 md:p-5 overflow-hidden">
        <h2 class="text-base sm:text-lg md:text-xl font-semibold mb-2 min-h-[3rem] overflow-hidden break-words">
            <a href="{{ route('news.show', $news->slug) }}" class="text-blue-600 dark:text-blue-400 hover:underline line-clamp-2 block w-full transition-colors" title="{{ $news->title }}">
                {{ $news->title }}
            </a>
        </h2>

        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-1">{{ optional($news->created_at)->format('d.m.Y') }}</p>

        <p class="text-gray-600 dark:text-gray-300 text-xs sm:text-sm mb-2 break-words">
            Категории:
            @forelse ($news->categories as $category)
                <a href="{{ url('/?category=' . $category->id) }}" class="text-blue-600 hover:underline">{{ $category->title }}</a>@if (!$loop->last),@endif
            @empty
                <span class="text-gray-400">Без категории</span>
            @endforelse
        </p>

        <div class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 mb-3 sm:mb-4 overflow-hidden relative flex-grow max-h-24 sm:max-h-28 md:max-h-32 leading-relaxed break-words">
            <div class="absolute bottom-0 left-0 w-full h-4 sm:h-6 bg-gradient-to-t from-white dark:from-gray-800 to-transparent pointer-events-none"></div>
            {!! Str::limit(strip_tags($news->content), 200) !!}
        </div>

        <a href="{{ route('news.show', $news->slug) }}" class="mt-auto text-blue-600 dark:text-blue-400 hover:underline text-xs sm:text-sm font-medium transition-colors">
            Читать далее →
        </a>
    </div>
</div>
