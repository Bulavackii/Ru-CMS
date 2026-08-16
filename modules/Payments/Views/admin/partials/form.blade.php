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
    //
    // Значок и фирменный цвет берутся из карты в модели — одного места на
    // весь раздел. Логотип подставляется, если файл положен в
    // public/images/payments; иначе остаётся значок способа оплаты.
    $types = [
        'yookassa' => ['label' => 'ЮKassa',  'note' => __('admin.payments.n_yookassa')],
        'sbp'      => ['label' => 'СБП',     'note' => __('admin.payments.n_sbp')],
        'sberpay'  => ['label' => 'SberPay', 'note' => __('admin.payments.n_sberpay')],
        'tbank'    => ['label' => 'Т-Банк',  'note' => __('admin.payments.n_tbank')],
        'online'   => ['label' => 'Online',  'note' => __('admin.payments.n_online')],
        'offline'  => ['label' => 'Offline', 'note' => __('admin.payments.n_offline')],
    ];

    foreach ($types as $value => $meta) {
        $brand = \Modules\Payments\Models\PaymentMethod::BRANDS[$value]
            ?? ['color' => '#6366F1', 'icon' => 'fa-credit-card'];

        $logo = null;
        foreach (['svg', 'png', 'webp'] as $ext) {
            $rel = \Modules\Payments\Models\PaymentMethod::LOGO_DIR . '/' . $value . '.' . $ext;
            if (is_file(public_path($rel))) {
                $logo = asset($rel) . '?v=' . filemtime(public_path($rel));
                break;
            }
        }

        $types[$value] += ['icon' => $brand['icon'], 'color' => $brand['color'], 'logo' => $logo];
    }

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

