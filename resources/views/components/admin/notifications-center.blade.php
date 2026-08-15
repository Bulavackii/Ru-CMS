{{-- Центр уведомлений в шапке панели --}}
<div x-data="notificationsCenter()" class="relative">
    {{-- Кнопка --}}
    <button type="button" @click="toggle()"
            {{-- ⚠️ Тот же класс, что у остальных кнопок шапки. Раньше здесь
                 была своя пара отступов (px-3 py-2), и колокольчик выбивался
                 из ряда: другой размер, другая высота, нет рамки — рядом с
                 учётной записью это читалось как случайно вставленный
                 элемент. Общий класс держит и размер, и зону нажатия на
                 сенсорных (там он растёт до 40). --}}
            class="ahd-btn ntf-trigger"
            :aria-expanded="open" aria-haspopup="true"
            :title="@js(__('admin.notif.title'))">
        <i class="fas fa-bell text-gray-300"></i>
        <span class="ntf-badge" x-show="unreadCount > 0" x-cloak
              x-text="unreadCount > 99 ? '99+' : unreadCount"></span>
    </button>

    {{-- Панель --}}
    <div x-show="open" x-cloak
         @click.away="open = false"
         @keydown.escape.window="open = false"
         x-transition:enter="ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="ntf-panel">

        <div class="ntf-head">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                {{ __('admin.notif.title') }}
                <span class="ntf-head__count" x-show="unreadCount > 0" x-cloak x-text="unreadCount"></span>
            </h3>

            <button type="button" @click="markAllAsRead()" x-show="unreadCount > 0" x-cloak
                    class="text-xs font-semibold text-indigo-600 hover:underline">
                {{ __('admin.notif.mark_all') }}
            </button>
        </div>

        <div class="ntf-list">
            {{-- Загрузка --}}
            <div class="ntf-state" x-show="loading" x-cloak>
                <i class="fas fa-spinner fa-spin"></i>
                <p>{{ __('admin.notif.loading') }}</p>
            </div>

            {{-- Ошибка сети: раньше она уходила только в console.error,
                 и панель молча висела пустой. --}}
            <div class="ntf-state" x-show="!loading && failed" x-cloak>
                <i class="fas fa-triangle-exclamation"></i>
                <p>{{ __('admin.notif.failed') }}</p>
                <button type="button" @click="load()" class="text-xs font-semibold text-indigo-600 hover:underline">
                    {{ __('admin.notif.retry') }}
                </button>
            </div>

            {{-- Пусто --}}
            <div class="ntf-state" x-show="!loading && !failed && items.length === 0" x-cloak>
                <i class="fas fa-bell-slash"></i>
                <p class="font-semibold text-gray-700 dark:text-gray-200">{{ __('admin.notif.empty') }}</p>
                <p class="ntf-state__hint">{{ __('admin.notif.empty_hint') }}</p>
            </div>

            {{-- Список --}}
            <template x-for="item in items" :key="item.id">
                <div class="ntf-item" :class="!item.read && 'is-unread'">
                    <span class="ntf-item__ico" :class="'ntf-item__ico--' + (item.type || 'info')">
                        <i class="fas" :class="icons[item.type] || icons.info"></i>
                    </span>

                    <div class="ntf-item__body">
                        <p class="ntf-item__title" x-text="item.title"></p>
                        <p class="ntf-item__text" x-text="item.message"></p>
                        <p class="ntf-item__time" x-text="formatTime(item.created_at)"></p>

                        <div class="ntf-item__links">
                            <a x-show="item.action_url" :href="item.action_url"
                               @click="markAsRead(item)"
                               x-text="item.action_text || @js(__('admin.notif.more'))"></a>

                            {{-- Маршрут «отметить прочитанным» существовал, но
                                 компонент его не вызывал: отдельное уведомление
                                 нельзя было прочитать вообще, только все разом. --}}
                            <button type="button" x-show="!item.read" @click="markAsRead(item)">
                                {{ __('admin.notif.mark_read') }}
                            </button>
                        </div>
                    </div>

                    <button type="button" class="ntf-item__close" @click="remove(item.id)"
                            :title="@js(__('admin.notif.remove'))">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            </template>
        </div>

        @if(\Illuminate\Support\Facades\Route::has('admin.notifications.index'))
            <a href="{{ route('admin.notifications.index') }}" class="ntf-foot">
                {{ __('admin.notif.all') }} <i class="fas fa-arrow-right"></i>
            </a>
        @endif
    </div>
