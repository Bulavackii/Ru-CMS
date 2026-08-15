@php
    use Modules\Delivery\Console\Commands\SeedDefaultDeliveryMethodsCommand;
    use Modules\Delivery\Models\DeliveryMethod;

    $method = $method ?? null;

    // Какие ключи нужны какой службе — один источник с сидером. Форма
    // показывает только поля выбранной службы, а не все сразу.
    $credentialFields = SeedDefaultDeliveryMethodsCommand::credentialFields();

    // Названия служб — торговые марки, не переводятся. Переводится
    // только подпись у своих способов (самовывоз, курьер по городу).
    //
    // Значок, фирменный цвет и логотип берутся из карты в модели — одного
    // места на весь раздел. Логотип подставляется, если файл положен в
    // public/images/delivery; иначе остаётся значок типа доставки.
    $services = [
        'pochta' => ['label' => 'Почта России', 'type' => 'post'],
        'cdek' => ['label' => 'СДЭК', 'type' => 'courier'],
        'boxberry' => ['label' => 'Boxberry', 'type' => 'terminal'],
        'yandex_delivery' => ['label' => 'Яндекс Доставка', 'type' => 'courier'],
        'pickup' => ['label' => __('admin.delivery.m_pickup'), 'type' => 'pickup'],
        'courier_local' => ['label' => __('admin.delivery.m_courier'), 'type' => 'courier'],
    ];

    foreach ($services as $code => $meta) {
        $brand = DeliveryMethod::BRANDS[$code]
            ?? DeliveryMethod::TYPE_BRANDS[$meta['type']]
            ?? ['color' => '#6366F1', 'icon' => 'fa-truck'];

        $logo = null;
        foreach (['svg', 'png', 'webp'] as $ext) {
            $relative = DeliveryMethod::LOGO_DIR . '/' . $code . '.' . $ext;
            if (is_file(public_path($relative))) {
                $logo = asset($relative) . '?v=' . filemtime(public_path($relative));
                break;
            }
        }

        $services[$code] += ['icon' => $brand['icon'], 'color' => $brand['color'], 'logo' => $logo];
    }

    // Подписи ключей — технические имена из документации служб, поэтому
    // не переводятся: владелец ищет ровно их в личном кабинете.
    $fieldLabels = [
        'token' => 'Token', 'login' => 'Login', 'password' => 'Password',
        'account' => 'Account', 'secure_password' => 'Secure password',
        'oauth_token' => 'OAuth token',
    ];

    // Седьмая карточка — своя служба. Ключ начинается с подчёркиваний,
    // чтобы наверняка не совпасть с настоящим кодом службы: код своей
    // службы вводит владелец, и слово «custom» он тоже вправе занять.
    $services['__custom'] = [
        'label' => __('admin.delivery.m_custom'),
        'type' => 'courier',
        'icon' => 'fa-circle-plus',
        'color' => '#64748B',
        'logo' => null,
    ];

    $savedCode = old('code', $method->code ?? 'pickup');

    // Служба, которой нет среди типовых, — это своя: показываем поля кода
    // и типа, а не подсовываем чужую карточку.
    $isCustom = ! array_key_exists($savedCode, $services) || $savedCode === '__custom';
    $currentCode = $isCustom ? '__custom' : $savedCode;
    $customCode = $isCustom && $savedCode !== '__custom' ? $savedCode : '';
    $customType = old('type', $method->type ?? 'courier');

    $settings = (array) old('api_settings', $method->api_settings ?? []);
    $selectedRegions = (array) old('regions', $method->regions ?? []);
@endphp

@csrf