<div class="pm-form" x-data="{
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

    {{-- Две колонки: слева описание метода, справа его реквизиты и
         проверка связи. Раньше секции шли одна под другой, и до полей с
         ключами приходилось прокручивать мимо выбора системы, который
         меняют один раз. --}}
    <div class="pm-cols">

    {{-- ── Основное ── --}}
    <section class="admin-card p-5">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">
            <i class="fas fa-tag text-indigo-500"></i> {{ __('admin.payments.g_main') }}
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="title" class="pm-field-label block text-gray-800 dark:text-gray-200 mb-1">
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
                        <label class="pm-type" style="--pm:{{ $meta['color'] }}"
                               :class="type === @js($value) ? 'is-active' : ''">
                            <input type="radio" name="type" value="{{ $value }}" x-model="type" class="sr-only">

                            <span class="pm-type__mark">
                                @if($meta['logo'])
                                    <img src="{{ $meta['logo'] }}" alt="{{ $meta['label'] }}" loading="lazy">
                                @else
                                    <i class="fas {{ $meta['icon'] }}"></i>
                                @endif
                            </span>

                            <span class="pm-type__name">{{ $meta['label'] }}</span>
                            <span class="pm-type__note">{{ $meta['note'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="md:col-span-2">
                <label for="description" class="pm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.payments.f_desc') }}
                </label>
                <textarea id="description" name="description" rows="2"
                          placeholder="{{ __('admin.payments.f_desc_ph') }}"
                          class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">{{ old('description', $method->description ?? '') }}</textarea>
            </div>

            <div>
                <label for="code" class="pm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.payments.f_code') }}
                </label>
                <input type="text" id="code" name="code" value="{{ old('code', $method->code ?? '') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm font-mono">
                <p class="admin-hint mt-1">{{ __('admin.payments.f_code_hint') }}</p>
            </div>

            <div>
                <label for="sort_order" class="pm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.payments.f_sort') }}
                </label>
                <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $method->sort_order ?? 0) }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                <p class="admin-hint mt-1">{{ __('admin.payments.f_sort_hint') }}</p>
            </div>
        </div>

        @php
            // Готовность драйвера у УЖЕ заведённого способа. У новой записи
            // тип выбирают на этой же странице, поэтому предупреждение там
            // показывать не по чему — оно появится после сохранения.
            $готовность = isset($method) && $method->exists ? $method->readiness() : 'ready';
        @endphp

        @if($готовность !== 'ready')
            <div class="pm-ready pm-ready--{{ $готовность }} mt-4">
                <i class="fas {{ $готовность === 'stub' ? 'fa-screwdriver-wrench' : 'fa-triangle-exclamation' }}"></i>
                <span>
                    <b>{{ $готовность === 'stub' ? __('admin.payments.ready_stub') : __('admin.payments.ready_untested') }}.</b>
                    {{ $готовность === 'stub' ? __('admin.payments.ready_stub_hint') : __('admin.payments.ready_untested_hint') }}
                </span>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-5 mt-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" class="border-gray-400"
                       @disabled($готовность === 'stub')
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
    <section class="admin-card p-5">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">
            <i class="fas fa-key text-indigo-500"></i> {{ __('admin.payments.g_creds') }}
        </h2>

        <template x-if="currentFields.length === 0">
            <p class="admin-hint">{{ __('admin.payments.creds_none') }}</p>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($fieldLabels as $field => $label)
                <div x-cloak x-show="currentFields.includes(@js($field))">
                    <label class="pm-field-label block text-gray-800 dark:text-gray-200 mb-1">{{ $label }}</label>
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
            <label for="docs_url" class="pm-field-label block text-gray-800 dark:text-gray-200 mb-1">
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
    <section class="admin-card p-5">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">
            <i class="fas fa-ruble-sign text-indigo-500"></i> {{ __('admin.payments.g_limits') }}
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="commission" class="pm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.payments.f_commission') }}
                </label>
                <input type="number" step="0.01" id="commission" name="commission"
                       value="{{ old('commission', $method->commission ?? 0) }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
            </div>

            <div>
                <label for="min_amount" class="pm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.payments.f_min') }}
                </label>
                <input type="number" step="0.01" id="min_amount" name="min_amount"
                       value="{{ old('min_amount', $method->min_amount ?? '') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
            </div>

            <div>
                <label for="max_amount" class="pm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.payments.f_max') }}
                </label>
                <input type="number" step="0.01" id="max_amount" name="max_amount"
                       value="{{ old('max_amount', $method->max_amount ?? '') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
            </div>
        </div>

        <p class="admin-hint mt-1">{{ __('admin.payments.f_limits_hint') }}</p>

        <div class="mt-4">
            <label for="currencies" class="pm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                {{ __('admin.payments.f_currencies') }}
            </label>
            <input type="text" id="currencies" name="currencies"
                   value="{{ old('currencies', is_array($method->currencies ?? null) ? implode(', ', $method->currencies) : 'RUB') }}"
                   class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm font-mono">
            <p class="admin-hint mt-1">{{ __('admin.payments.f_currencies_hint') }}</p>
        </div>
    </section>
    </div>


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
/* Две колонки: описание метода и его реквизиты. `align-items:start`
       обязателен — иначе короткая карточка растягивается до высоты
       соседней и под ней висит пустая рамка. */
    .pm-cols{ display:grid; gap:1rem; align-items:start; margin-bottom:1rem }
    @media (min-width:1180px){ .pm-cols{ grid-template-columns:1.25fr 1fr } }

    /* Типографика как на страницах входа и в кабинете: подписи полей и
       заголовки секций — моноширинным, мелко, капсом, с крупным
       просветом. Второй шрифт не нужен — системный моноширинный стек уже
       используется в проекте для ключей и кодов. */
    .pm-form label:not(.pm-type):not(.pm-check),
    .pm-form h2{ font-family:ui-monospace, SFMono-Regular, Menlo, monospace }
    .pm-form h2{ font-size:.7rem; letter-spacing:.12em }
    .pm-form .pm-field-label{ font-size:.66rem; font-weight:700;
        letter-spacing:.1em; text-transform:uppercase }

    /* Плашка о готовности драйвера. Литеральный CSS: в сборке нет ни
       прозрачности через дробь, ни произвольных значений. */
    .pm-ready{ display:flex; align-items:flex-start; gap:.6rem;
        padding:.7rem .85rem; font-size:.82rem; line-height:1.45;
        border-left:3px solid #f59e0b; background:#fffbeb; color:#78350f }
    .pm-ready--stub{ border-left-color:#dc2626; background:#fef2f2; color:#7f1d1d }
    .pm-ready i{ margin-top:.15rem; flex:0 0 auto }

    .pm-types { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 150px), 1fr)); gap: .5rem }

    /* Место под знак системы: если файл положен — логотип, если нет —
       значок способа оплаты. Размер один и тот же, чтобы ряд карточек не
       прыгал по высоте. */
    .pm-type__mark { display:flex; align-items:center; justify-content:center;
        width:2.25rem; height:2.25rem; margin:0 auto .35rem }
    .pm-type__mark img { width:100%; height:100%; object-fit:contain; display:block }
    .pm-type__mark i { font-size:1.05rem; color:var(--pm, #6366f1) }

    /* Выбранная карточка — в фирменном цвете системы, а не общим индиго:
       так видно, что выбрано, ещё до чтения подписи. */
    .pm-type.is-active { border-color: var(--pm, #6366f1);
        box-shadow: 0 0 0 1px var(--pm, #6366f1),
                    0 6px 16px color-mix(in srgb, var(--pm, #6366f1) 20%, transparent) }
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
