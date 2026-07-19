// Утилита для показа уведомлений в админке
(function() {
    'use strict';

    /**
     * Показывает уведомление
     * @param {string} message - Текст сообщения
     * @param {string} type - Тип: 'success', 'error', 'warning', 'info'
     * @param {number} duration - Длительность в миллисекундах (0 = не закрывать автоматически)
     */
    window.showNotification = function(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 max-w-sm w-full rounded-lg shadow-lg p-4 transform transition-all duration-300 translate-x-full`;
        
        const typeClasses = {
            success: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200',
            error: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200',
            warning: 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200',
            info: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200',
        };
        
        const iconMap = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle',
        };
        
        notification.className += ` ${typeClasses[type] || typeClasses.info}`;
        
        notification.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <i class="fas fa-${iconMap[type] || iconMap.info}"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium">${escapeHtml(message)}</p>
                </div>
                <button onclick="this.closest('.fixed').remove()" 
                        class="flex-shrink-0 text-current opacity-50 hover:opacity-100 transition"
                        aria-label="Закрыть">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Анимация появления
        requestAnimationFrame(() => {
            notification.classList.remove('translate-x-full');
        });
        
        // Автоматическое закрытие
        if (duration > 0) {
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, duration);
        }
    };
    
    /**
     * Экранирование HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Глобальная функция для обработки flash сообщений
    document.addEventListener('DOMContentLoaded', function() {
        // Обработка flash сообщений из Laravel
        const flashMessages = document.querySelectorAll('[data-flash-message]');
        flashMessages.forEach(function(element) {
            const message = element.getAttribute('data-flash-message');
            const type = element.getAttribute('data-flash-type') || 'info';
            if (message) {
                window.showNotification(message, type);
            }
        });
    });
})();




