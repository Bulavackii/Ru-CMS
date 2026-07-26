{{--
    Страница модуля «Каптча» в панели.

    26.07.2026. Вьюха существовала с самого начала, но АДМИНСКОГО МАРШРУТА к
    ней не было вовсе (в Routes/web.php жили только api/captcha/*) — открыть
    её было нельзя ни по ссылке, ни по прямому адресу. Заведён маршрут
    admin.captcha.index и метод CaptchaController::admin().

    Второе: примеры кода лежали в обычных <pre> — и Blade их ВЫПОЛНЯЛ. Вызов
    хелпера каптчи в «памятке» реально генерировал каптчу, а директива CSRF
    подставляла скрытое поле вместо того, чтобы показать себя текстом. Blade
    не знает про <pre> — для него это такой же шаблон, как всё остальное.
    Примеры обёрнуты в директиву, отключающую компиляцию блока. Её имя, как и
    имена директив в примерах, нельзя писать здесь текстом: Blade компилирует
    и содержимое комментариев тоже (см. CLAUDE.md, «Архитектурные грабли»).
--}}
@extends('layouts.admin')

@section('title', 'Каптча')

@section('content')
@php
    $typeCards = [
        ['key' => 'image',    'title' => 'Картинка',   'desc' => 'Классический код на картинке с помехами', 'icon' => 'fa-image'],
        ['key' => 'slider',   'title' => 'Слайдер',    'desc' => 'Перетащить ползунок до конца дорожки',    'icon' => 'fa-arrows-left-right'],
        ['key' => 'math',     'title' => 'Пример',     'desc' => 'Простое арифметическое выражение',        'icon' => 'fa-calculator'],
        ['key' => 'question', 'title' => 'Вопрос',     'desc' => 'Вопрос со свободным ответом',             'icon' => 'fa-circle-question'],
    ];
@endphp

<div class="max-w-screen-2xl mx-auto">

    {{-- Шапка раздела --}}
    <div class="admin-card mb-5">
        <div class="admin-accent-bar" aria-hidden="true"></div>
        <div class="p-5 flex flex-wrap items-center gap-4">
            <span class="admin-icon-badge" aria-hidden="true"><i class="fas fa-shield-halved"></i></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Каптча</h1>
                <p class="text-sm text-gray-500">Защита форм от автоматических отправок. Встраивается в любую форму сайта.</p>
            </div>

            {{-- Настоящее состояние модуля, а не литерал во вьюхе --}}
            <span class="cap-state {{ $enabled ? 'is-on' : 'is-off' }}">
                <span class="cap-dot" aria-hidden="true"></span>
                {{ $enabled ? 'Включена' : 'Выключена' }}
            </span>
        </div>
    </div>

    @unless($enabled)
        <div class="admin-hint p-4 mb-5 text-sm">
            Модуль выключен: <span class="font-mono">CAPTCHA_ENABLED=false</span> в <span class="font-mono">.env</span>.
            Правило валидации <span class="font-mono">captcha</span> и функция <span class="font-mono">captcha_img()</span>
            продолжат работать, но проверка пропустит любое значение.
        </div>
    @endunless

    <div class="grid gap-5 lg:grid-cols-2">

        {{-- Типы --}}
        <section class="admin-card p-5">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">Типы каптчи</h2>

            <ul class="space-y-2">
                @foreach($typeCards as $type)
                    <li class="cap-type {{ $type['key'] === $defaultType ? 'is-default' : '' }}">
                        <span class="cap-type-ico"><i class="fas {{ $type['icon'] }}"></i></span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <strong class="text-gray-900 dark:text-white">{{ $type['title'] }}</strong>
                                <code class="cap-code">{{ $type['key'] }}</code>
                                @if($type['key'] === $defaultType)
                                    <span class="cap-badge">по умолчанию</span>
                                @endif
                            </span>
                            <span class="block text-xs text-gray-500 mt-0.5">{{ $type['desc'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>

            @if(!empty($types))
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mt-5 mb-2">Параметры из конфига</h3>
                <div class="cap-params">
                    @foreach($types as $name => $params)
                        <div class="cap-param-row">
                            <code class="cap-code">{{ $name }}</code>
                            <span class="text-xs text-gray-500">
                                @foreach($params as $key => $value){{ $key }}: <strong>{{ $value }}</strong>@if(!$loop->last), @endif @endforeach
                            </span>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-2">
                    Меняются в <span class="font-mono">modules/Captcha/Config/captcha.php</span>
                </p>
            @endif
        </section>

        {{-- Живая проверка --}}
        <section class="admin-card p-5">
            <div class="flex items-center justify-between gap-3 mb-3">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400">Как это выглядит</h2>
                <button type="button" onclick="capLoadDemos()" class="cap-btn">
                    <i class="fas fa-rotate"></i> Обновить
                </button>
            </div>

            <p class="text-xs text-gray-500 mb-3">
                Живые примеры с рабочего эндпоинта — если блок пуст, каптча этого типа не отдаётся.
            </p>

            <div class="space-y-3">
                @foreach($typeCards as $type)
                    <div class="cap-demo">
                        <span class="cap-demo-label">{{ $type['title'] }}</span>
                        <div class="cap-demo-box" id="cap-demo-{{ $type['key'] }}">
                            <span class="text-xs text-gray-400">загрузка…</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- Памятка. Примеры кода обёрнуты так, чтобы Blade их не компилировал:
         иначе он выполняет их, а не показывает (так и было до 26.07.2026). --}}
    <section class="admin-card p-5 mt-5">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">Как встроить</h2>

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <h3 class="cap-h3">1. В шаблоне формы</h3>
                <pre class="cap-pre">@verbatim
<form method="POST" action="/feedback">
    @csrf
    {!! captcha_img('image') !!}
    <input type="text" name="captcha" required>
    <button type="submit">Отправить</button>
</form>
@endverbatim</pre>
            </div>

            <div>
                <h3 class="cap-h3">2. Проверка в контроллере</h3>
                <pre class="cap-pre">@verbatim
$request->validate([
    'captcha' => 'required|captcha:image',
]);

// либо вручную
if (! app('captcha')->verify($request->captcha, 'image')) {
    return back()->withErrors(['captcha' => 'Неверный код']);
}
@endverbatim</pre>
            </div>

            <div>
                <h3 class="cap-h3">3. Через AJAX</h3>
                <pre class="cap-pre">@verbatim
const r = await fetch('/api/captcha/generate/slider');
const data = await r.json();
document.querySelector('#box').innerHTML = data.html;
@endverbatim</pre>
            </div>

            <div>
                <h3 class="cap-h3">4. С параметрами</h3>
                <pre class="cap-pre">@verbatim
{!! captcha_img('slider', ['width' => 250]) !!}
{!! captcha_img('math') !!}
@endverbatim</pre>
                <p class="text-xs text-gray-500 mt-2">
                    Доступные параметры — в таблице слева.
                </p>
            </div>
        </div>
    </section>
</div>

@push('styles')
<style>
    /* Литеральный CSS: в собранном tailwind.min.css нет opacity-модификаторов,
       произвольных значений и dark:-вариантов (см. CLAUDE.md). */
    .cap-state{display:inline-flex;align-items:center;gap:.45rem;padding:.3rem .7rem;flex:none;
        font-size:.75rem;font-weight:600;border:1px solid}
    .cap-state.is-on{color:#166534;background:#dcfce7;border-color:#86efac}
    .cap-state.is-off{color:#991b1b;background:#fee2e2;border-color:#fca5a5}
    .cap-dot{width:.45rem;height:.45rem;border-radius:999px;background:currentColor}
    .dark .cap-state.is-on{color:#86efac;background:rgba(22,101,52,.25);border-color:#166534}
    .dark .cap-state.is-off{color:#fca5a5;background:rgba(153,27,27,.25);border-color:#991b1b}

    .cap-type{display:flex;align-items:flex-start;gap:.7rem;padding:.6rem .7rem;
        border:1px solid #e5e7eb;transition:border-color .15s ease,background .15s ease}
    .dark .cap-type{border-color:#374151}
    .cap-type:hover{border-color:var(--admin-primary,#6366f1)}
    .cap-type.is-default{border-color:var(--admin-primary,#6366f1);
        background:var(--admin-primary-soft,rgba(99,102,241,.08))}
    .cap-type-ico{display:grid;place-items:center;width:2rem;height:2rem;flex:none;color:var(--admin-on-primary,#fff);
        background:linear-gradient(135deg,var(--admin-primary,#6366f1),var(--admin-accent,#a855f7));
        font-size:.8rem}
    .cap-code{padding:.05rem .35rem;font-size:.7rem;color:#4b5563;background:rgba(17,24,39,.06);
        font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
    .dark .cap-code{color:#d1d5db;background:rgba(255,255,255,.08)}
    .cap-badge{padding:.05rem .35rem;font-size:.62rem;font-weight:700;letter-spacing:.03em;
        color:var(--admin-on-primary,#fff);background:var(--admin-primary,#6366f1)}

    .cap-params{display:flex;flex-direction:column;gap:.25rem}
    .cap-param-row{display:flex;align-items:center;gap:.6rem;padding:.35rem .5rem;
        border:1px solid #e5e7eb}
    .dark .cap-param-row{border-color:#374151}

    .cap-demo{display:flex;align-items:center;gap:.7rem}
    .cap-demo-label{width:5.5rem;flex:none;font-size:.75rem;font-weight:600;color:#6b7280}
    .cap-demo-box{flex:1;min-width:0;min-height:3rem;display:flex;align-items:center;
        padding:.5rem;border:1px dashed #d1d5db;overflow-x:auto}
    .dark .cap-demo-box{border-color:#4b5563}

    .cap-btn{display:inline-flex;align-items:center;gap:.4rem;padding:.35rem .75rem;flex:none;
        font-size:.75rem;font-weight:600;color:var(--admin-on-primary,#fff);border:0;cursor:pointer;
        background:var(--admin-primary,#6366f1);transition:filter .15s ease}
    .cap-btn:hover{filter:brightness(1.1)}

    .cap-h3{font-size:.78rem;font-weight:700;color:#374151;margin-bottom:.4rem}
    .dark .cap-h3{color:#d1d5db}
    .cap-pre{margin:0;padding:.75rem;font-size:.72rem;line-height:1.5;overflow-x:auto;
        color:#e5e7eb;background:#111827;border:1px solid #374151;
        font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
</style>
@endpush

@push('scripts')
<script>
    // Демонстрация тянется с того же эндпоинта, которым пользуется сайт:
    // если тип каптчи сломан, это видно прямо здесь, а не только на форме.
    async function capLoadDemos() {
        const types = ['image', 'slider', 'math', 'question'];

        await Promise.all(types.map(async (type) => {
            const box = document.getElementById('cap-demo-' + type);
            if (!box) return;

            box.innerHTML = '<span class="text-xs text-gray-400">загрузка…</span>';

            try {
                const response = await fetch('/api/captcha/generate/' + type, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);

                const data = await response.json();
                box.innerHTML = data.html || '<span class="text-xs text-gray-400">пусто</span>';
            } catch (error) {
                box.innerHTML = '<span class="text-xs" style="color:#dc2626">не удалось загрузить: '
                    + String(error.message || error) + '</span>';
            }
        }));
    }

    document.addEventListener('DOMContentLoaded', capLoadDemos);
</script>
@endpush
@endsection
