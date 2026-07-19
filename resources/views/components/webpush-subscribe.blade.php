{{-- Компонент подписки на Web Push уведомления --}}
@props(['userId' => null])

@auth
<div x-data="webPushSubscribe()" class="webpush-subscribe">
    <button 
        @click="toggleSubscription()"
        :disabled="loading || !supported"
        class="flex items-center gap-2 px-4 py-2 rounded-lg transition-colors"
        :class="{
            'bg-blue-600 hover:bg-blue-700 text-white': !subscribed && supported,
            'bg-gray-300 text-gray-600 cursor-not-allowed': !supported,
            'bg-green-600 hover:bg-green-700 text-white': subscribed && supported,
            'opacity-50 cursor-wait': loading
        }"
        :title="!supported ? 'Web Push не поддерживается в вашем браузере' : (subscribed ? 'Отписаться от уведомлений' : 'Подписаться на уведомления')">
        <i class="fas" :class="{
            'fa-bell': !subscribed,
            'fa-bell-slash': subscribed,
            'fa-spinner fa-spin': loading
        }"></i>
        <span x-text="loading ? 'Загрузка...' : (subscribed ? 'Отписаться' : 'Подписаться')"></span>
    </button>
</div>

@push('scripts')
<script>
function webPushSubscribe() {
    return {
        subscribed: false,
        loading: false,
        supported: false,
        
        async init() {
            this.supported = window.WebPushManager?.isSupported || false;
            
            if (this.supported) {
                const status = await window.WebPushManager.getSubscriptionStatus();
                this.subscribed = status.subscribed;
            }
        },
        
        async toggleSubscription() {
            if (!this.supported || this.loading) return;
            
            this.loading = true;
            
            try {
                if (this.subscribed) {
                    await window.WebPushManager.unsubscribe();
                    this.subscribed = false;
                    this.showNotification('Отписка успешна', 'Вы больше не будете получать уведомления');
                } else {
                    await window.WebPushManager.subscribe();
                    this.subscribed = true;
                    this.showNotification('Подписка успешна', 'Теперь вы будете получать уведомления');
                }
            } catch (error) {
                console.error('Ошибка Web Push:', error);
                alert('Ошибка: ' + error.message);
            } finally {
                this.loading = false;
            }
        },
        
        showNotification(title, message) {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification(title, {
                    body: message,
                    icon: '/favicon.svg',
                });
            }
        }
    }
}
</script>
@endpush
@endauth

