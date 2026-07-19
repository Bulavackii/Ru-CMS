@extends('layouts.frontend')

@section('title', 'Новости')

@section('content')
    <div class="my-12 max-w-screen-xl mx-auto px-4">
        <!-- VIEW: frontend/news/index -->

        <h2 class="text-3xl font-extrabold text-center mb-10 text-gray-800 dark:text-white tracking-tight flex items-center justify-center gap-3">
            🗞️ {{ $title ?? 'Последние новости' }}
        </h2>

        @if ($newsList->count())
            <div class="flex flex-wrap justify-center gap-8">
                @foreach ($newsList as $news)
                    @php
                        // ===== утилиты =====
                        $IMG_EXT = ['jpg','jpeg','png','gif','webp','bmp','svg','avif'];
                        $VID_EXT = ['mp4','webm','ogg','ogv','mov','m4v','mkv','avi','3gp','3g2'];

                        $extOf = function (?string $url): string {
                            if (!$url) return '';
                            $path = parse_url($url, PHP_URL_PATH) ?? '';
                            return strtolower(pathinfo($path, PATHINFO_EXTENSION));
                        };

                        // ===== cover абсолютным URL (нужен для poster) =====
                        $coverAbs = null;
                        if (!empty($news->cover)) {
                            $raw = (string) $news->cover;
                            $isHttp = (bool) preg_match('~^https?://~i', $raw);
                            $rel    = ltrim(preg_replace('~^storage/~','',$raw),'/');
                            $exists = $isHttp ? true : \Illuminate\Support\Facades\Storage::disk('public')->exists($rel);
                            if ($exists) $coverAbs = $isHttp ? $raw : asset('storage/'.$rel);
                        }

                        // ===== достаём видео из контента =====
                        $videoSrc = null;

                        if (!$videoSrc && preg_match('~<video[^>]*\bsrc\s*=\s*[\'"]([^\'">]+)[\'"]~i', $news->content, $m)) {
                            $videoSrc = $m[1];
                        }
                        if (!$videoSrc && preg_match_all('~<source[^>]*\bsrc\s*=\s*[\'"]([^\'">]+)[\'"][^>]*>~i', $news->content, $mm)) {
                            foreach ($mm[1] as $src) {
                                if (in_array($extOf($src), $VID_EXT, true)) { $videoSrc = $src; break; }
                            }
                        }
                        if (!$videoSrc && preg_match('~https?://[^\s"\']+\.(mp4|webm|ogg|ogv|mov|m4v|mkv|avi|3gp|3g2)(\?.*)?~i', $news->content, $m)) {
                            $videoSrc = $m[0];
                        }

                        // Если cover — это видео и в контенте не нашли, используем cover
                        if (!$videoSrc && $coverAbs && in_array($extOf($coverAbs), $VID_EXT, true)) {
                            $videoSrc = $coverAbs;
                        }

                        // ===== картинка (или заглушка) =====
                        $imageSrc = null;
                        if ($coverAbs && in_array($extOf($coverAbs), $IMG_EXT, true)) {
                            $imageSrc = $coverAbs;
                        } elseif (preg_match('~<img[^>]*\bsrc\s*=\s*[\'"]([^\'">]+)[\'"]~i', $news->content, $m)) {
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

                    <div class="news-card flex flex-col w-full max-w-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm hover:shadow-lg transition-all duration-300">
                        <div class="absolute -top-3 right-3 z-10 bg-white dark:bg-gray-800 border border-blue-600 text-blue-600 text-xs font-bold px-3 py-1 rounded-full shadow animate-pulse">
                            📰 NEWS
                        </div>

                        <div class="w-full h-44 overflow-hidden mb-4 rounded-xl border border-gray-200 relative">
                            @if ($isVideo)
                                <video controls preload="metadata" playsinline muted class="w-full h-full object-cover rounded-xl"
                                       @if($coverAbs && in_array($extOf($coverAbs), $IMG_EXT, true)) poster="{{ $coverAbs }}" @endif>
                                    <source src="{{ $videoSrc }}" type="{{ $vMime }}">
                                    Ваш браузер не поддерживает видео.
                                </video>
                            @else
                                <img src="{{ $imageSrc }}" alt="{{ $news->title }}" class="w-full h-full object-cover rounded-xl" loading="lazy" decoding="async">
                            @endif
                        </div>

                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2 leading-snug line-clamp-2 break-words">
                            <a href="{{ route('news.show', $news->slug) }}" class="hover:text-blue-600 transition">
                                {{ $news->title }}
                            </a>
                        </h3>

                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">
                            📅 {{ $news->created_at->format('d.m.Y') }}
                        </div>

                        <div class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                            🏷️
                            @forelse ($news->categories as $category)
                                <a href="{{ url('/?category=' . $category->id) }}" class="text-blue-600 hover:underline">
                                    {{ $category->title }}
                                </a>{{ !$loop->last ? ',' : '' }}
                            @empty
                                <span class="text-gray-400">Без категории</span>
                            @endforelse
                        </div>

                        <div class="text-sm text-gray-700 dark:text-gray-100 mb-4 leading-relaxed line-clamp-4 break-words">
                            {!! strip_tags($news->content) !!}
                        </div>

                        <a href="{{ route('news.show', $news->slug) }}"
                           aria-label="Читать новость {{ $news->title }}"
                           class="mt-auto text-sm text-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition shadow block">
                            Читать далее →
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="mt-12">
                {{ $newsList->withQueryString()->links('vendor.pagination.tailwind') }}
            </div>
        @else
            <p class="text-center text-gray-500 dark:text-gray-400 text-lg mt-10">Нет опубликованных новостей.</p>
        @endif
    </div>
@endsection
