{{--
    Глобальный поиск панели.

    Раньше здесь была КНОПКА, стилизованная под поле («Поиск... Ctrl+K»):
    печатать в неё было нельзя, ввод жил в модалке поверх страницы. Со стороны
    это выглядело как декоративная сноска — непонятно, ищет ли оно вообще.
    Теперь это настоящее поле прямо в шапке: печатаешь — под полем появляются
    подсказки, стрелки и Enter работают, Ctrl+K ставит курсор в поле.

    Результаты приходят из App\Http\Controllers\Admin\GlobalSearchController:
    разделы панели (общий список с сайдбаром — App\Support\AdminSections),
    новости, страницы, категории, пользователи, меню.
--}}
<div x-data="globalSearch()" x-cloak class="ags relative w-full">
    <form method="GET" action="{{ route('admin.search.index') }}"
          @submit="onSubmit($event)" role="search" class="ags-form">
        <span class="ags-ico" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path>
            </svg>
        </span>

        <input type="search" name="q" x-ref="input" x-model="query" autocomplete="off"
               class="ags-input"
               :placeholder="placeholder"
               aria-label="{{ __('admin.header.search') }}"
               :aria-expanded="panelOpen.toString()"
               aria-controls="ags-panel"
               @input="onInput()"
               {{-- Открываем панель только если в поле уже что-то есть:
                    пустое поле показывать нечем. --}}
               @focus="panelOpen = query.trim().length > 0"
               @keydown.arrow-down.prevent="selectNext()"
               @keydown.arrow-up.prevent="selectPrev()"
               @keydown.enter="onEnter($event)"
               @keydown.escape.prevent="close()">

        {{-- Значок состояния: крутилка во время запроса, иначе подсказка Ctrl+K --}}
        <span class="ags-hint" aria-hidden="true">
            <i x-show="loading" class="fas fa-spinner fa-spin"></i>
            <kbd x-show="!loading && !query" class="ags-kbd">Ctrl K</kbd>
            <button type="button" x-show="!loading && query" @click="clear()"
                    class="ags-clear" tabindex="-1" aria-label="{{ __('admin.header.search_clear') }}">
                <i class="fas fa-times"></i>
            </button>
        </span>
    </form>

    {{-- Выпадающая панель с подсказками --}}
    <div id="ags-panel" x-show="panelOpen" x-transition.opacity.duration.120ms
         @click.outside="close()" class="ags-panel" x-ref="panel">

        {{-- ⚠️ Здесь был блок «Быстрый переход»: по щелчку в пустое поле
             открывались четыре ссылки на создание. Это дублировало кнопку
             «Создать», стоящую в шапке рядом, и мешало по делу — владелец
             шёл искать, а получал меню. Поиск теперь только ищет. --}}

        {{-- Слишком короткий запрос --}}
        <template x-if="query && query.trim().length < 2">
            <p class="ags-empty">{{ __('admin.header.search_min') }}</p>
        </template>

        {{-- Ничего не нашлось --}}
        <template x-if="!loading && query.trim().length >= 2 && results.length === 0">
            <p class="ags-empty">
                {{ __('admin.header.search_empty') }} «<span x-text="query"></span>»
            </p>
        </template>

        {{-- Результаты: сгруппированы по типу, видно откуда каждый --}}
        <template x-if="results.length > 0">
            <div>
                <template x-for="group in groupedResults()" :key="group.type">
                    <div>
                        <p class="ags-group" x-text="group.type"></p>
                        <template x-for="result in group.items" :key="result.url + result.title">
                            <a :href="result.url"
                               :id="'ags-result-' + results.indexOf(result)"
                               class="ags-item"
                               :class="results.indexOf(result) === selectedIndex ? 'is-active' : ''"
                               @click="close()"
                               @mouseenter="selectedIndex = results.indexOf(result)">
                                <i :class="result.icon" class="ags-item-ico"></i>
                                <span class="ags-item-title" x-text="result.title"></span>
                                <span class="ags-item-note" x-text="result.subtitle || ''"></span>
                            </a>
                        </template>
                    </div>
                </template>

                <a :href="allResultsUrl()" class="ags-all" @click="close()">
                    {{ __('admin.header.search_all') }} «<span x-text="query"></span>» →
                </a>
            </div>
        </template>
    </div>
