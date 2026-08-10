@extends('layouts.admin')

@section('title', __('admin.system.er_title'))

@section('content')
{{-- ── Шапка ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex items-center gap-3">
    <span class="admin-icon-badge"><i class="fas fa-bug"></i></span>
    <div class="min-w-0">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('admin.system.er_title') }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.system.er_subtitle') }}</p>
    </div>
</div>

@unless($mailReady)
    {{-- Раньше это выяснялось только после отправки — падением на 500. --}}
    <div class="er-warn mb-4">
        <i class="fas fa-envelope-circle-check"></i>
        <div>
            <p class="font-semibold">{{ __('admin.system.er_mail_off') }}</p>
            <p>{{ __('admin.system.er_mail_off_hint') }}</p>
        </div>
    </div>
@endunless

<form action="{{ route('admin.error.report.send') }}" method="POST" enctype="multipart/form-data"
      class="admin-card p-5 er-form"
      x-data="{
          file: '',
          size: '',
          text: @js(old('message', '')),
          sent: false,
          get ready() { return this.text.trim().length >= 10; }
      }">
    @csrf

    {{-- Две колонки: слева суть обращения, справа вложение и отправка.
         Форма стояла узким столбцом по центру, и половина ширины экрана
         пустовала, а поле «что случилось» — главное здесь — было не шире
         подписи под ним. --}}
    <div class="er-cols">
      <div class="er-col er-col--main">
        {{-- ── Сообщение ── --}}
        <div class="mb-4">
            <label for="message" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                {{ __('admin.system.er_message') }} <span class="text-red-500">*</span>
            </label>
    
            <textarea id="message" name="message" rows="6" required minlength="10"
                      x-model="text"
                      placeholder="{{ __('admin.system.er_message_ph') }}"
                      class="w-full border px-3 py-2 text-sm dark:bg-gray-800 resize-y
                             @error('message') border-red-500 @else border-gray-300 dark:border-gray-600 @enderror">{{ old('message') }}</textarea>
    
            @error('message')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
    
            {{-- Счётчик вместо статичной подписи «минимум 10 символов»: видно,
                 сколько уже набрано и сколько осталось, а не только требование. --}}
            <div class="er-help">
                <span>{{ __('admin.system.er_message_hint_short') }}</span>
                <span class="er-count" :class="ready && 'er-count--ok'"
                      x-text="ready
                          ? @js(__('admin.system.er_count_ok'))
                          : @js(__('admin.system.er_count_left')).replace(':n', Math.max(0, 10 - text.trim().length))"></span>
            </div>
        </div>
    
        {{-- ── E-mail ── --}}
        <div class="mb-4">
            <label for="email" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                {{ __('admin.system.er_email') }}
            </label>
    
            <input type="email" id="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}"
                   placeholder="{{ __('admin.system.er_email_ph') }}"
                   class="w-full border px-3 py-2 text-sm dark:bg-gray-800
                          @error('email') border-red-500 @else border-gray-300 dark:border-gray-600 @enderror">
    
            @error('email')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @else
                <p class="er-help">{{ __('admin.system.er_email_hint') }}</p>
            @enderror
        </div>
    
      </div>

      <aside class="er-col er-col--side">
        {{-- ── Вложение ── --}}
        <div class="mb-4">
            <span class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
                {{ __('admin.system.er_file') }}
            </span>
    
            <label for="file" class="er-drop" :class="file && 'er-drop--filled'">
                <i class="fas fa-cloud-arrow-up"></i>
                <span x-text="file || @js(__('admin.system.er_file_pick'))"></span>
                {{-- Размер показываем сразу: ограничение в 2 МБ иначе всплывает
                     только после отправки, уже отказом. --}}
                <span class="er-size" x-show="size" x-cloak x-text="size"></span>
            </label>
    
            <input type="file" id="file" name="file" class="hidden"
                   accept=".jpg,.jpeg,.png,.gif,.webp,.txt,.log,.pdf"
                   @change="
                       const f = $event.target.files[0];
                       file = f ? f.name : '';
                       size = f ? (f.size / 1048576).toFixed(2).replace('.', ',') + ' МБ' : '';
                   ">
    
            <div class="flex flex-wrap items-center gap-3 mt-1">
                <p class="er-help">{{ __('admin.system.er_file_hint') }}</p>
    
                <button type="button" x-show="file" x-cloak
                        class="text-sm font-semibold text-red-600 hover:underline"
                        @click="file = ''; size = ''; document.getElementById('file').value = ''">
                    {{ __('admin.system.er_file_clear') }}
                </button>
            </div>
    
            @error('file')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
    
        {{-- Что уходит вместе с письмом — чтобы это не было сюрпризом. --}}
        <div class="er-context mb-4">
            <i class="fas fa-circle-info"></i>
            <span>
                {{ __('admin.system.er_context') }}:
                {{ __('admin.system.er_ctx_user') }} · {{ __('admin.system.er_ctx_ip') }} ·
                {{ __('admin.system.er_ctx_ua') }} · {{ __('admin.system.er_ctx_url') }}.
            </span>
        </div>
    
        <div class="er-actions">
            {{-- Кнопка выключена, пока сообщение короче требуемого: раньше об
                 этом узнавали только после отправки, из ответа сервера.
                 Состояние берётся из формы, а не из собственного x-data —
                 иначе кнопка не знала бы про длину текста. --}}
            <button type="submit" @click="sent = true" :disabled="sent || !ready"
                    class="er-send inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                           px-5 py-2.5 text-sm font-semibold shadow-sm transition">
                <i class="fas fa-paper-plane"></i>
                <span x-text="sent ? @js(__('admin.system.er_sending')) : @js(__('admin.system.er_send'))"></span>
            </button>
        </div>
      </aside>
    </div>
