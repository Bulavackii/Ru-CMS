@extends('layouts.admin')

@section('title', 'Настройки аналитики')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">⚙️ Настройки аналитики</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Настройка интеграции с Яндекс.Метрикой</p>
    </div>

    <form id="analytics-settings-form" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-6">
        @csrf

        <div>
            <label class="block text-sm font-medium mb-2">Включить Яндекс.Метрику</label>
            <label class="inline-flex items-center">
                <input type="checkbox" name="enabled" value="1" {{ $settings->enabled ?? false ? 'checked' : '' }} class="form-checkbox">
                <span class="ml-2">Активировать интеграцию</span>
            </label>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">API ключ Яндекс.Метрики</label>
            <input type="text" name="api_key" value="{{ $settings->api_key ?? '' }}" 
                class="w-full border rounded px-3 py-2" 
                placeholder="OAuth токен из Яндекс.Метрики">
            <p class="text-xs text-gray-500 mt-1">Получить можно в <a href="https://oauth.yandex.ru/" target="_blank" class="text-blue-600">Яндекс OAuth</a></p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">ID счетчика</label>
            <input type="text" name="counter_id" value="{{ $settings->counter_id ?? '' }}" 
                class="w-full border rounded px-3 py-2" 
                placeholder="12345678">
            <p class="text-xs text-gray-500 mt-1">ID счетчика из Яндекс.Метрики</p>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                💾 Сохранить
            </button>
            <a href="{{ route('admin.analytics.index') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                Отмена
            </a>
        </div>
    </form>
</div>

<script>
document.getElementById('analytics-settings-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        api_key: formData.get('api_key'),
        counter_id: formData.get('counter_id'),
        enabled: formData.get('enabled') === '1',
    };

    fetch('{{ route("admin.analytics.saveSettings") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Настройки сохранены');
            window.location.href = '{{ route("admin.analytics.index") }}';
        } else {
            alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
        }
    });
});
</script>
@endsection

