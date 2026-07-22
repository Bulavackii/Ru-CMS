{{-- Глобальный поиск (Cmd/Ctrl+K) --}}
<div x-data="globalSearch()" x-cloak class="relative">
    {{-- Кнопка поиска --}}
    <button @click="open = true"
            class="flex items-center gap-2 px-3 py-2 bg-gray-800 hover:bg-gray-700 rounded-lg text-sm transition"
            title="Поиск (Ctrl+K)">
        <i class="fas fa-search text-gray-400"></i>
        <span class="hidden md:inline text-gray-300">Поиск...</span>
        <kbd class="hidden lg:inline-flex items-center px-2 py-1 text-xs font-semibold text-gray-400 bg-gray-700 border border-gray-600 rounded">Ctrl+K</kbd>
    </button>

    {{-- Модальное окно поиска --}}
    <div x-show="open"
         @click.away="open = false"
         @keydown.escape.window="open = false"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4"
         style="display: none;">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl w-full max-w-2xl overflow-hidden">
            {{-- Поле поиска --}}
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <i class="fas fa-search text-gray-400"></i>
                    <input type="text"
                           x-model="query"
                           @input="debouncedSearch()"
                           @keydown.arrow-down.prevent="selectNext()"
                           @keydown.arrow-up.prevent="selectPrev()"
                           @keydown.enter.prevent="openSelected()"
                           placeholder="Поиск по всем модулям..."
                           class="flex-1 bg-transparent border-none outline-none text-gray-900 dark:text-white placeholder-gray-400"
                           autofocus>
                    <i x-show="loading" class="fas fa-spinner fa-spin text-gray-400"></i>
                </div>
            </div>

            {{-- Результаты --}}
            <div class="max-h-96 overflow-y-auto" x-ref="resultsBox">
                <template x-if="!loading && results.length === 0 && query.length > 0">
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-search text-2xl mb-2"></i>
                        <p>Ничего не найдено по «<span x-text="query"></span>»</p>
                    </div>
                </template>

                <template x-if="query.length === 0">
                    <div class="p-4">
                        <p class="text-sm text-gray-500 mb-2">Быстрые действия:</p>
                        <div class="space-y-1">
                            <button @click="quickAction('news')" class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded flex items-center gap-2">
                                <i class="fas fa-newspaper text-blue-500"></i>
                                <span>Создать новость</span>
                            </button>
                            <button @click="quickAction('page')" class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded flex items-center gap-2">
                                <i class="fas fa-file text-green-500"></i>
                                <span>Создать страницу</span>
                            </button>
                            <button @click="quickAction('user')" class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded flex items-center gap-2">
                                <i class="fas fa-user-plus text-purple-500"></i>
                                <span>Создать пользователя</span>
                            </button>
                        </div>
                    </div>
                </template>

                <template x-if="results.length > 0">
                    <div>
                        <template x-for="group in groupedResults()" :key="group.type">
                            <div>
                                <p class="px-4 pt-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400" x-text="group.type"></p>
                                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <template x-for="result in group.items" :key="result.url">
                                        <a :href="result.url"
                                           :id="'search-result-' + results.indexOf(result)"
                                           @click="open = false"
                                           @mouseenter="selectedIndex = results.indexOf(result)"
                                           :class="results.indexOf(result) === selectedIndex ? 'bg-indigo-50 dark:bg-indigo-900' : ''"
                                           class="block px-4 py-2.5 transition">
                                            <div class="flex items-center gap-3">
                                                <i :class="result.icon" class="w-4 text-center"></i>
                                                <p class="flex-1 truncate font-medium text-gray-900 dark:text-white" x-text="result.title"></p>
                                            </div>
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <a :href="'{{ route('admin.search.index') }}?q=' + encodeURIComponent(query)"
                           class="block px-4 py-3 text-center text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700 border-t border-gray-200 dark:border-gray-700 transition">
                            Показать все результаты по «<span x-text="query"></span>» →
                        </a>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function globalSearch() {
    return {
        open: false,
        query: '',
        results: [],
        loading: false,
        selectedIndex: -1,
        debounceTimer: null,
        requestId: 0,

        init() {
            // Горячая клавиша Ctrl+K или Cmd+K
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    this.open = !this.open;
                    if (this.open) {
                        this.$nextTick(() => {
                            this.$el.querySelector('input')?.focus();
                        });
                    }
                }
            });
        },

        // Группирует плоский results[] по result.type для отображения секциями,
        // не трогая порядок/индексы самого results — по нему считается
        // selectedIndex (клавиатурная навигация работает по плоскому списку).
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

        // Debounce: не долбим сервер на каждое нажатие клавиши.
        debouncedSearch() {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => this.search(), 250);
        },

        async search() {
            if (this.query.length < 2) {
                this.results = [];
                this.loading = false;
                return;
            }

            this.loading = true;
            this.selectedIndex = -1;

            // Защита от гонки: если пока летал этот запрос пользователь
            // напечатал ещё что-то и улетел более новый запрос — ответ
            // на устаревший запрос просто игнорируется, не перетирая
            // более свежие (и более узкие) результаты.
            const myRequestId = ++this.requestId;

            try {
                const response = await fetch(`/admin/search/global?q=${encodeURIComponent(this.query)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
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
                if (window.showNotification) {
                    window.showNotification('Ошибка при выполнении поиска', 'error');
                }
            } finally {
                if (myRequestId === this.requestId) {
                    this.loading = false;
                }
            }
        },

        selectNext() {
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
                document.getElementById('search-result-' + this.selectedIndex)
                    ?.scrollIntoView({ block: 'nearest' });
            });
        },

        openSelected() {
            if (this.selectedIndex >= 0 && this.results[this.selectedIndex]) {
                window.location.href = this.results[this.selectedIndex].url;
            }
        },

        quickAction(type) {
            const routes = {
                'news': '{{ route("admin.news.create") }}',
                'page': '{{ route("admin.pages.create") }}',
                'user': '{{ route("admin.users.create") }}'
            };

            if (routes[type]) {
                window.location.href = routes[type];
            }
            this.open = false;
        }
    }
}
</script>
