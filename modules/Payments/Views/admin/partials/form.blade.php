@php
    use Modules\Payments\Console\Commands\SeedDefaultPaymentMethodsCommand;

    $method = $method ?? null;

    // Какие реквизиты нужны какому драйверу — один источник с сидером.
    // Раньше форма показывала ВСЕ поля сразу (ИНН, БИК, terminal key,
    // shop id…), и было непонятно, что из этого относится к выбранной
    // системе.
    $credentialFields = SeedDefaultPaymentMethodsCommand::credentialFields();

    // Названия платёжных систем — торговые марки, не переводятся.
    // Переводится только короткое пояснение под названием.
    $types = [
        'yookassa' => ['label' => 'ЮKassa',  'icon' => 'fa-wallet',       'note' => __('admin.payments.n_yookassa')],
        'sbp'      => ['label' => 'СБП',     'icon' => 'fa-qrcode',       'note' => __('admin.payments.n_sbp')],
        'sberpay'  => ['label' => 'SberPay', 'icon' => 'fa-mobile-screen', 'note' => __('admin.payments.n_sberpay')],
        'tbank'    => ['label' => 'Т-Банк',  'icon' => 'fa-building-columns', 'note' => __('admin.payments.n_tbank')],
        'online'   => ['label' => 'Online',  'icon' => 'fa-globe',        'note' => __('admin.payments.n_online')],
        'offline'  => ['label' => 'Offline', 'icon' => 'fa-hand-holding-dollar', 'note' => __('admin.payments.n_offline')],
    ];

    // Подписи полей реквизитов — технические имена из документации систем,
    // поэтому не переводятся: владелец ищет ровно их в личном кабинете.
    $fieldLabels = [
        'shop_id' => 'shopId', 'secret_key' => 'Secret key',
        'merchant_id' => 'Merchant ID', 'account' => 'Account',
        'username' => 'Username', 'password' => 'Password',
        'terminal_key' => 'Terminal key', 'api_key' => 'API key',
    ];

    $currentType = old('type', $method->type ?? 'offline');
    $settings = (array) old('settings', $method->settings ?? []);
@endphp

