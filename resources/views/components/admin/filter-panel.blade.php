{{-- Панель расширенных фильтров --}}
<div x-data="filterPanel()" class="mb-6">
    <button @click="open = !open"
            class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">
        <i class="fas fa-filter"></i>
        <span>Фильтры</span>
        <span x-show="activeFiltersCount > 0" 
              x-text="'(' + activeFiltersCount + ')'"
              class="bg-blue-600 text-white px-2 py-0.5 rounded-full text-xs"></span>
        <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
    </button>

    <div x-show="open"
         x-transition
         class="mt-4 bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700"
         style="display: none;">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Здесь будут динамические фильтры --}}
            @yield('filters')
        </div>
        
        <div class="mt-4 flex items-center justify-between">
            <button @click="clearFilters()" 
                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                Сбросить фильтры
            </button>
            <div class="flex gap-2">
                <button @click="saveFilterPreset()" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Сохранить как шаблон
                </button>
                <button @click="applyFilters()" 
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Применить
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function filterPanel() {
    return {
        open: false,
        filters: {},
        activeFiltersCount: 0,
        
        init() {
            // Загрузка сохраненных фильтров из localStorage
            const saved = localStorage.getItem('admin_filters_' + window.location.pathname);
            if (saved) {
                this.filters = JSON.parse(saved);
                this.updateActiveCount();
            }
            
            // Загрузка фильтров из URL
            const params = new URLSearchParams(window.location.search);
            params.forEach((value, key) => {
                if (key.startsWith('filter_')) {
                    this.filters[key.replace('filter_', '')] = value;
                }
            });
            this.updateActiveCount();
        },
        
        updateActiveCount() {
            this.activeFiltersCount = Object.keys(this.filters).filter(key => this.filters[key]).length;
        },
        
        applyFilters() {
            const params = new URLSearchParams();
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) {
                    params.set('filter_' + key, this.filters[key]);
                }
            });
            
            // Сохранение в localStorage
            localStorage.setItem('admin_filters_' + window.location.pathname, JSON.stringify(this.filters));
            
            window.location.search = params.toString();
        },
        
        clearFilters() {
            this.filters = {};
            this.updateActiveCount();
            localStorage.removeItem('admin_filters_' + window.location.pathname);
            window.location.search = '';
        },
        
        saveFilterPreset() {
            const name = prompt('Название шаблона фильтра:');
            if (name) {
                const presets = JSON.parse(localStorage.getItem('admin_filter_presets') || '{}');
                presets[name] = this.filters;
                localStorage.setItem('admin_filter_presets', JSON.stringify(presets));
                alert('Шаблон сохранен!');
            }
        }
    }
}
</script>