</div>

@once
@push('scripts')
<script>
    function notificationsCenter() {
        return {
            open: false,
            items: [],
            unreadCount: 0,
            loading: true,
            failed: false,
            timer: null,

            icons: {
                success: 'fa-circle-check',
                error: 'fa-circle-exclamation',
                warning: 'fa-triangle-exclamation',
                info: 'fa-circle-info',
            },

            init() {
                this.load();

                // Опрос шёл каждые 30 секунд вечно, даже в фоновой вкладке.
                // Теперь пауза, пока вкладка скрыта, и минута вместо
                // полуминуты: уведомления панели не настолько срочные.
                document.addEventListener('visibilitychange', () => {
                    document.hidden ? this.stopPolling() : (this.load(), this.startPolling());
                });

                if (! document.hidden) this.startPolling();
            },

            startPolling() {
                this.stopPolling();
                this.timer = setInterval(() => this.load(), 60000);
            },

            stopPolling() {
                if (this.timer) { clearInterval(this.timer); this.timer = null; }
            },

            toggle() {
                this.open = ! this.open;
                if (this.open) this.load();
            },

            headers(json = false) {
                const meta = document.querySelector('meta[name="csrf-token"]');
                const headers = { 'Accept': 'application/json' };
                if (json && meta) headers['X-CSRF-TOKEN'] = meta.content;
                return headers;
            },

            async load() {
                try {
                    const response = await fetch('/admin/notification-center', { headers: this.headers() });
                    if (! response.ok) throw new Error(response.status);

                    const data = await response.json();
                    this.items = data.notifications || [];
                    this.unreadCount = data.unread_count || 0;
                    this.failed = false;
                } catch (error) {
                    this.failed = true;
                } finally {
                    this.loading = false;
                }
            },

            async markAsRead(item) {
                if (item.read) return;

                // Помечаем сразу, не дожидаясь ответа: иначе при переходе по
                // ссылке уведомление успевало остаться непрочитанным.
                item.read = true;
                this.unreadCount = Math.max(0, this.unreadCount - 1);

                await fetch('/admin/notification-center/' + item.id + '/read', {
                    method: 'POST', headers: this.headers(true),
                }).catch(() => {});
            },

            async markAllAsRead() {
                this.items.forEach(item => item.read = true);
                this.unreadCount = 0;

                await fetch('/admin/notification-center/mark-all-read', {
                    method: 'POST', headers: this.headers(true),
                }).catch(() => {});
            },

            async remove(id) {
                const removed = this.items.find(item => item.id === id);
                this.items = this.items.filter(item => item.id !== id);

                if (removed && ! removed.read) {
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }

                await fetch('/admin/notification-center/' + id, {
                    method: 'DELETE', headers: this.headers(true),
                }).catch(() => {});
            },

            formatTime(value) {
                // Раньше «мин назад» и toLocaleDateString('ru-RU') были
                // прибиты гвоздями — на любом языке панели время выводилось
                // по-русски. Intl берёт язык из <html lang>.
                const locale = document.documentElement.lang || 'ru';
                const date = new Date(value);

                if (isNaN(date)) return '';

                const seconds = Math.round((date - new Date()) / 1000);
                const units = [['day', 86400], ['hour', 3600], ['minute', 60]];

                for (const [unit, size] of units) {
                    if (Math.abs(seconds) >= size) {
                        return new Intl.RelativeTimeFormat(locale, { numeric: 'auto' })
                            .format(Math.round(seconds / size), unit);
                    }
                }

                return @js(__('admin.notif.just_now'));
            },
        };
    }
</script>
@endpush
@endonce

