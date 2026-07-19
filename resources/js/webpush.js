/**
 * 📱 Web Push уведомления - Frontend
 */

class WebPushManager {
    constructor() {
        this.registration = null;
        this.subscription = null;
        this.publicKey = null;
        this.isSupported = this.checkSupport();
    }

    /**
     * Проверка поддержки Web Push
     */
    checkSupport() {
        return 'serviceWorker' in navigator && 
               'PushManager' in window && 
               'Notification' in window;
    }

    /**
     * Инициализация Web Push
     */
    async init() {
        if (!this.isSupported) {
            console.warn('Web Push не поддерживается в этом браузере');
            return false;
        }

        try {
            // Запрашиваем разрешение
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                console.warn('Разрешение на уведомления не получено');
                return false;
            }

            // Регистрируем Service Worker
            this.registration = await navigator.serviceWorker.register('/sw.js');
            console.log('Service Worker зарегистрирован');

            // Получаем публичный ключ
            await this.fetchPublicKey();

            // Проверяем существующую подписку
            this.subscription = await this.registration.pushManager.getSubscription();

            return true;
        } catch (error) {
            console.error('Ошибка инициализации Web Push:', error);
            return false;
        }
    }

    /**
     * Получить публичный VAPID ключ
     */
    async fetchPublicKey() {
        try {
            const response = await fetch('/webpush/public-key');
            const data = await response.json();
            
            if (data.success) {
                this.publicKey = data.publicKey;
                return this.publicKey;
            }
        } catch (error) {
            console.error('Ошибка получения публичного ключа:', error);
        }
        return null;
    }

    /**
     * Подписаться на уведомления
     */
    async subscribe() {
        if (!this.isSupported || !this.registration || !this.publicKey) {
            throw new Error('Web Push не инициализирован');
        }

        try {
            // Создаем подписку
            this.subscription = await this.registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.publicKey),
            });

            // Отправляем подписку на сервер
            const response = await fetch('/webpush/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    endpoint: this.subscription.endpoint,
                    keys: {
                        p256dh: this.arrayBufferToBase64(this.subscription.getKey('p256dh')),
                        auth: this.arrayBufferToBase64(this.subscription.getKey('auth')),
                    },
                }),
            });

            const data = await response.json();
            
            if (data.success) {
                console.log('Подписка на Web Push успешна');
                return true;
            } else {
                throw new Error(data.message || 'Ошибка подписки');
            }
        } catch (error) {
            console.error('Ошибка подписки на Web Push:', error);
            throw error;
        }
    }

    /**
     * Отписаться от уведомлений
     */
    async unsubscribe() {
        if (!this.subscription) {
            return true;
        }

        try {
            // Отписываемся на клиенте
            await this.subscription.unsubscribe();

            // Уведомляем сервер
            await fetch('/webpush/unsubscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    endpoint: this.subscription.endpoint,
                }),
            });

            this.subscription = null;
            console.log('Отписка от Web Push успешна');
            return true;
        } catch (error) {
            console.error('Ошибка отписки от Web Push:', error);
            throw error;
        }
    }

    /**
     * Проверка статуса подписки
     */
    async getSubscriptionStatus() {
        if (!this.registration) {
            return { subscribed: false, supported: false };
        }

        const subscription = await this.registration.pushManager.getSubscription();
        return {
            subscribed: subscription !== null,
            supported: this.isSupported,
        };
    }

    /**
     * Конвертация Base64 URL в Uint8Array
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    /**
     * Конвертация ArrayBuffer в Base64
     */
    arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }
}

// Глобальный экземпляр
window.WebPushManager = new WebPushManager();

// Автоинициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', async () => {
    // Инициализируем только если пользователь авторизован
    if (document.body.dataset.userId) {
        await window.WebPushManager.init();
    }
});

