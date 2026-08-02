@php
    use Modules\Delivery\Console\Commands\SeedDefaultDeliveryMethodsCommand;
    use Modules\Delivery\Models\DeliveryMethod;

    $method = $method ?? null;

    // Какие ключи нужны какой службе — один источник с сидером. Форма
    // показывает только поля выбранной службы, а не все сразу.
    $credentialFields = SeedDefaultDeliveryMethodsCommand::credentialFields();

    // Названия служб — торговые марки, не переводятся. Переводится
    // только короткое пояснение под названием.
    $services = [
        'pochta' => ['label' => 'Почта России', 'icon' => 'fa-envelope', 'type' => 'post'],
        'cdek' => ['label' => 'СДЭК', 'icon' => 'fa-truck-fast', 'type' => 'courier'],
        'boxberry' => ['label' => 'Boxberry', 'icon' => 'fa-box', 'type' => 'terminal'],
        'yandex_delivery' => ['label' => 'Яндекс Доставка', 'icon' => 'fa-motorcycle', 'type' => 'courier'],
        'pickup' => ['label' => __('admin.delivery.m_pickup'), 'icon' => 'fa-store', 'type' => 'pickup'],
        'courier_local' => ['label' => __('admin.delivery.m_courier'), 'icon' => 'fa-person-biking', 'type' => 'courier'],
    ];

    // Подписи ключей — технические имена из документации служб, поэтому
    // не переводятся: владелец ищет ровно их в личном кабинете.
    $fieldLabels = [
        'token' => 'Token', 'login' => 'Login', 'password' => 'Password',
        'account' => 'Account', 'secure_password' => 'Secure password',
        'oauth_token' => 'OAuth token',
    ];

    $currentCode = old('code', $method->code ?? 'pickup');
    $settings = (array) old('api_settings', $method->api_settings ?? []);
    $selectedRegions = (array) old('regions', $method->regions ?? []);
@endphp

@csrf

<div x-data="{
        code: @js($currentCode),
        types: @js(collect($services)->map(fn ($s) => $s['type'])->all()),
        fields: @js($credentialFields),
        checking: false,
        checkResult: null,
        checkOk: false,
        get currentFields() { return this.fields[this.code] || []; },
        get currentType() { return this.types[this.code] || 'courier'; },
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

    {{-- ── Основное ── --}}
    <section class="admin-card p-5 mb-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">
            <i class="fas fa-tag text-indigo-500"></i> {{ __('admin.delivery.g_main') }}
        </h2>

        <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">
            {{ __('admin.delivery.f_service') }}
        </label>

        <div class="dm-types mb-4">
            @foreach($services as $value => $meta)
                <label class="dm-type" :class="code === @js($value) ? 'is-active' : ''">
                    <input type="radio" name="code" value="{{ $value }}" x-model="code" class="sr-only">
                    <i class="fas {{ $meta['icon'] }}"></i>
                    <span class="dm-type__name">{{ $meta['label'] }}</span>
                </label>
            @endforeach
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="title" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.delivery.f_name') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title" required
                       value="{{ old('title', $method->title ?? '') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                @error('title') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="sort_order" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.delivery.f_sort') }}
                </label>
                <input type="number" id="sort_order" name="sort_order"
                       value="{{ old('sort_order', $method->sort_order ?? 0) }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                <p class="admin-hint mt-1">{{ __('admin.delivery.f_sort_hint') }}</p>
            </div>

            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.delivery.f_desc') }}
                </label>
                <textarea id="description" name="description" rows="2"
                          placeholder="{{ __('admin.delivery.f_desc_ph') }}"
                          class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">{{ old('description', $method->description ?? '') }}</textarea>
            </div>
        </div>

        <p class="admin-hint mt-3">{{ __('admin.delivery.f_code_hint') }}</p>

        <div class="flex flex-wrap items-center gap-5 mt-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="active" value="0">
                <input type="checkbox" name="active" value="1" class="border-gray-400"
                       {{ old('active', $method->active ?? false) ? 'checked' : '' }}>
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.delivery.f_active') }}</span>
            </label>

            <label class="flex items-center gap-2 cursor-pointer">
                <input type="hidden" name="is_russian" value="0">
                <input type="checkbox" name="is_russian" value="1" class="border-gray-400"
                       {{ old('is_russian', $method->is_russian ?? true) ? 'checked' : '' }}>
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.delivery.ru_system') }}</span>
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
                <label for="price" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.delivery.f_price') }} <span class="text-red-500">*</span>
                </label>
                <input type="number" step="0.01" id="price" name="price" required
                       value="{{ old('price', $method->price ?? 0) }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                @error('price') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="free_delivery_threshold" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.delivery.f_free_from') }}
                </label>
                <input type="number" step="0.01" id="free_delivery_threshold" name="free_delivery_threshold"
                       value="{{ old('free_delivery_threshold', $method->free_delivery_threshold ?? '') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                <p class="admin-hint mt-1">{{ __('admin.delivery.f_free_hint') }}</p>
            </div>

            <div>
                <label for="weight_limit" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.delivery.f_weight') }}
                </label>
                <input type="number" step="0.01" id="weight_limit" name="weight_limit"
                       value="{{ old('weight_limit', $method->weight_limit ?? '') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                <p class="admin-hint mt-1">{{ __('admin.delivery.f_weight_hint') }}</p>
            </div>

            <div>
                <label for="min_days" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.delivery.f_min_days') }}
                </label>
                <input type="number" id="min_days" name="min_days"
                       value="{{ old('min_days', $method->min_days ?? '') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
            </div>

            <div>
                <label for="max_days" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                    {{ __('admin.delivery.f_max_days') }}
                </label>
                <input type="number" id="max_days" name="max_days"
                       value="{{ old('max_days', $method->max_days ?? '') }}"
                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
                @error('max_days') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- ── Интеграция ── --}}
    <section class="admin-card p-5 mb-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-4">
            <i class="fas fa-plug text-indigo-500"></i> {{ __('admin.delivery.g_api') }}
        </h2>

        <template x-if="currentFields.length === 0">
            <p class="admin-hint">{{ __('admin.delivery.creds_none') }}</p>
        </template>

        <template x-if="currentFields.length > 0">
            <div>
                <label class="flex items-center gap-2 cursor-pointer mb-4">
                    <input type="hidden" name="api_enabled" value="0">
                    <input type="checkbox" name="api_enabled" value="1" class="border-gray-400"
                           {{ old('api_enabled', $method->api_enabled ?? false) ? 'checked' : '' }}>
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.delivery.f_api_enabled') }}</span>
                </label>
            </div>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($fieldLabels as $field => $label)
                <div x-cloak x-show="currentFields.includes(@js($field))">
                    <label class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ $label }}</label>
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
            <label for="docs_url" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
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

        <label for="regions" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
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
    /* Карточки служб. Литеральный CSS: в статической сборке Tailwind нет
       ни произвольных значений, ни нужных вариантов. */
    .dm-types { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .5rem }
    .dm-type { display: block; padding: .75rem; cursor: pointer; text-align: center;
               border: 1px solid #e5e7eb; background: #fff; transition: border-color .15s, box-shadow .15s }
    .dm-type:hover { border-color: #a5b4fc }
    .dm-type.is-active { border-color: #6366f1; box-shadow: inset 0 0 0 1px #6366f1 }
    .dm-type i { font-size: 1.15rem; color: #6366f1 }
    .dm-type__name { display: block; font-weight: 700; font-size: .8rem; margin-top: .35rem; color: #111827 }
</style>
@endpush
