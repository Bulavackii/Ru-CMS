@php
    $method = $method ?? null;
@endphp

{{-- 🏷️ Название метода --}}
<div class="mb-4">
    <label class="block font-semibold mb-1">
        🏷️ Название
    </label>
    <input type="text" name="title" value="{{ old('title', $method->title ?? '') }}"
           class="w-full border border-gray-300 rounded px-3 py-2 shadow-sm focus:ring focus:ring-black/20"
           placeholder="Введите название" required>
</div>

{{-- 📝 Описание метода --}}
<div class="mb-4">
    <label class="block font-semibold mb-1">
        📝 Описание
    </label>
    <textarea name="description" rows="3"
              class="w-full border border-gray-300 rounded px-3 py-2 shadow-sm focus:ring focus:ring-black/20"
              placeholder="Дополнительная информация (необязательно)">{{ old('description', $method->description ?? '') }}</textarea>
</div>

{{-- ⚙️ Тип метода --}}
<div class="mb-4">
    <label class="block font-semibold mb-1">
        ⚙️ Тип метода
    </label>
    <select name="type"
            class="w-full border border-gray-300 rounded px-3 py-2 shadow-sm focus:ring focus:ring-black/20"
            required>
        <option value="offline" {{ old('type', $method->type ?? '') === 'offline' ? 'selected' : '' }}>🖐️ Offline (Оффлайн)</option>
        <option value="online" {{ old('type', $method->type ?? '') === 'online' ? 'selected' : '' }}>🌐 Online (Онлайн)</option>
        <option value="sbp" {{ old('type', $method->type ?? '') === 'sbp' ? 'selected' : '' }}>💸 СБП (Система быстрых платежей)</option>
        <option value="yookassa" {{ old('type', $method->type ?? '') === 'yookassa' ? 'selected' : '' }}>💳 ЮKassa</option>
        <option value="tinkoff" {{ old('type', $method->type ?? '') === 'tinkoff' ? 'selected' : '' }}>🏦 Тинькофф</option>
        <option value="sberbank" {{ old('type', $method->type ?? '') === 'sberbank' ? 'selected' : '' }}>🏦 Сбербанк</option>
        <option value="sberpay" {{ old('type', $method->type ?? '') === 'sberpay' ? 'selected' : '' }}>💳 Сбербанк Pay</option>
        <option value="qiwi" {{ old('type', $method->type ?? '') === 'qiwi' ? 'selected' : '' }}>📱 QIWI</option>
        <option value="robokassa" {{ old('type', $method->type ?? '') === 'robokassa' ? 'selected' : '' }}>🔄 Robokassa</option>
        <option value="cloudpayments" {{ old('type', $method->type ?? '') === 'cloudpayments' ? 'selected' : '' }}>☁️ CloudPayments</option>
        <option value="unitpay" {{ old('type', $method->type ?? '') === 'unitpay' ? 'selected' : '' }}>💳 Unitpay</option>
        <option value="interkassa" {{ old('type', $method->type ?? '') === 'interkassa' ? 'selected' : '' }}>💳 Interkassa</option>
    </select>
</div>

{{-- 🔑 Уникальный код --}}
<div class="mb-4">
    <label class="block font-semibold mb-1">
        🔑 Уникальный код (опционально)
    </label>
    <input type="text" name="code" value="{{ old('code', $method->code ?? '') }}"
           class="w-full border border-gray-300 rounded px-3 py-2 shadow-sm focus:ring focus:ring-black/20"
           placeholder="Например: sbp, yookassa, tinkoff">
    <p class="text-xs text-gray-500 mt-1">Используется для внутренней идентификации</p>
</div>

{{-- 🇷🇺 Российская платежная система --}}
<div class="mb-4">
    <label class="inline-flex items-center font-medium">
        <input type="checkbox" name="is_russian" value="1"
               class="mr-2 rounded border-gray-300 text-black shadow-sm"
               {{ old('is_russian', $method->is_russian ?? false) ? 'checked' : '' }}>
        🇷🇺 Это российская платежная система
    </label>
</div>

{{-- 💰 Комиссия --}}
<div class="mb-4">
    <label class="block font-semibold mb-1">
        💰 Комиссия (%)
    </label>
    <input type="number" name="commission" value="{{ old('commission', $method->commission ?? '') }}" step="0.01" min="0" max="100"
           class="w-full border border-gray-300 rounded px-3 py-2 shadow-sm focus:ring focus:ring-black/20"
           placeholder="Например: 2.9">
</div>

