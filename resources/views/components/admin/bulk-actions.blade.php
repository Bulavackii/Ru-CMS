{{-- Массовые действия --}}
<div x-data="bulkActions()" class="mb-4">
    <div x-show="selected.length > 0" 
         class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4 flex items-center justify-between"
         style="display: none;">
        <div class="flex items-center gap-3">
            <span class="font-medium text-blue-900 dark:text-blue-100">
                Выбрано: <span x-text="selected.length"></span>
            </span>
        </div>
        <div class="flex gap-2">
            <button @click="bulkAction('delete')" 
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-trash mr-2"></i>Удалить
            </button>
            <button @click="bulkAction('publish')" 
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-check mr-2"></i>Опубликовать
            </button>
            <button @click="bulkAction('unpublish')" 
                    class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                <i class="fas fa-times mr-2"></i>Снять с публикации
            </button>
            <button @click="clearSelection()" 
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                Отменить
            </button>
        </div>
    </div>
</div>

<script>
function bulkActions() {
    return {
        selected: [],
        
        init() {
            // Слушаем события выбора
            window.addEventListener('bulk-select', (e) => {
                if (e.detail.checked) {
                    this.selected.push(e.detail.id);
                } else {
                    this.selected = this.selected.filter(id => id !== e.detail.id);
                }
            });
        },
        
        async bulkAction(action) {
            if (this.selected.length === 0) return;
            
            if (!confirm(`Вы уверены, что хотите ${this.getActionName(action)} ${this.selected.length} элементов?`)) {
                return;
            }
            
            try {
                const response = await fetch(window.bulkActionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        action: action,
                        ids: this.selected
                    })
                });
                
                if (response.ok) {
                    location.reload();
                }
            } catch (error) {
                console.error('Bulk action error:', error);
                alert('Ошибка при выполнении действия');
            }
        },
        
        getActionName(action) {
            const names = {
                'delete': 'удалить',
                'publish': 'опубликовать',
                'unpublish': 'снять с публикации'
            };
            return names[action] || action;
        },
        
        clearSelection() {
            this.selected = [];
            document.querySelectorAll('input[type="checkbox"][data-bulk-select]').forEach(cb => {
                cb.checked = false;
            });
        }
    }
}
</script>