</div>

<style>
    /* Поле поиска шапки. Литеральный CSS, а не Tailwind-утилиты: в собранном
       public/assets/css/tailwind.min.css нет ни opacity-модификаторов
       (bg-white/10), ни произвольных значений — «стеклянное» поле на них
       просто не отрисовалось бы. */
    .ags-form{position:relative;display:flex;align-items:center;width:100%}
    .ags-input{width:100%;height:2rem;padding:0 4.6rem 0 2.1rem;font-size:.8rem;line-height:1;
        color:#e5e7eb;background:rgba(255,255,255,.06);border:1px solid #374151;outline:none;
        transition:border-color .15s ease,background .15s ease,box-shadow .15s ease}
    .ags-input::placeholder{color:#9ca3af}
    .ags-input:hover{background:rgba(255,255,255,.09)}
    .ags-input:focus{background:rgba(255,255,255,.12);border-color:var(--admin-primary,#6366f1);
        box-shadow:0 0 0 3px var(--admin-primary-soft,rgba(99,102,241,.25))}
    /* Крестик очистки у type=search рисует сам браузер — убираем, свой понятнее */
    .ags-input::-webkit-search-cancel-button{-webkit-appearance:none;appearance:none}
    .ags-ico{position:absolute;left:.65rem;top:0;bottom:0;display:flex;align-items:center;
        color:#9ca3af;pointer-events:none}
    .ags-hint{position:absolute;right:.5rem;top:0;bottom:0;display:flex;align-items:center;
        gap:.35rem;color:#9ca3af;font-size:.7rem}
    .ags-kbd{padding:.1rem .35rem;font-size:.62rem;font-weight:600;letter-spacing:.03em;
        color:#9ca3af;background:#374151;border:1px solid #4b5563}
    .ags-clear{display:inline-flex;padding:.15rem .3rem;color:#9ca3af;background:none;border:0;
        cursor:pointer;pointer-events:auto}
    .ags-clear:hover{color:#e5e7eb}

    /* Панель подсказок */
    .ags-panel{position:absolute;left:0;right:0;top:calc(100% + .45rem);z-index:60;max-height:26rem;
        overflow-y:auto;padding:.3rem;background:#fff;border:1px solid #e5e7eb;
        box-shadow:0 24px 48px -20px rgba(17,24,39,.55)}
    .dark .ags-panel{background:#111827;border-color:#374151}
    .ags-group{padding:.5rem .6rem .25rem;font-size:.63rem;font-weight:700;letter-spacing:.06em;
        text-transform:uppercase;color:#9ca3af}
    .ags-item{display:flex;align-items:center;gap:.6rem;padding:.45rem .6rem;font-size:.82rem;
        color:#374151;text-decoration:none;transition:background .12s ease}
    .dark .ags-item{color:#d1d5db}
    .ags-item:hover,.ags-item.is-active{background:var(--admin-primary-soft,rgba(99,102,241,.12));
        color:var(--admin-primary-ink,#312e81)}
    .dark .ags-item:hover,.dark .ags-item.is-active{color:#c7d2fe}
    .ags-item-ico{width:1rem;text-align:center;flex:none;font-size:.85rem}
    .ags-item-title{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .ags-item-note{flex:none;max-width:45%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
        font-size:.7rem;color:#9ca3af}
    .ags-empty{padding:1.1rem .6rem;text-align:center;font-size:.8rem;color:#6b7280}
    .ags-all{display:block;padding:.6rem;margin-top:.3rem;border-top:1px solid #e5e7eb;
        text-align:center;font-size:.78rem;font-weight:600;color:var(--admin-primary,#4f46e5);
        text-decoration:none}
    .dark .ags-all{border-color:#374151}
    .ags-all:hover{background:var(--admin-primary-soft,rgba(99,102,241,.12))}
</style>

<script>
function globalSearch() {
    return {
        query: '',
        results: [],
        loading: false,
        panelOpen: false,
        selectedIndex: -1,
        debounceTimer: null,
        requestId: 0,
        placeholder: @js(__('admin.header.search_placeholder')),

        init() {
            // Ctrl+K / Cmd+K ставит курсор в поле (раньше открывал модалку)
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    this.$refs.input.focus();
                    this.$refs.input.select();
                    this.panelOpen = this.query.trim().length > 0;
                }
            });
        },

        allResultsUrl() {
            return @js(route('admin.search.index')) + '?q=' + encodeURIComponent(this.query);
        },

        onInput() {
            this.panelOpen = true;
            clearTimeout(this.debounceTimer);
            // Debounce: не дёргаем сервер на каждое нажатие клавиши
            this.debounceTimer = setTimeout(() => this.search(), 250);
        },

        onEnter(event) {
            // Выбран пункт стрелками — идём по нему; иначе пусть форма
            // уедет на полноценную страницу поиска (работает и без JS)
            if (this.selectedIndex >= 0 && this.results[this.selectedIndex]) {
                event.preventDefault();
                window.location.href = this.results[this.selectedIndex].url;
            }
        },

        onSubmit(event) {
            if (this.query.trim().length === 0) {
                event.preventDefault();
            }
        },

        clear() {
            this.query = '';
            this.results = [];
            this.selectedIndex = -1;
            this.$refs.input.focus();
        },

        close() {
            this.panelOpen = false;
            this.selectedIndex = -1;
        },

        // Группировка по типу для показа секциями. Порядок и индексы самого
        // results[] не трогаем: по ним считается selectedIndex, то есть
        // клавиатура ходит по плоскому списку в том же порядке, что видно.
        groupedResults() {
            const groups = [];
            const byType = {};
            for (const result of this.results) {
                if (!byType[result.type]) {
                    byType[result.type] = { type: result.type, items: [] };
                    groups.push(byType[result.type]);
                }
                byType[result.type].items.push(result);
            }
            return groups;
        },

        async search() {
            if (this.query.trim().length < 2) {
                this.results = [];
                this.loading = false;
                return;
            }

            this.loading = true;
            this.selectedIndex = -1;

            // Защита от гонки: пока летел этот запрос, пользователь мог
            // напечатать ещё что-то — ответ на устаревший запрос не должен
            // перетирать более свежие и более узкие результаты.
            const myRequestId = ++this.requestId;

            try {
                const response = await fetch(
                    @js(route('admin.search.global')) + '?q=' + encodeURIComponent(this.query),
                    { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }
                );

                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }

                const data = await response.json();
                if (myRequestId !== this.requestId) return; // устарел

                this.results = data.results || [];

                if (data.error) {
                    console.error('Search error:', data.error);
                }
            } catch (error) {
                if (myRequestId !== this.requestId) return;
                console.error('Search error:', error);
                this.results = [];
            } finally {
                if (myRequestId === this.requestId) {
                    this.loading = false;
                }
            }
        },

        selectNext() {
            this.panelOpen = true;
            if (this.selectedIndex < this.results.length - 1) {
                this.selectedIndex++;
                this.scrollSelectedIntoView();
            }
        },

        selectPrev() {
            if (this.selectedIndex > 0) {
                this.selectedIndex--;
                this.scrollSelectedIntoView();
            }
        },

        scrollSelectedIntoView() {
            this.$nextTick(() => {
                document.getElementById('ags-result-' + this.selectedIndex)
                    ?.scrollIntoView({ block: 'nearest' });
            });
        },
    }
}
</script>