<div class="dm-form" x-data="{
        code: @js($currentCode),
        customCode: @js($customCode),
        customType: @js($customType),
        types: @js(collect($services)->map(fn ($s) => $s['type'])->all()),
        fields: @js($credentialFields),
        allFields: @js(array_keys($fieldLabels)),
        get isCustom() { return this.code === '__custom'; },
        checking: false,
        checkResult: null,
        checkOk: false,
        // У своей службы набор ключей заранее не известен — показываем
        // все, владелец заполняет те, что ему выдали.
        get currentFields() { return this.isCustom ? this.allFields : (this.fields[this.code] || []); },
        get currentType() { return this.isCustom ? this.customType : (this.types[this.code] || 'courier'); },
        async check() {
            this.checking = true;
            this.checkResult = null;
            try {
                const response = await fetch(@js($method ? route('admin.delivery.check', $method->id) : ''), {
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

    {{-- Тип подставляется по выбранной службе: у Почты он post, у СДЭК
         courier и так далее. Отдельным полем это только путало. --}}
    <input type="hidden" name="type" :value="currentType">

    {{-- Код службы: у типовых он равен выбранной карточке, у своей —
         тому, что ввёл владелец. Уникальность кода проверяет сервер, а
         список типов там же ограничен четырьмя значениями. --}}
    <input type="hidden" name="code" :value="isCustom ? customCode : code">

    {{-- Две колонки: слева то, что заполняют всегда (служба, название,
         цена и сроки), справа — то, к чему возвращаются реже (ключи API и
         регионы). В одну колонку форма растягивалась на два экрана. --}}
    <div class="dm-cols">
        <div>
            {{-- ── Основное ── --}}
            <section class="admin-card p-5 mb-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">
                    <i class="fas fa-tag text-indigo-500"></i> {{ __('admin.delivery.g_main') }}
                </h2>

                <label class="dm-field-label block text-gray-800 dark:text-gray-200 mb-2">
                    {{ __('admin.delivery.f_service') }}
                </label>

                <div class="dm-types mb-4">
                    @foreach($services as $value => $meta)
                        <label class="dm-type" style="--dl:{{ $meta['color'] }}"
                               :class="code === @js($value) ? 'is-active' : ''">
                            {{-- Без name: переключатели — только выбор в
                                 интерфейсе, а на сервер код уходит скрытым
                                 полем ниже (у своей службы он свой). --}}
                            <input type="radio" value="{{ $value }}" x-model="code" class="sr-only">

                            <span class="dm-type__mark">
                                @if($meta['logo'])
                                    <img src="{{ $meta['logo'] }}" alt="{{ $meta['label'] }}" loading="lazy">
                                @else
                                    <i class="fas {{ $meta['icon'] }}"></i>
                                @endif
                            </span>

                            <span class="dm-type__name">{{ $meta['label'] }}</span>
                        </label>
                    @endforeach
                </div>

                {{-- Своя служба: код и тип задаются руками. У типовых они
                     подставляются картой выше, и показывать эти поля
                     незачем — их правка только ломает выбор драйвера. --}}
                <div x-cloak x-show="isCustom" class="dm-custom mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="custom_code" class="dm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                                {{ __('admin.delivery.f_code') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="custom_code" x-model="customCode"
                                   placeholder="{{ __('admin.delivery.code_ph') }}"
                                   class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm font-mono">
                            @error('code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="custom_type" class="dm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                                {{ __('admin.delivery.type') }}
                            </label>
                            <select id="custom_type" x-model="customType"
                                    class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                                <option value="courier">{{ __('admin.delivery.courier') }}</option>
                                <option value="pickup">{{ __('admin.delivery.pickup') }}</option>
                                <option value="post">{{ __('admin.delivery.post') }}</option>
                                <option value="terminal">{{ __('admin.delivery.terminal') }}</option>
                            </select>
                            @error('type') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <p class="admin-hint mt-2">{{ __('admin.delivery.custom_hint') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="title" class="dm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                            {{ __('admin.delivery.f_name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" required
                               value="{{ old('title', $method->title ?? '') }}"
                               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                        @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="sort_order" class="dm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                            {{ __('admin.delivery.f_sort') }}
                        </label>
                        <input type="number" id="sort_order" name="sort_order"
                               value="{{ old('sort_order', $method->sort_order ?? 0) }}"
                               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                        <p class="admin-hint mt-1">{{ __('admin.delivery.f_sort_hint') }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="dm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                            {{ __('admin.delivery.f_desc') }}
                        </label>
                        <textarea id="description" name="description" rows="2"
                                  placeholder="{{ __('admin.delivery.f_desc_ph') }}"
                                  class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">{{ old('description', $method->description ?? '') }}</textarea>
                    </div>
                </div>

                <p class="admin-hint mt-3">{{ __('admin.delivery.f_code_hint') }}</p>

                {{-- Тумблеры, а не голые галочки: в этой сборке Tailwind
                     нет варианта peer-checked, поэтому переключатель
                     собран настоящим CSS-селектором (класс .admin-toggle
                     в лейауте панели). --}}
                <div class="dm-switches mt-4">
                    <label class="dm-switch">
                        <span class="admin-toggle">
                            <input type="hidden" name="active" value="0">
                            <input type="checkbox" name="active" value="1"
                                   {{ old('active', $method->active ?? false) ? 'checked' : '' }}>
                            <span class="track"></span><span class="knob"></span>
                        </span>
                        <span class="dm-switch__text">{{ __('admin.delivery.f_active') }}</span>
                    </label>

                    <label class="dm-switch">
                        <span class="admin-toggle">
                            <input type="hidden" name="is_russian" value="0">
                            <input type="checkbox" name="is_russian" value="1"
                                   {{ old('is_russian', $method->is_russian ?? true) ? 'checked' : '' }}>
                            <span class="track"></span><span class="knob"></span>
                        </span>
                        <span class="dm-switch__text">{{ __('admin.delivery.ru_system') }}</span>
                    </label>
                </div>
            </section>

            {{-- ── Стоимость и сроки ── --}}
            <section class="admin-card p-5 mb-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">
                    <i class="fas fa-ruble-sign text-indigo-500"></i> {{ __('admin.delivery.g_price') }}
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="price" class="dm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                            {{ __('admin.delivery.f_price') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="number" step="0.01" id="price" name="price" required
                               value="{{ old('price', $method->price ?? 0) }}"
                               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                        @error('price') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="free_delivery_threshold" class="dm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                            {{ __('admin.delivery.f_free_from') }}
                        </label>
                        <input type="number" step="0.01" id="free_delivery_threshold" name="free_delivery_threshold"
                               value="{{ old('free_delivery_threshold', $method->free_delivery_threshold ?? '') }}"
                               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                        <p class="admin-hint mt-1">{{ __('admin.delivery.f_free_hint') }}</p>
                    </div>

                    <div>
                        <label for="weight_limit" class="dm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                            {{ __('admin.delivery.f_weight') }}
                        </label>
                        <input type="number" step="0.01" id="weight_limit" name="weight_limit"
                               value="{{ old('weight_limit', $method->weight_limit ?? '') }}"
                               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                        <p class="admin-hint mt-1">{{ __('admin.delivery.f_weight_hint') }}</p>
                    </div>

                    <div>
                        <label for="min_days" class="dm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                            {{ __('admin.delivery.f_min_days') }}
                        </label>
                        <input type="number" id="min_days" name="min_days"
                               value="{{ old('min_days', $method->min_days ?? '') }}"
                               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label for="max_days" class="dm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                            {{ __('admin.delivery.f_max_days') }}
                        </label>
                        <input type="number" id="max_days" name="max_days"
                               value="{{ old('max_days', $method->max_days ?? '') }}"
                               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                        @error('max_days') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
        </div>

        <div>
            {{-- ── Интеграция ── --}}
            <section class="admin-card p-5 mb-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">
                    <i class="fas fa-plug text-indigo-500"></i> {{ __('admin.delivery.g_api') }}
                </h2>

                <template x-if="currentFields.length === 0">
                    <p class="admin-hint">{{ __('admin.delivery.creds_none') }}</p>
                </template>

                <template x-if="currentFields.length > 0">
                    <label class="dm-switch mb-4">
                        <span class="admin-toggle">
                            <input type="hidden" name="api_enabled" value="0">
                            <input type="checkbox" name="api_enabled" value="1"
                                   {{ old('api_enabled', $method->api_enabled ?? false) ? 'checked' : '' }}>
                            <span class="track"></span><span class="knob"></span>
                        </span>
                        <span class="dm-switch__text">{{ __('admin.delivery.f_api_enabled') }}</span>
                    </label>
                </template>

                <template x-if="isCustom">
                    <p class="admin-hint mb-3">
                        <b>{{ __('admin.delivery.custom_fields') }}.</b>
                        {{ __('admin.delivery.custom_fields_hint') }}
                    </p>
                </template>

                <div class="grid grid-cols-1 gap-4">
                    @foreach($fieldLabels as $field => $label)
                        <div x-cloak x-show="currentFields.includes(@js($field))">
                            <label class="dm-field-label block text-gray-800 dark:text-gray-200 mb-1">{{ $label }}</label>
                            <input type="text" name="api_settings[{{ $field }}]" autocomplete="off"
                                   value="{{ $settings[$field] ?? '' }}"
                                   class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm font-mono">
                        </div>
                    @endforeach
                </div>

                <template x-if="currentFields.length > 0">
                    <p class="admin-hint mt-3">{{ __('admin.delivery.f_api_hint') }}</p>
                </template>

                <div class="mt-4">
                    <label for="docs_url" class="dm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                        {{ __('admin.delivery.f_docs') }}
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
                            <span x-text="checking ? @js(__('admin.delivery.checking')) : @js(__('admin.delivery.check'))"></span>
                        </button>

                        <p x-cloak x-show="checkResult" class="text-sm"
                           :class="checkOk ? 'text-green-700' : 'text-red-600'" x-text="checkResult"></p>
                    </div>
                @endif
            </section>

            {{-- ── Регионы ── --}}
            <section class="admin-card p-5 mb-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">
                    <i class="fas fa-map text-indigo-500"></i> {{ __('admin.delivery.g_regions') }}
                </h2>

                <label for="regions" class="dm-field-label block text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.delivery.f_regions') }}
                </label>

                <select id="regions" name="regions[]" multiple size="8"
                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                    {{-- Значение — идентификатор из константы, а не перевод: на
                         другом языке в базу писалась бы другая строка. --}}
                    <option value="{{ DeliveryMethod::ALL_REGIONS }}"
                            {{ in_array(DeliveryMethod::ALL_REGIONS, $selectedRegions, true) ? 'selected' : '' }}>
                        {{ __('admin.delivery.all_regions') }}
                    </option>

                    @foreach(DeliveryMethod::getRussianRegions() as $region)
                        @if($region !== DeliveryMethod::ALL_REGIONS)
                            <option value="{{ $region }}" {{ in_array($region, $selectedRegions, true) ? 'selected' : '' }}>
                                {{ $region }}
                            </option>
                        @endif
                    @endforeach
                </select>

                <p class="admin-hint mt-1">{{ __('admin.delivery.f_regions_hint') }}</p>
            </section>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <button type="submit"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 text-sm font-semibold shadow-sm transition">
            <i class="fas fa-floppy-disk"></i> {{ __('admin.delivery.save') }}
        </button>

        <a href="{{ route('admin.delivery.index') }}"
           class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-5 py-2.5 text-sm font-semibold transition">
            {{ __('admin.delivery.cancel') }}
        </a>
    </div>
</div>

@push('styles')
<style>
    /* Форма службы доставки. Литеральный CSS: в статической сборке
       Tailwind нет ни произвольных значений, ни нужных вариантов. */

    .dm-cols{ display:grid; gap:1rem; align-items:start; margin-bottom:1rem }
    @media (min-width:1180px){ .dm-cols{ grid-template-columns:1.25fr 1fr } }

    /* Типографика как в «Оплате» и на страницах входа: подписи полей и
       заголовки секций — моноширинным, мелко, капсом, с крупным
       просветом. Второй шрифт не нужен — системный моноширинный стек уже
       используется в проекте для ключей и кодов. */
    .dm-form label:not(.dm-type):not(.dm-switch),
    .dm-form h2{ font-family:ui-monospace, SFMono-Regular, Menlo, monospace }
    .dm-form h2{ font-size:.7rem; letter-spacing:.12em }
    .dm-form .dm-field-label{ font-size:.66rem; font-weight:700;
        letter-spacing:.1em; text-transform:uppercase }

    .dm-types { display:grid; grid-template-columns:repeat(auto-fill, minmax(min(100%, 150px), 1fr)); gap:.5rem }

    .dm-type { display:block; padding:.75rem; cursor:pointer; text-align:center;
        border:1px solid #e5e7eb; background:#fff; transition:border-color .15s, box-shadow .15s }
    .dm-type:hover { border-color:var(--dl, #6366f1) }
    /* Выбранная карточка — в фирменном цвете службы, а не общим индиго:
       так видно, что выбрано, ещё до чтения подписи. */
    .dm-type.is-active { border-color:var(--dl, #6366f1);
        box-shadow:0 0 0 1px var(--dl, #6366f1),
                   0 6px 16px color-mix(in srgb, var(--dl, #6366f1) 20%, transparent) }
    .dark .dm-type { background:#111827; border-color:#374151 }

    /* Место под знак службы: если файл положен — логотип, если нет —
       значок типа доставки. Размер один и тот же, чтобы ряд карточек не
       прыгал по высоте. */
    .dm-type__mark { display:flex; align-items:center; justify-content:center;
        width:2.25rem; height:2.25rem; margin:0 auto .35rem; overflow:hidden }
    .dm-type__mark img { width:100%; height:100%; object-fit:cover; display:block }
    .dm-type__mark i { font-size:1.05rem; color:var(--dl, #6366f1) }

    .dm-type__name { display:block; font-weight:700; font-size:.8rem; color:#111827 }
    .dark .dm-type__name { color:#f3f4f6 }

    /* Блок своей службы выделен подложкой: это не обычные поля формы, а
       ветка «всё задаю сам», и её видно сразу. */
    .dm-custom{ padding:.85rem; background:#f9fafb; border:1px solid #e5e7eb }
    .dark .dm-custom{ background:#0f172a; border-color:#374151 }

    .dm-switches{ display:flex; flex-wrap:wrap; align-items:center; gap:1.25rem }
    .dm-switch{ display:inline-flex; align-items:center; gap:.6rem; cursor:pointer }
    .dm-switch__text{ font-size:.85rem; color:#374151 }
    .dark .dm-switch__text{ color:#d1d5db }
</style>
@endpush
