{{-- Центр уведомлений --}}
<div x-data="notificationsCenter()" class="relative">
    {{-- Кнопка уведомлений --}}
    <button @click="open = !open"
            class="relative flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-800 transition"
            title="Уведомления">
        <i class="fas fa-bell text-gray-300"></i>
        <span x-show="unreadCount > 0" 
              x-text="unreadCount"
              class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"></span>
    </button>

    {{-- Панель уведомлений --}}
    <div x-show="open"
         @click.away="open = false"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
         style="display: none;">
        
        {{-- Заголовок --}}
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white">Уведомления</h3>
            <button @click="markAllAsRead()" 
                    x-show="unreadCount > 0"
                    class="text-sm text-blue-600 hover:text-blue-700">
                Отметить все как прочитанные
            </button>
        </div>

        {{-- Список уведомлений --}}
        <div class="max-h-96 overflow-y-auto">
            <template x-if="loading">
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p>Загрузка...</p>
                </div>
            </template>

            <template x-if="!loading && notifications.length === 0">
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-bell-slash text-2xl mb-2"></i>
                    <p>Нет уведомлений</p>
                </div>
            </template>

            <template x-if="!loading && notifications.length > 0">
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="(notification, index) in notifications" :key="notification.id">
                        <div :class="{'bg-blue-50 dark:bg-blue-900': !notification.read}"
                             class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                     :class="{
                                         'bg-green-100 dark:bg-green-900': notification.type === 'success',
                                         'bg-red-100 dark:bg-red-900': notification.type === 'error',
                                         'bg-yellow-100 dark:bg-yellow-900': notification.type === 'warning',
                                         'bg-blue-100 dark:bg-blue-900': notification.type === 'info'
                                     }">
                                    <i class="fas"
                                       :class="{
                                           'fa-check-circle text-green-600 dark:text-green-400': notification.type === 'success',
                                           'fa-exclamation-circle text-red-600 dark:text-red-400': notification.type === 'error',
                                           'fa-exclamation-triangle text-yellow-600 dark:text-yellow-400': notification.type === 'warning',
                                           'fa-info-circle text-blue-600 dark:text-blue-400': notification.type === 'info'
                                       }"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white" x-text="notification.title"></p>
                                    <p class="text-sm text-gray-500 mt-1" x-text="notification.message"></p>
                                    <p class="text-xs text-gray-400 mt-1" x-text="formatTime(notification.created_at)"></p>
                                    <a x-show="notification.action_url"
                                       :href="notification.action_url"
                                       class="text-sm text-blue-600 hover:text-blue-700 mt-2 inline-block"
                                       x-text="notification.action_text || 'Подробнее'"></a>
                                </div>
                                <button @click="deleteNotification(notification.id)"
                                        class="text-gray-400 hover:text-gray-600 flex-shrink-0">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function notificationsCenter() {
    return {
        open: false,
        notifications: [],
        unreadCount: 0,
        loading: true,
        
        init() {
            this.loadNotifications();
            // Обновление каждые 30 секунд
            setInterval(() => this.loadNotifications(), 30000);
        },
        
        async loadNotifications() {
            try {
                const response = await fetch('/admin/notifications');
                const data = await response.json();
                this.notifications = data.notifications || [];
                this.unreadCount = data.unread_count || 0;
            } catch (error) {
                console.error('Failed to load notifications:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async markAllAsRead() {
            try {
                await fetch('/admin/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                this.loadNotifications();
            } catch (error) {
                console.error('Failed to mark all as read:', error);
            }
        },
        
        async deleteNotification(id) {
            try {
                await fetch(`/admin/notifications/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                this.loadNotifications();
            } catch (error) {
                console.error('Failed to delete notification:', error);
            }
        },
        
        formatTime(time) {
            const date = new Date(time);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) return 'Только что';
            if (diff < 3600000) return Math.floor(diff / 60000) + ' мин назад';
            if (diff < 86400000) return Math.floor(diff / 3600000) + ' ч назад';
            return date.toLocaleDateString('ru-RU');
        }
    }
}
</script>

