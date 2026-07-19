<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="stylesheet" href="{{ local_font_css('inter') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- 🎨 CSS активной темы (собирается при "Применить") --}}
    @if(!empty($activeTheme?->config['css']))
        <style id="theme-css">{!! $activeTheme->config['css'] !!}</style>
    @endif
</head>

<body class="font-sans antialiased"
      style="
        background:  var(--color-bg, var(--colors-bg, #f8fafc));
        color:       var(--color-text, var(--colors-text, #1e293b));
        font-family: var(--font-base, 'Inter', sans-serif);
      ">

    <div class="min-h-screen flex flex-col">

        {{-- 🧩 Хедер-фрагмент (fallback на старый хедер, если фрагмента нет) --}}
        @php
            try {
                $headerHtml = \Modules\Visual\Support\FragmentRenderer::render(['slug' => 'site-header']);
            } catch (\Throwable $e) {
                $headerHtml = null;
            }
        @endphp

        @if(!empty($headerHtml) && strpos($headerHtml, 'fragment not found') === false)
            {!! $headerHtml !!}
        @else
            {{-- Fallback-хедер (минимальный) --}}
            <header class="shadow"
                    style="background-color: var(--color-header, var(--colors-header, #ffffff));
                           color:            var(--color-header-text, var(--colors-header-text, #111827));">
                <div class="max-w-screen-2xl mx-auto py-3 sm:py-4 px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 flex justify-between items-center">
                    <div class="font-semibold"> {{ config('app.name') }} </div>
                    <a href="{{ route('admin.search.index') }}" class="text-sm underline">Поиск</a>
                </div>
            </header>
        @endif

        {{-- Контент --}}
        <main class="flex-grow py-6 sm:py-8 md:py-10">
            <div class="container mx-auto px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16">
                {{ $slot }}
            </div>
        </main>

        {{-- 🧩 Футер-фрагмент (fallback на простой футер) --}}
        @php
            try {
                $footerHtml = \Modules\Visual\Support\FragmentRenderer::render(['slug' => 'site-footer']);
            } catch (\Throwable $e) {
                $footerHtml = null;
            }
        @endphp

        @if(!empty($footerHtml) && strpos($footerHtml, 'fragment not found') === false)
            {!! $footerHtml !!}
        @else
            <footer class="text-center text-sm py-4 border-t"
                    style="background-color: var(--color-footer, var(--colors-footer, #ffffff));
                           color:            var(--color-footer-text, var(--colors-footer-text, #6b7280));">
                &copy; {{ date('Y') }} {{ config('app.name') }}. Все права защищены.
            </footer>
        @endif
    </div>

    @stack('scripts')
</body>
</html>
