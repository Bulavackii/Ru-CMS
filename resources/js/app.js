import './bootstrap';

import Alpine from 'alpinejs';
import { setupImageErrorHandling } from './utils/resource-loader';

window.Alpine = Alpine;

// Обработка ошибок загрузки ресурсов
if (typeof document !== 'undefined') {
    setupImageErrorHandling();
}

// Глобальная обработка ошибок JavaScript
if (typeof window !== 'undefined') {
    window.addEventListener('error', (event) => {
        // Логируем ошибки в консоль (в production можно отправлять на сервер)
        console.error('JavaScript Error:', {
            message: event.message,
            source: event.filename,
            line: event.lineno,
            column: event.colno,
            error: event.error
        });
    });

    // Обработка необработанных промисов
    window.addEventListener('unhandledrejection', (event) => {
        console.error('Unhandled Promise Rejection:', event.reason);
    });
}

Alpine.start();
