@foreach ($pages as $page)
    <article class="w-full mb-3 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm transition-all duration-300">

        {{-- 📰 Заголовок страницы --}}
        <header class="pt-6 pb-2 text-center px-[2px] sm:px-[2px] md:px-[2px] lg:px-6 xl:px-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white leading-tight mb-2"
                style="text-wrap:balance">
                @if (!empty($page->slug))
                    <a href="{{ route('frontend.pages.show', $page->slug) }}"
                       class="hover:text-blue-600 transition inline-flex items-center gap-1"
                       title="Открыть страницу">
                        🔗 {{ $page->title }}
                    </a>
                @else
                    {{ $page->title }}
                @endif
            </h2>

            {{-- 🏷️ Категории --}}
            @if ($page->categories->isNotEmpty())
                <div class="flex flex-wrap justify-center gap-2 text-xs sm:text-sm mb-3">
                    @foreach ($page->categories as $category)
                        <a href="{{ url('/?category=' . $category->id) }}"
                           class="inline-block bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100 px-3 py-1 rounded-full font-medium transition">
                            🏷️ {{ $category->title }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- 🔹 Декоративная линия --}}
            <div class="flex justify-center">
                <hr class="w-24 border-t-2 border-black dark:border-white opacity-30 mb-2">
            </div>
        </header>

        {{-- 📄 Контент страницы --}}
        <div class="page-content prose dark:prose-invert max-w-none px-[2px] sm:px-[2px] md:px-[2px] lg:px-6 xl:px-8 pb-6 text-gray-800 dark:text-gray-100">
            {!! $page->content !!}
        </div>
    </article>
@endforeach

@push('styles')
<style>
  /* Аккуратные переносы для русского текста */
  .page-content{
      /* нормальные слова не ломаем */
      word-break: normal;
      /* длинные куски (ссылки/артикулы) можно переносить где угодно */
      overflow-wrap: anywhere;    /* современный */
      word-wrap: break-word;      /* совместимость */
      /* расстановка переносов по словарю */
      hyphens: auto;
      -webkit-hyphens: auto;
      -ms-hyphens: auto;
      /* чуть красивее набор абзацев */
      text-wrap: pretty;
  }
  /* заголовки — балансируем строки */
  .page-content h1,.page-content h2,.page-content h3{ text-wrap: balance; }

  /* Медиа/встраиваемые блоки */
  .page-content img,
  .page-content iframe,
  .page-content video,
  .page-content embed,
  .page-content object{
      display:inline-block;max-width:100%;height:auto;border-radius:.75rem;margin:1rem auto
  }

  /* Поддержка float из редактора */
  .page-content img[style*="float:left"],
  .page-content iframe[style*="float:left"],
  .page-content video[style*="float:left"],
  .page-content embed[style*="float:left"],
  .page-content object[style*="float:left"],
  .page-content img[style*="float: left"],
  .page-content iframe[style*="float: left"],
  .page-content video[style*="float: left"],
  .page-content embed[style*="float: left"],
  .page-content object[style*="float: left"]{
      float:left;margin-right:1rem;margin-left:0
  }
  .page-content img[style*="float:right"],
  .page-content iframe[style*="float:right"],
  .page-content video[style*="float:right"],
  .page-content embed[style*="float:right"],
  .page-content object[style*="float:right"],
  .page-content img[style*="float: right"],
  .page-content iframe[style*="float: right"],
  .page-content video[style*="float: right"],
  .page-content embed[style*="float: right"],
  .page-content object[style*="float: right"]{
      float:right;margin-left:1rem;margin-right:0
  }
  .page-content:after{content:"";display:table;clear:both}

  /* Ссылки — дружелюбные переносы (особенно URL) */
  .page-content a{
      color:#2563eb;text-decoration:none;transition:.2s;
      word-break: break-all;        /* чтобы очень длинные URL не выпирали */
  }
  .dark .page-content a{color:#60a5fa}
  .page-content a:hover{color:#1d4ed8;text-decoration:underline}
</style>
@endpush
