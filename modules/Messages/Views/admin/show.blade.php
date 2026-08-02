@extends('layouts.admin')

@section('title', $message->subject)

@section('content')
@php
    $incoming = $message->to_user_id === auth()->id();
@endphp

{{-- ── Шапка ── --}}
<div class="admin-accent-bar mb-0"></div>
<div class="admin-glass border border-t-0 border-gray-200 dark:border-gray-700 px-5 py-4 mb-4
            flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
    <div class="flex items-start gap-3 min-w-0">
        <span class="admin-icon-badge"><i class="fas fa-envelope-open-text"></i></span>
        <div class="min-w-0">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                @if($message->is_important)
                    <i class="fas fa-star msg-star" title="{{ __('admin.messages.is_important') }}"></i>
                @endif
                {{ $message->subject }}
            </h1>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.messages.sent_at') }}: {{ $message->created_at->format('d.m.Y H:i') }}
                @if($message->archived_at)
                    · <span class="msg-chip">{{ __('admin.messages.in_archive') }}</span>
                @endif
            </p>
        </div>
    </div>

    <a href="{{ route('admin.messages.index') }}"
       class="inline-flex items-center gap-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200
              hover:bg-gray-100 dark:hover:bg-gray-800 px-3 py-2 text-sm font-semibold transition flex-shrink-0">
        <i class="fas fa-arrow-left"></i> {{ __('admin.messages.back') }}
    </a>
</div>

{{-- ── Действия ── --}}
<div class="msg-toolbar admin-card mb-4">
    <a href="{{ route('admin.messages.reply', $message) }}"
       class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white
              px-4 py-2 text-sm font-semibold shadow-sm transition">
        <i class="fas fa-reply"></i> {{ __('admin.messages.reply') }}
    </a>

    @if($incoming)
        <button type="submit" form="msg-read" class="msg-action">
            <i class="fas {{ $message->is_read ? 'fa-envelope' : 'fa-envelope-open' }}"></i>
            {{ $message->is_read ? __('admin.messages.mark_unread') : __('admin.messages.mark_read') }}
        </button>
    @endif

    <button type="submit" form="msg-important" class="msg-action">
        <i class="fas fa-star {{ $message->is_important ? 'msg-star' : '' }}"></i>
        {{ $message->is_important ? __('admin.messages.unmark_important') : __('admin.messages.mark_important') }}
    </button>

    <button type="submit" form="msg-archive" class="msg-action">
        <i class="fas fa-box-archive"></i>
        {{ $message->archived_at ? __('admin.messages.unarchive') : __('admin.messages.archive') }}
    </button>

    <button type="submit" form="msg-destroy" class="msg-action msg-action--danger"
            onclick="return confirm(@js(__('admin.messages.delete_confirm')))">
        <i class="fas fa-trash"></i> {{ __('admin.messages.delete') }}
    </button>
</div>

{{-- Формы действий вынесены из панели: раньше это были вложенные form
     внутри одного контейнера, а кнопки — с собственными обработчиками. --}}
@if($incoming)
    <form id="msg-read" method="POST" action="{{ route('admin.messages.toggle-read', $message) }}" class="hidden">@csrf</form>
@endif
<form id="msg-important" method="POST" action="{{ route('admin.messages.toggle-important', $message) }}" class="hidden">@csrf</form>
<form id="msg-archive" method="POST" action="{{ route('admin.messages.archive', $message) }}" class="hidden">@csrf</form>
<form id="msg-destroy" method="POST" action="{{ route('admin.messages.destroy', $message) }}" class="hidden">@csrf @method('DELETE')</form>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2">

        {{-- ── Письмо ── --}}
        <section class="admin-card p-5 mb-4">
            <div class="msg-people">
                <span class="msg-avatar">{{ mb_strtoupper(mb_substr($message->sender->name ?? '?', 0, 1)) }}</span>

                <dl class="msg-people__list">
                    <div><dt>{{ __('admin.messages.sender') }}</dt>
                        <dd>{{ $message->sender->name ?? '—' }}
                            <span class="msg-email">{{ $message->sender->email ?? '' }}</span></dd></div>

                    <div><dt>{{ __('admin.messages.receiver') }}</dt>
                        <dd>{{ $message->receiver->name ?? '—' }}
                            <span class="msg-email">{{ $message->receiver->email ?? '' }}</span></dd></div>
                </dl>
            </div>

            @if($message->parent_id && $message->parent)
                <a href="{{ route('admin.messages.show', $message->parent) }}" class="msg-parent">
                    <i class="fas fa-turn-up"></i>
                    {{ __('admin.messages.replying_to', ['subject' => $message->parent->subject]) }}
                </a>
            @endif

            <div class="msg-body">{!! nl2br(e($message->body)) !!}</div>
        </section>

        {{-- ── Вложения ── --}}
        @if($message->attachments->count())
            <section class="admin-card p-5 mb-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">
                    <i class="fas fa-paperclip text-indigo-500"></i>
                    {{ __('admin.messages.attachments_n', ['count' => $message->attachments->count()]) }}
                </h2>

                <ul class="msg-attach">
                    @foreach($message->attachments as $attachment)
                        <li>
                            <i class="fas fa-file"></i>
                            <span class="msg-attach__name">{{ $attachment->filename }}</span>
                            <span class="msg-attach__size">{{ $attachment->human_size }}</span>

                            <a href="{{ route('admin.messages.attachment.download', $attachment) }}"
                               class="msg-attach__get">
                                <i class="fas fa-download"></i> {{ __('admin.messages.download') }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- ── Ответы ── --}}
        @if($message->replies->count())
            <section class="admin-card p-5">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">
                    <i class="fas fa-comments text-indigo-500"></i> {{ __('admin.messages.replies') }}
                </h2>

                @foreach($message->replies as $reply)
                    <a href="{{ route('admin.messages.show', $reply) }}" class="msg-reply">
                        <strong>{{ $reply->subject }}</strong>
                        <span class="msg-reply__meta">
                            {{ $reply->sender->name ?? '—' }} · {{ $reply->created_at->format('d.m.Y H:i') }}
                        </span>
                    </a>
                @endforeach
            </section>
        @endif
    </div>

    {{-- ── Цепочка ── --}}
    @if(($thread ?? null) && $thread->count() > 1)
        <aside class="admin-card p-5 self-start">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400 mb-3">
                <i class="fas fa-list text-indigo-500"></i> {{ __('admin.messages.thread') }}
            </h2>

            <ol class="msg-thread">
                @foreach($thread as $item)
                    <li>
                        <a href="{{ route('admin.messages.show', $item) }}"
                           class="{{ $item->id === $message->id ? 'is-current' : '' }}">
                            <span class="msg-thread__subject">{{ $item->subject }}</span>
                            <span class="msg-thread__meta">{{ $item->created_at->format('d.m.Y H:i') }}</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </aside>
    @endif