<div x-data="{
        type: @js($currentType),
        fields: @js($credentialFields),
        checking: false,
        checkResult: null,
        checkOk: false,
        get currentFields() { return this.fields[this.type] || []; },
        async check() {
            this.checking = true;
            this.checkResult = null;
            try {
                const response = await fetch(@js($method ? route('admin.payments.check', $method->id) : ''), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': @js(csrf_token()), 'Accept': 'application/json' },
                });
                const data = await response.json();
                this.checkOk = response.ok;
                this.checkResult = data.message;
            } catch (e) {
                this.checkOk = false;
                this.checkResult = e.message;
            } finally {
                this.checking = false;
            }
        }
     }">

    {{-- ── Основное ── --}}
    <section class="admin-card p-5 mb-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">
            <i class="fas fa-tag text-indigo-500"></i> {{ __('admin.payments.g_main') }}
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="title" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.payments.f_title') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title" required
                       value="{{ old('title', $method->title ?? '') }}"
                       placeholder="{{ __('admin.payments.f_title_ph') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">
                    {{ __('admin.payments.f_type') }}
                </label>

                {{-- Карточки вместо выпадающего списка: сразу видно, какие
                     системы поддерживаются и чем они отличаются. --}}
                <div class="pm-types">
                    @foreach($types as $value => $meta)
                        <label class="pm-type" :class="type === @js($value) ? 'is-active' : ''">
                            <input type="radio" name="type" value="{{ $value }}" x-model="type" class="sr-only">
                            <i class="fas {{ $meta['icon'] }}"></i>
                            <span class="pm-type__name">{{ $meta['label'] }}</span>
                            <span class="pm-type__note">{{ $meta['note'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.payments.f_desc') }}
                </label>
                <textarea id="description" name="description" rows="2"
                          placeholder="{{ __('admin.payments.f_desc_ph') }}"
                          class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">{{ old('description', $method->description ?? '') }}</textarea>
            </div>

            <div>
                <label for="code" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.payments.f_code') }}
                </label>
                <input type="text" id="code" name="code" value="{{ old('code', $method->code ?? '') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm font-mono">
                <p class="admin-hint mt-1">{{ __('admin.payments.f_code_hint') }}</p>
            </div>

            <div>
                <label for="sort_order" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.payments.f_sort') }}
                </label>
                <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $method->sort_order ?? 0) }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                <p class="admin-hint mt-1">{{ __('admin.payments.f_sort_hint') }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-5 mt-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" class="border-gray-400"
                       {{ old('active', $method->active ?? false) ? 'checked' : '' }}>
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.payments.f_active') }}</span>
            </label>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="test_mode" value="0">
                <input type="checkbox" name="test_mode" value="1" class="border-gray-400"
                       {{ old('test_mode', $method->test_mode ?? true) ? 'checked' : '' }}>
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.payments.f_test') }}</span>
            </label>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="is_russian" value="0">
                <input type="checkbox" name="is_russian" value="1" class="border-gray-400"
                       {{ old('is_russian', $method->is_russian ?? true) ? 'checked' : '' }}>
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.payments.ru_system') }}</span>
            </label>
        </div>

        <p class="admin-hint mt-2">{{ __('admin.payments.f_test_hint') }}</p>
    </section>

    {{-- ── Реквизиты платёжной системы ── --}}
    <section class="admin-card p-5 mb-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">
            <i class="fas fa-key text-indigo-500"></i> {{ __('admin.payments.g_creds') }}
        </h2>

        <template x-if="currentFields.length === 0">
            <p class="admin-hint">{{ __('admin.payments.creds_none') }}</p>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($fieldLabels as $field => $label)
                <div x-cloak x-show="currentFields.includes(@js($field))">
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ $label }}</label>
                    <input type="text" name="settings[{{ $field }}]" autocomplete="off"
                           value="{{ $settings[$field] ?? '' }}"
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm font-mono">
                </div>
            @endforeach
        </div>

        <template x-if="currentFields.length > 0">
            <p class="admin-hint mt-3">{{ __('admin.payments.creds_hint') }}</p>
        </template>

        <div class="mt-4">
            <label for="docs_url" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                {{ __('admin.payments.f_docs') }}
            </label>
            <input type="url" id="docs_url" name="docs_url" value="{{ old('docs_url', $method->docs_url ?? '') }}"
                   class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
        </div>

        @if($method)
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <button type="button" @click="check()" :disabled="checking"
                        class="inline-flex items-center gap-2 border border-indigo-300 text-indigo-700 dark:text-indigo-300
                               hover:bg-indigo-50 dark:hover:bg-gray-800 px-4 py-2 text-sm font-semibold transition">
                    <i class="fas fa-plug"></i>
                    <span x-text="checking ? @js(__('admin.payments.checking')) : @js(__('admin.payments.check'))"></span>
                </button>

                <p x-cloak x-show="checkResult" class="text-sm"
                   :class="checkOk ? 'text-green-700' : 'text-red-600'" x-text="checkResult"></p>
            </div>
        @endif
    </section>

    {{-- ── Суммы и комиссия ── --}}
    <section class="admin-card p-5 mb-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">
            <i class="fas fa-ruble-sign text-indigo-500"></i> {{ __('admin.payments.g_limits') }}
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="commission" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.payments.f_commission') }}
                </label>
                <input type="number" step="0.01" id="commission" name="commission"
                       value="{{ old('commission', $method->commission ?? 0) }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
            </div>

            <div>
                <label for="min_amount" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.payments.f_min') }}
                </label>
                <input type="number" step="0.01" id="min_amount" name="min_amount"
                       value="{{ old('min_amount', $method->min_amount ?? '') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
            </div>

            <div>
                <label for="max_amount" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.payments.f_max') }}
                </label>
                <input type="number" step="0.01" id="max_amount" name="max_amount"
                       value="{{ old('max_amount', $method->max_amount ?? '') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
        </div>

        <p class="admin-hint mt-1">{{ __('admin.payments.f_limits_hint') }}</p>

        <div class="mt-4">
            <label for="currencies" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                {{ __('admin.payments.f_currencies') }}
            </label>
            <input type="text" id="currencies" name="currencies"
                   value="{{ old('currencies', is_array($method->currencies ?? null) ? implode(', ', $method->currencies) : 'RUB') }}"
                   class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm font-mono">
            <p class="admin-hint mt-1">{{ __('admin.payments.f_currencies_hint') }}</p>
        </div>
    </section>

    <div class="flex flex-wrap items-center gap-2">
        <button type="submit"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 text-sm font-semibold shadow-sm transition">
            <i class="fas fa-floppy-disk"></i> {{ __('admin.payments.save') }}
        </button>

        <a href="{{ route('admin.payments.index') }}"
           class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-5 py-2.5 text-sm font-semibold transition">
            {{ __('admin.payments.cancel') }}
        </a>
    </div>
</div>

@push('styles')
<style>
    .pm-types { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .5rem }
    .pm-type {
        display: block; padding: .75rem; cursor: pointer; text-align: center;
        border: 1px solid #e5e7eb; background: #fff; transition: border-color .15s, box-shadow .15s;
    }
    .pm-type:hover { border-color: #a5b4fc }
    .pm-type.is-active { border-color: #6366f1; box-shadow: inset 0 0 0 1px #6366f1 }
    .pm-type i { font-size: 1.15rem; color: #6366f1 }
    .pm-type__name { display: block; font-weight: 700; font-size: .8rem; margin-top: .35rem; color: #111827 }
    .pm-type__note { display: block; font-size: 11px; color: #6b7280; margin-top: .15rem }
</style>
@endpush