{{-- 💸 Минимальная и максимальная сумма --}}
<div class="grid grid-cols-2 gap-4 mb-4">
    <div>
        <label class="block font-semibold mb-1">
            💸 Мин. сумма (₽)
        </label>
        <input type="number" name="min_amount" value="{{ old('min_amount', $method->min_amount ?? '') }}" step="0.01" min="0"
               class="w-full border border-gray-300 rounded px-3 py-2 shadow-sm focus:ring focus:ring-black/20"
               placeholder="1.00">
    </div>
    <div>
        <label class="block font-semibold mb-1">
            💸 Макс. сумма (₽)
        </label>
        <input type="number" name="max_amount" value="{{ old('max_amount', $method->max_amount ?? '') }}" step="0.01" min="0"
               class="w-full border border-gray-300 rounded px-3 py-2 shadow-sm focus:ring focus:ring-black/20"
               placeholder="100000.00">
    </div>
</div>

{{-- 💱 Валюты --}}
<div class="mb-4">
    <label class="block font-semibold mb-1">
        💱 Поддерживаемые валюты (через запятую)
    </label>
    <input type="text" name="currencies" value="{{ old('currencies') ? implode(', ', old('currencies')) : ($method->currencies ? implode(', ', $method->currencies) : 'RUB') }}"
           class="w-full border border-gray-300 rounded px-3 py-2 shadow-sm focus:ring focus:ring-black/20"
           placeholder="RUB, USD, EUR">
    <p class="text-xs text-gray-500 mt-1">Коды валют (например: RUB, USD, EUR)</p>
</div>

{{-- ✅ Активность метода --}}
<div class="mb-4">
    <label class="inline-flex items-center font-medium">
        <input type="checkbox" name="active" value="1"
               class="mr-2 rounded border-gray-300 text-black shadow-sm"
               {{ old('active', $method->active ?? true) ? 'checked' : '' }}>
        ✅ Включить метод (отображать при оформлении заказа)
    </label>
</div>

{{-- 🧪 Режим тестирования --}}
<div class="mb-4">
    <label class="inline-flex items-center font-medium">
        <input type="checkbox" name="test_mode" value="1"
               class="mr-2 rounded border-gray-300 text-black shadow-sm"
               {{ old('test_mode', $method->test_mode ?? false) ? 'checked' : '' }}>
        🧪 Режим тестирования
    </label>
</div>

{{-- 🇷🇺 Специфичные поля для российских систем --}}
<div id="russian-fields" class="space-y-4 p-4 bg-gray-50 rounded border border-gray-200" style="display: none;">
    <h3 class="font-semibold text-gray-700">🇷🇺 Настройки российской платежной системы</h3>

    <div id="field-inn" style="display: none;">
        <label class="block font-semibold mb-1">ИНН (10 цифр)</label>
        <input type="text" name="inn" value="{{ old('inn', $method->inn ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2"
               placeholder="1234567890" maxlength="10">
    </div>

    <div id="field-bik" style="display: none;">
        <label class="block font-semibold mb-1">БИК (9 цифр)</label>
        <input type="text" name="bik" value="{{ old('bik', $method->bik ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2"
               placeholder="044525999" maxlength="9">
    </div>

    <div id="field-account" style="display: none;">
        <label class="block font-semibold mb-1">Расчетный счет (20 цифр)</label>
        <input type="text" name="account" value="{{ old('account', $method->account ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2"
               placeholder="40817810100000123456" maxlength="20">
    </div>

    <div id="field-shop_id" style="display: none;">
        <label class="block font-semibold mb-1">Shop ID / ID магазина</label>
        <input type="text" name="shop_id" value="{{ old('shop_id', $method->shop_id ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2"
               placeholder="shop_12345">
    </div>

    <div id="field-secret_key" style="display: none;">
        <label class="block font-semibold mb-1">Secret Key / Секретный ключ</label>
        <input type="text" name="secret_key" value="{{ old('secret_key', $method->secret_key ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2"
               placeholder="Ваш секретный ключ">
    </div>

    <div id="field-terminal_key" style="display: none;">
        <label class="block font-semibold mb-1">Terminal Key / Ключ терминала</label>
        <input type="text" name="terminal_key" value="{{ old('terminal_key', $method->terminal_key ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2"
               placeholder="Ваш ключ терминала">
    </div>

    <div id="field-api_key" style="display: none;">
        <label class="block font-semibold mb-1">API Key</label>
        <input type="text" name="api_key" value="{{ old('api_key', $method->api_key ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2"
               placeholder="Ваш API ключ">
    </div>

    <div id="field-public_id" style="display: none;">
        <label class="block font-semibold mb-1">Public ID (CloudPayments)</label>
        <input type="text" name="public_id" value="{{ old('public_id', $method->public_id ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2"
               placeholder="pk_12345">
    </div>

    <div id="field-bank_name" style="display: none;">
        <label class="block font-semibold mb-1">Название банка</label>
        <input type="text" name="bank_name" value="{{ old('bank_name', $method->bank_name ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2"
               placeholder="Например: Сбербанк">
    </div>

    <div id="field-kpp" style="display: none;">
        <label class="block font-semibold mb-1">КПП (9 цифр)</label>
        <input type="text" name="kpp" value="{{ old('kpp', $method->kpp ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2"
               placeholder="123456789" maxlength="9">
    </div>

    <div id="field-correspondent_account" style="display: none;">
        <label class="block font-semibold mb-1">Корреспондентский счет (20 цифр)</label>
        <input type="text" name="correspondent_account" value="{{ old('correspondent_account', $method->correspondent_account ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2"
               placeholder="30101810400000000999" maxlength="20">
    </div>
