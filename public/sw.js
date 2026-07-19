// Service Worker для Web Push уведомлений
const CACHE_NAME = 'ru-cms-v1';
const VAPID_PUBLIC_KEY = '{{VAPID_PUBLIC_KEY}}'; // Заменится при генерации

// Установка Service Worker
self.addEventListener('install', (event) => {
    console.log('Service Worker: Installing...');
    self.skipWaiting();
});

// Активация Service Worker
self.addEventListener('activate', (event) => {
    console.log('Service Worker: Activating...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Service Worker: Deleting old cache', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    return self.clients.claim();
});

// Обработка push уведомлений
self.addEventListener('push', (event) => {
    console.log('Service Worker: Push notification received');

    let notificationData = {
        title: 'Уведомление',
        body: 'У вас новое уведомление',
        icon: '/favicon.svg',
        badge: '/favicon.svg',
        tag: 'notification',
        requireInteraction: false,
        data: {},
    };

    if (event.data) {
        try {
            const data = event.data.json();
            notificationData = {
                title: data.title || notificationData.title,
                body: data.body || data.message || notificationData.body,
                icon: data.icon || notificationData.icon,
                badge: data.badge || notificationData.badge,
                tag: data.tag || notificationData.tag,
                requireInteraction: data.requireInteraction || false,
                data: data.data || {},
                actions: data.actions || [],
                vibrate: data.vibrate || [200, 100, 200],
            };
        } catch (e) {
            notificationData.body = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(notificationData.title, notificationData)
    );
});

// Обработка клика по уведомлению
self.addEventListener('notificationclick', (event) => {
    console.log('Service Worker: Notification clicked');

    event.notification.close();

    const urlToOpen = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true,
        }).then((clientList) => {
            // Проверяем, есть ли уже открытое окно
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            // Если окна нет, открываем новое
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});

// Обработка закрытия уведомления
self.addEventListener('notificationclose', (event) => {
    console.log('Service Worker: Notification closed');
});

