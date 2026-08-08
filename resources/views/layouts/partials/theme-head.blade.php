{{--
    Оформление из активной темы: шрифт, иконки и CSS-переменные.

    Вынесено отдельным партиалом, потому что нужно не только сайту: экраны
    входа, регистрации и восстановления пароля живут на своём лейауте и до
    этого не знали о теме вовсе — они были прибиты к синему цвету и системному
    шрифту, и смена оформления проекта их не касалась.

    Второй копии этих вычислений быть не должно: набор токенов у темы растёт,
    и разошедшиеся копии дали бы сайт и вход в разном оформлении.

    Ожидает переменную $pageTheme (модель темы или null). Без темы отдаёт
    значения по умолчанию — страница выглядит как раньше.
--}}
@php
    $tokens = $pageTheme->tokens ?? [];
    $config = $pageTheme->config ?? [];

    $fontBase = data_get($tokens, 'font.base', '-apple-system, BlinkMacSystemFont, Inter, system-ui, sans-serif');
    $radiusMd = data_get($tokens, 'radius.md', '12px');

    $cBg      = data_get($tokens, 'colors.bg', '#ffffff');
    $cText    = data_get($tokens, 'colors.text', '#111827');
    $cPrimary = data_get($tokens, 'colors.primary', '#2563eb');
    $cAccent  = data_get($tokens, 'colors.accent', '#10b981');
    $cHeader  = data_get($tokens, 'colors.header', '#ffffff');
    $cFooter  = data_get($tokens, 'colors.footer', '#ffffff');

    $bgImage =
        data_get($config, 'background_url') ??
        (data_get($config, 'bg_url') ??
            (data_get($config, 'pattern_url') ?? (data_get($config, 'bg_image') ?? null)));

    $iconMode = data_get($config, 'icon_mode', 'lucide');

    $fontProvider = data_get($config, 'font_provider'); // 'local' | 'google' | 'bunny' | null
    $fontName = trim((string) data_get($config, 'font_name', ''));

    $localFontSlug = null;
    if ($fontProvider === 'local' && $fontName !== '') {
        $slug = \Illuminate\Support\Str::slug($fontName);
        $localFontSlug = array_key_exists($slug, LOCAL_FONTS) ? $slug : null;
    }

    $iconAsset = theme_icon_asset($iconMode);
@endphp

{{-- Шрифт: локальный (по умолчанию — Inter), без обращений к внешним CDN --}}
@if ($localFontSlug)
    <link rel="stylesheet" href="{{ local_font_css($localFontSlug) }}">
@elseif ($fontProvider === 'google' && $fontName !== '')
    <link href="https://fonts.googleapis.com/css2?family={{ urlencode($fontName) }}:wght@400;500;600;700&display=swap"
          rel="stylesheet">
@elseif($fontProvider === 'bunny' && $fontName !== '')
    <link href="https://fonts.bunny.net/css?family={{ urlencode(str_replace(' ', '-', $fontName)) }}:400,500,600,700"
          rel="stylesheet">
@else
    <link rel="stylesheet" href="{{ local_font_css('inter') }}">
@endif

{{-- Иконки по режиму (локальные) --}}
@if($iconAsset)
    @if($iconMode === 'lucide')
        <script src="{{ $iconAsset }}"></script>
    @else
        <link rel="stylesheet" href="{{ $iconAsset }}">
    @endif
@endif

<style id="theme-vars">
    :root {
        --font-base: {{ $fontBase }};
        --radius-md: {{ $radiusMd }};
        --color-bg: {{ $cBg }};
        --color-text: {{ $cText }};
        --color-primary: {{ $cPrimary }};
        --color-accent: {{ $cAccent }};
        --color-header: {{ $cHeader }};
        --color-footer: {{ $cFooter }};
        --bg-image: url('{{ $bgImage ?: asset('images/fon.svg') }}');
    }
</style>