@once
{{-- Стиль объявлен прямо здесь, а не через стек: стек стилей живёт
     в head и к моменту отрисовки шапки уже закрыт. --}}
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни прозрачности через /NN, ни половины палитры. */
    /* Размер кнопки-колокольчика. Вид (рамка и подложка) задаёт шапка:
       кнопка лежит в «обойме» `.ahd-group`, а та НАМЕРЕННО снимает у
       вложенных кнопок рамку и фон — рамку рисует сама обойма. Селектор
       обоймы из двух классов сильнее одиночного, поэтому любые попытки
       покрасить кнопку отсюда молча ничего не меняли. */
    .ntf-trigger{ position:relative; display:grid; place-items:center;
        width:2rem; height:2rem; padding:0; cursor:pointer }

    @media (max-width: 1024px), (max-height: 500px){
        .ntf-trigger{ width:2.5rem; height:2.5rem }
    }

    .ntf-badge{ position:absolute; top:.15rem; right:.35rem; min-width:1.05rem; height:1.05rem; padding:0 .2rem;
                display:flex; align-items:center; justify-content:center; background:#dc2626; color:#fff;
                font-size:.65rem; font-weight:700; line-height:1 }

    .ntf-panel{ position:absolute; right:0; top:calc(100% + .35rem); width:22rem; max-width:calc(100vw - 1.5rem);
                background:#fff; border:1px solid #e5e7eb; box-shadow:0 12px 32px rgba(15,23,42,.18);
                z-index:60; display:flex; flex-direction:column }

    .ntf-head{ display:flex; align-items:center; justify-content:space-between; gap:.75rem;
               padding:.75rem .9rem; border-bottom:1px solid #f1f5f9 }
    .ntf-head__count{ display:inline-block; margin-left:.35rem; padding:.05rem .35rem; background:#eef2ff;
                      color:#3730a3; font-size:.7rem; font-weight:700 }

    .ntf-list{ max-height:24rem; overflow-y:auto }

    .ntf-state{ padding:2rem 1.25rem; text-align:center; color:#6b7280; font-size:.85rem }
    .ntf-state i{ font-size:1.5rem; color:#c7d2fe; display:block; margin-bottom:.6rem }
    .ntf-state p{ margin:0 0 .25rem }
    .ntf-state__hint{ font-size:.78rem; color:#9ca3af }

    .ntf-item{ display:flex; gap:.65rem; padding:.75rem .9rem; border-bottom:1px solid #f1f5f9 }
    .ntf-item:hover{ background:#f8fafc }
    .ntf-item.is-unread{ border-left:3px solid #4f46e5 }

    .ntf-item__ico{ display:flex; align-items:center; justify-content:center; width:2rem; height:2rem;
                    flex-shrink:0; font-size:.8rem }
    .ntf-item__ico--success{ background:#f0fdf4; color:#166534 }
    .ntf-item__ico--error{ background:#fef2f2; color:#991b1b }
    .ntf-item__ico--warning{ background:#fffbeb; color:#92400e }
    .ntf-item__ico--info{ background:#eef2ff; color:#3730a3 }

    .ntf-item__body{ flex:1; min-width:0 }
    .ntf-item__title{ margin:0; font-size:.85rem; font-weight:600; color:#111827; word-break:break-word }
    .ntf-item__text{ margin:.15rem 0 0; font-size:.78rem; color:#6b7280; word-break:break-word }
    .ntf-item__time{ margin:.25rem 0 0; font-size:.7rem; color:#9ca3af }
    .ntf-item__links{ display:flex; flex-wrap:wrap; gap:.75rem; margin-top:.35rem }
    .ntf-item__links a, .ntf-item__links button{ font-size:.75rem; font-weight:600; color:#4f46e5;
                                                 background:none; border:0; padding:0; cursor:pointer }
    .ntf-item__links a:hover, .ntf-item__links button:hover{ text-decoration:underline }

    .ntf-item__close{ color:#cbd5e1; background:none; border:0; cursor:pointer; align-self:flex-start;
                      padding:.1rem .2rem; flex-shrink:0 }
    .ntf-item__close:hover{ color:#dc2626 }

    .ntf-foot{ display:block; padding:.6rem .9rem; text-align:center; font-size:.78rem; font-weight:600;
               color:#4f46e5; border-top:1px solid #f1f5f9 }
    .ntf-foot:hover{ background:#f8fafc }

    /* ⚠️ Здесь стоял блок по тёмному режиму ОПЕРАЦИОННОЙ СИСТЕМЫ. Тему
       панели задают класс .dark и переменные --admin-*, а настройка ОС к
       ним отношения не имеет: при тёмной системе и светлой панели
       выпадающий список уведомлений становился тёмным посреди светлой
       шапки, а заголовки — почти белыми на белом. */
</style>
@endonce