</div>

{{-- 🌐 URL настройки --}}
<div class="mb-4">
    <label class="block font-semibold mb-1">
        🌐 Callback URL (уведомления)
    </label>
    <input type="url" name="callback_url" value="{{ old('callback_url', $method->callback_url ?? '') }}"
           class="w-full border border-gray-300 rounded px-3 py-2 shadow-sm focus:ring focus:ring-black/20"
           placeholder="https://your-site.com/payment/callback">
</div>

<div class="grid grid-cols-2 gap-4 mb-4">
    <div>
        <label class="block font-semibold mb-1">
            ✅ Success URL
        </label>
        <input type="url" name="success_url" value="{{ old('success_url', $method->success_url ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 shadow-sm focus:ring focus:ring-black/20"
               placeholder="https://your-site.com/success">
    </div>
    <div>
        <label class="block font-semibold mb-1">
            ❌ Fail URL
        </label>
        <input type="url" name="fail_url" value="{{ old('fail_url', $method->fail_url ?? '') }}"
               class="w-full border border-gray-300 rounded px-3 py-2 shadow-sm focus:ring focus:ring-black/20"
               placeholder="https://your-site.com/fail">
    </div>
</div>

{{-- 🧪 Sandbox режим --}}
<div class="mb-4">
    <label class="inline-flex items-center font-medium">
        <input type="checkbox" name="sandbox" value="1"
               class="mr-2 rounded border-gray-300 text-black shadow-sm"
               {{ old('sandbox', $method->sandbox ?? false) ? 'checked' : '' }}>
        🧪 Sandbox (Тестовый режим)
    </label>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.querySelector('select[name="type"]');
    const russianFields = document.getElementById('russian-fields');

    const fieldGroups = {
        'yookassa': ['field-shop_id', 'field-secret_key'],
        'tinkoff': ['field-terminal_key', 'field-secret_key'],
        'sberbank': ['field-api_key', 'field-inn'],
        'sberpay': ['field-api_key', 'field-inn'],
        'sbp': ['field-bik', 'field-account', 'field-inn'],
        'qiwi': ['field-api_key', 'field-shop_id'],
        'robokassa': ['field-shop_id', 'field-secret_key'],
        'cloudpayments': ['field-api_key', 'field-public_id'],
        'unitpay': ['field-shop_id', 'field-secret_key'],
        'interkassa': ['field-shop_id', 'field-secret_key'],
    };

    function toggleFields() {
        const selectedType = typeSelect.value;
        const isRussian = document.querySelector('input[name="is_russian"]').checked;

        // Сброс всех полей
        document.querySelectorAll('#russian-fields > div').forEach(div => {
            div.style.display = 'none';
        });

        // Показать/скрыть блок российских полей
        if (isRussian && fieldGroups[selectedType]) {
            russianFields.style.display = 'block';
            fieldGroups[selectedType].forEach(fieldId => {
                document.getElementById(fieldId).style.display = 'block';
            });
        } else {
            russianFields.style.display = 'none';
        }
    }

    typeSelect.addEventListener('change', toggleFields);
    document.querySelector('input[name="is_russian"]').addEventListener('change', toggleFields);

    // Инициализация при загрузке
    toggleFields();
});
</script>

<style>
#russian-fields {
    transition: all 0.3s ease;
}
#russian-fields > div {
    transition: all 0.2s ease;
}
</style>