</form>
@endsection

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни прозрачности через /NN. */
    /* Форма занимает всю ширину: узкий столбец по центру оставлял половину
       экрана пустой, а поле «что случилось» было не шире подписи под ним. */
    .er-form{ max-width:none }

    .er-cols{ display:grid; grid-template-columns:1fr; gap:1.25rem }
    @media (min-width:1024px){
        /* Слева суть обращения, справа вложение и отправка. Правая колонка
           уже: там короткие блоки, а текст письма должен быть просторным. */
        .er-cols{ grid-template-columns:minmax(0,1.75fr) minmax(17rem,1fr); gap:1.75rem }
    }

    .er-col--side{ display:flex; flex-direction:column; gap:1rem }
    /* Кнопка прижата к низу колонки: рядом с вложением и перечнем того,
       что уйдёт вместе с письмом. */
    .er-col--side > *:last-child{ margin-top:auto }

    .er-actions{ display:flex }
    .er-actions .er-send{ width:100%; justify-content:center }

    /* Поле сообщения выше: это главное здесь, и запас места экономит
       прокрутку при длинном описании. */
    .er-form textarea{ min-height:14rem }

    /* Подсказка под полем.
       Раньше тут стоял класс врезки-примечания: он рисует заливку и
       полосу слева, и три коротких подсказки подряд читались как
       выделенный текст, а не как пояснения к полям. */
    .er-help{ display:flex; align-items:baseline; justify-content:space-between;
              gap:.75rem; margin-top:.35rem; font-size:.78rem; color:#64748b }

    .er-count{ flex:none; font-weight:600; color:#b45309;
               font-variant-numeric:tabular-nums }
    .er-count--ok{ color:#15803d }

    .er-size{ font-size:.72rem; opacity:.75 }

    /* Выключенная кнопка не должна выглядеть нажимаемой. */
    .er-send:disabled{ opacity:.5; cursor:not-allowed }
    .er-send:disabled:hover{ background:#4f46e5 }

    .er-drop{ display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.5rem;
              border:2px dashed #cbd5e1; padding:1.5rem 1rem; cursor:pointer; text-align:center;
              font-size:.875rem; color:#64748b; transition:border-color .15s, color .15s }
    .er-drop:hover{ border-color:#818cf8; color:#4f46e5 }
    .er-drop i{ font-size:1.5rem; color:#94a3b8 }
    .er-drop--filled{ border-style:solid; border-color:#818cf8; color:#4f46e5 }
    .er-drop--filled i{ color:#6366f1 }

    .er-context{ display:flex; gap:.6rem; align-items:flex-start; font-size:.8rem; color:#475569;
                 background:#f8fafc; border:1px solid #eef2f7; padding:.65rem .85rem }

    .er-warn{ display:flex; gap:.75rem; align-items:flex-start; font-size:.875rem;
              color:#92400e; background:#fffbeb; border:1px solid #fde68a; padding:.85rem 1rem }
    .er-warn i{ font-size:1.1rem; margin-top:.1rem }

    /* ⚠️ Здесь стоял блок @media (prefers-color-scheme: dark) — это
       настройка ОПЕРАЦИОННОЙ СИСТЕМЫ, а не оформление панели. При тёмной
       системе и светлой панели он перекрашивал текст в почти белый на
       белом фоне: сумма заказа пропадала совсем. Тему панели задают класс
       .dark и переменные --admin-*; перекрытие по настройке ОС их только
       ломало. */
</style>
@endpush