</div>
@endsection

@push('styles')
<style>
    /* Литеральный CSS: в статической сборке Tailwind нет ни произвольных
       значений, ни прозрачности через /NN. */
    .msg-toolbar{ display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; padding:.65rem .85rem }
    .msg-action{ display:inline-flex; align-items:center; gap:.45rem; padding:.5rem .85rem; font-size:.85rem;
                 font-weight:600; color:#475569; background:transparent; border:1px solid #e5e7eb;
                 cursor:pointer; transition:.15s }
    .msg-action:hover{ border-color:#c7d2fe; color:#4f46e5 }
    .msg-action--danger:hover{ border-color:#fecaca; color:#dc2626 }
    .msg-star{ color:#f59e0b }

    .msg-people{ display:flex; gap:.9rem; align-items:flex-start; padding-bottom:1rem;
                 border-bottom:1px solid #f1f5f9; margin-bottom:1rem }
    .msg-avatar{ display:flex; align-items:center; justify-content:center; width:2.75rem; height:2.75rem;
                 background:#4f46e5; color:#fff; font-weight:700; font-size:1.1rem; flex-shrink:0 }
    .msg-people__list{ display:grid; gap:.3rem; font-size:.875rem; min-width:0 }
    .msg-people__list > div{ display:flex; gap:.5rem; flex-wrap:wrap; align-items:baseline }
    .msg-people__list dt{ color:#6b7280; min-width:6rem }
    .msg-people__list dd{ margin:0; font-weight:600; color:#111827 }
    .msg-email{ font-weight:400; color:#9ca3af; font-size:.8rem }

    .msg-parent{ display:inline-flex; align-items:center; gap:.45rem; font-size:.8rem; color:#4f46e5;
                 margin-bottom:1rem }

    .msg-body{ font-size:.95rem; line-height:1.65; color:#111827; word-break:break-word }

    .msg-attach{ display:grid; gap:.35rem; font-size:.875rem }
    .msg-attach li{ display:flex; align-items:center; gap:.6rem; padding:.5rem .65rem; border:1px solid #eef2f7 }
    .msg-attach i{ color:#94a3b8 }
    .msg-attach__name{ flex:1; min-width:0; word-break:break-all; color:#111827 }
    .msg-attach__size{ font-size:.75rem; color:#9ca3af; white-space:nowrap }
    .msg-attach__get{ display:inline-flex; align-items:center; gap:.35rem; font-weight:600; color:#4f46e5;
                      white-space:nowrap }

    .msg-reply{ display:block; padding:.6rem .75rem; border:1px solid #eef2f7; margin-bottom:.4rem;
                transition:border-color .15s }
    .msg-reply:hover{ border-color:#c7d2fe }
    .msg-reply strong{ display:block; color:#111827; font-size:.9rem }
    .msg-reply__meta{ font-size:.75rem; color:#6b7280 }

    .msg-thread{ display:grid; gap:.3rem; font-size:.85rem }
    .msg-thread a{ display:block; padding:.5rem .65rem; border-left:2px solid #e5e7eb; color:#475569 }
    .msg-thread a:hover{ border-color:#c7d2fe; color:#4f46e5 }
    .msg-thread a.is-current{ border-color:#4f46e5; color:#4f46e5; font-weight:700; background:#f5f7ff }
    .msg-thread__subject{ display:block; word-break:break-word }
    .msg-thread__meta{ font-size:.72rem; color:#9ca3af }

    .msg-chip{ display:inline-block; font-size:.68rem; font-weight:700; padding:.12rem .45rem;
               border:1px solid #e5e7eb; color:#6b7280 }

    @media (prefers-color-scheme: dark){
        .msg-action{ border-color:#374151; color:#cbd5e1 }
        .msg-people{ border-color:#1f2937 }
        .msg-people__list dd, .msg-body, .msg-attach__name, .msg-reply strong{ color:#f3f4f6 }
        .msg-attach li, .msg-reply{ border-color:#374151 }
        .msg-thread a{ border-color:#374151 }
        .msg-thread a.is-current{ background:#1e1b4b }
    }
</style>
@endpush
