@extends('layouts.admin')

@php
    $isReply = isset($replyTo);
    $heading = $isReply ? __('admin.messages.reply_title') : __('admin.messages.create_title');
@endphp

@section('title', $heading)

@section('content')
{{-- ── Шапка ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-6
            flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas {{ $isReply ? 'fa-reply' : 'fa-pen-to-square' }}"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $heading }}</h1>

            @if($isReply)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.messages.replying_to', ['subject' => $replyTo->subject]) }}
                </p>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.messages.subtitle') }}</p>
            @endif
        </div>
    </div>

    <a href="{{ route('admin.messages.index') }}"
       class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
              hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition flex-shrink-0">
        <i class="fas fa-arrow-left"></i> {{ __('admin.messages.back') }}
    </a>
</div>

{{-- Форма была одной узкой колонкой по центру (52rem) — на широком экране
     по бокам оставалась пустота, а поле текста при этом жалось в ширину.

     Теперь: получатель и тема делят верхний ряд, ниже слева — само письмо,
     справа — вложения и пометка. То же устройство, что у редактора
     фрагментов: содержимое слева, свойства справа. --}}
<form method="POST" action="{{ route('admin.messages.store') }}" enctype="multipart/form-data"
      class="msg-form" x-data="messageForm()" @submit="sending = true">
    @csrf

    @if($isReply)
        <input type="hidden" name="parent_id" value="{{ $replyTo->id }}">
    @endif

    <div class="admin-card p-5 msg-head">
    {{-- ── Получатель ── --}}
    <div class="msg-field">
        <label for="to_user_id" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
            {{ __('admin.messages.to_user') }} <span class="text-red-500">*</span>
        </label>

        <select name="to_user_id" id="to_user_id" required
                class="w-full border px-3 py-2 text-sm dark:bg-gray-800
                       @error('to_user_id') border-red-500 @else border-gray-300 dark:border-gray-600 @enderror">
            <option value="">{{ __('admin.messages.to_user_ph') }}</option>
            @foreach($admins as $admin)
                <option value="{{ $admin->id }}"
                    @selected(old('to_user_id', $isReply ? $replyTo->user_id : null) == $admin->id)>
                    {{ $admin->name }} ({{ $admin->email }})
                </option>
            @endforeach
        </select>

        @error('to_user_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- ── Тема ── --}}
    <div class="msg-field">
        <label for="subject" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
            {{ __('admin.messages.subject') }} <span class="text-red-500">*</span>
        </label>

        <input type="text" name="subject" id="subject" required maxlength="255"
               value="{{ old('subject', $isReply ? 'Re: ' . $replyTo->subject : '') }}"
               placeholder="{{ __('admin.messages.subject_ph') }}"
               class="w-full border px-3 py-2 text-sm dark:bg-gray-800
                      @error('subject') border-red-500 @else border-gray-300 dark:border-gray-600 @enderror">

        @error('subject')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    </div>{{-- /msg-head --}}

    <div class="msg-grid">
    <section class="admin-card p-5 msg-main">
    {{-- ── Текст ── --}}
    <div>
        <label for="body" class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
            {{ __('admin.messages.body') }} <span class="text-red-500">*</span>
        </label>

        <textarea name="body" id="body" rows="10" required x-model="body"
                  placeholder="{{ __('admin.messages.body_ph') }}"
                  class="w-full border px-3 py-2 text-sm dark:bg-gray-800 resize-y
                         @error('body') border-red-500 @else border-gray-300 dark:border-gray-600 @enderror">{{ old('body') }}</textarea>

        <div class="flex flex-wrap items-center gap-3 mt-1">
            <p class="admin-hint" x-text="@js(__('admin.messages.chars')).replace(':count', body.length)"></p>

            {{-- Черновик пишется в localStorage. Раньше о нём сообщал alert(),
                 который на возврате к форме всплывал прежде самой страницы. --}}
            <span class="msg-draft" x-show="draftNote" x-cloak x-text="draftNote"></span>

            <button type="button" x-show="draftNote" x-cloak @click="dropDraft()"
                    class="text-sm font-semibold text-red-600 hover:underline">
                {{ __('admin.messages.draft_drop') }}
            </button>
        </div>

        @error('body')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    </section>

    <aside class="admin-card p-5 msg-side">
    {{-- ── Вложения ── --}}
    <div class="mb-5">
        <span class="block text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">
            {{ __('admin.messages.files') }}
        </span>

        <label for="attachments" class="msg-drop" :class="files.length && 'is-filled'">
            <i class="fas fa-paperclip"></i>
            <span x-text="files.length
                ? @js(__('admin.messages.files_chosen')).replace(':count', files.length)
                : @js(__('admin.messages.files_pick'))"></span>
        </label>

        <input type="file" name="attachments[]" id="attachments" multiple class="hidden"
               accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.log,.csv,.jpg,.jpeg,.png,.gif,.webp,.zip,.rar"
               @change="files = Array.from($event.target.files).map(f => f.name)">

        <ul class="msg-files" x-show="files.length" x-cloak>
            <template x-for="name in files" :key="name">
                <li><i class="fas fa-file"></i> <span x-text="name"></span></li>
            </template>
        </ul>

        <p class="admin-hint mt-1">{{ __('admin.messages.files_hint') }}</p>

        @error('attachments.*')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- ── Важное ── --}}
    <div>
        {{-- Тумблер был на peer-checked, которого в этой сборке Tailwind нет:
             кнопка не ехала и цвет не менялся. Общий класс admin-toggle
             сделан настоящим CSS-селектором. --}}
        <div class="flex items-center gap-3">
            <label class="admin-toggle">
                <input type="checkbox" name="is_important" value="1" @checked(old('is_important'))>
                <span class="track"></span>
                <span class="knob"></span>
            </label>

            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                <i class="fas fa-star msg-star"></i> {{ __('admin.messages.important') }}
            </span>
        </div>

        <p class="admin-hint mt-1">{{ __('admin.messages.important_hint') }}</p>
    </div>

    </aside>
    </div>{{-- /msg-grid --}}

    <div class="admin-card p-4 msg-actions">
        <a href="{{ route('admin.messages.index') }}"
           class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
                  hover:bg-gray-100 dark:hover:bg-gray-800 px-4 py-2.5 text-sm font-semibold transition">
            {{ __('admin.messages.cancel') }}
        </a>

        <button type="submit" :disabled="sending"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
                       px-5 py-2.5 text-sm font-semibold shadow-sm transition">
            <i class="fas fa-paper-plane"></i>
            <span x-text="sending ? @js(__('admin.messages.sending')) : @js(__('admin.messages.send'))"></span>
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function messageForm() {
        const key = 'msg-draft-{{ $isReply ? $replyTo->id : 'new' }}';

        return {
            body: @js(old('body', '')),
            files: [],
            sending: false,
            draftNote: '',

            init() {
                // Черновик восстанавливаем только если поле пустое: иначе
                // затёрли бы то, что вернула валидация.
                const saved = localStorage.getItem(key);

                if (saved && this.body === '') {
                    this.body = saved;
                    this.draftNote = @js(__('admin.messages.draft_restored'));
                }

                this.$watch('body', value => {
                    if (this.sending) return;
                    value ? localStorage.setItem(key, value) : localStorage.removeItem(key);
                });

                // Письмо ушло — черновик больше не нужен.
                this.$el.addEventListener('submit', () => localStorage.removeItem(key));
            },

            dropDraft() {
                localStorage.removeItem(key);
                this.body = '';
                this.draftNote = '';
            },
        };
    }
</script>
@endpush

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни прозрачности через /NN. */
    .msg-form{ max-width:52rem; margin-inline:auto }
    .msg-grid{ display:grid; gap:1rem; margin-top:1rem }
    .msg-head{ display:grid; gap:1rem }
    .msg-actions{ display:flex; flex-wrap:wrap; justify-content:flex-end; gap:.5rem; margin-top:1rem }

    /* Поле текста тянется на всю высоту своей колонки: справа стоят
       вложения и пометка, они короче, и без этого под ними зияла бы
       пустота. */
    .msg-main{ display:flex; flex-direction:column }
    .msg-main > div{ display:flex; flex-direction:column; flex:1 }
    .msg-main textarea{ flex:1; min-height:18rem }

    /* На широком экране — две колонки.
       Форма была прибита к 52rem и центрировалась: на мониторе шире полутора
       тысяч пикселей по бокам оставалось пусто, а поле письма при этом жалось
       в ширину. Верхний ряд делят получатель и тема — два коротких поля, им
       целая строка каждому не нужна. */
    @media (min-width:1280px){
        .msg-form{ max-width:82rem }
        .msg-head{ grid-template-columns:1fr 1fr }
        .msg-grid{ grid-template-columns:minmax(0,1fr) 22rem; align-items:start }
    }

    .msg-drop{ display:flex; flex-direction:column; align-items:center; justify-content:center; gap:.5rem;
               border:2px dashed #cbd5e1; padding:1.25rem 1rem; cursor:pointer; text-align:center;
               font-size:.875rem; color:#64748b; transition:border-color .15s, color .15s }
    .msg-drop:hover{ border-color:#818cf8; color:#4f46e5 }
    .msg-drop i{ font-size:1.35rem; color:#94a3b8 }
    .msg-drop.is-filled{ border-style:solid; border-color:#818cf8; color:#4f46e5 }
    .msg-drop.is-filled i{ color:#6366f1 }

    .msg-files{ margin-top:.5rem; display:grid; gap:.25rem; font-size:.8rem; color:#475569 }
    .msg-files li{ display:flex; align-items:center; gap:.4rem }
    .msg-files i{ color:#94a3b8 }

    .msg-star{ color:#f59e0b }
    .msg-draft{ font-size:.8rem; font-weight:600; color:#4f46e5 }

    /* ⚠️ Здесь стоял блок @media (prefers-color-scheme: dark) — это
       настройка ОПЕРАЦИОННОЙ СИСТЕМЫ, а не оформление панели. При тёмной
       системе и светлой панели он перекрашивал текст в почти белый на
       белом фоне: сумма заказа пропадала совсем. Тему панели задают класс
       .dark и переменные --admin-*; перекрытие по настройке ОС их только
       ломало. */
</style>
@endpush
