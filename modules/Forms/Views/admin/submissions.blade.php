{{--
    Заявки по форме.

    Постраничный вывод — тот же общий компонент, что во всех списках проекта
    (resources/views/vendor/pagination/tailwind.blade.php).
--}}
@extends('layouts.admin')

@section('title', __('admin.forms.submissions_title', ['title' => $form->title]))

@section('content')
<div class="max-w-screen-2xl mx-auto">

    <div class="admin-card mb-5">
        <div class="admin-accent-bar" aria-hidden="true"></div>
        <div class="p-5 flex flex-wrap items-center gap-4">
            <span class="admin-icon-badge" aria-hidden="true"><i class="fa-regular fa-envelope"></i></span>
            <div class="min-w-0 flex-1">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $form->title }}</h1>
                <p class="text-sm text-gray-500">
                    {{ __('admin.forms.submissions_subtitle', ['total' => $form->submissions_count]) }}
                </p>
            </div>
            <a href="{{ route('admin.forms.index') }}" class="fs-btn">
                <i class="fas fa-arrow-left"></i> {{ __('admin.forms.back_to_forms') }}
            </a>
        </div>
    </div>

    @forelse($submissions as $submission)
        <div class="admin-card p-4 mb-3 {{ $submission->is_read ? '' : 'fs-new' }}">
            <div class="flex flex-wrap items-center gap-3 mb-3">
                <span class="fs-date">{{ $submission->created_at->format('d.m.Y H:i') }}</span>
                @unless($submission->is_read)
                    <span class="fs-badge">{{ __('admin.forms.stat_new') }}</span>
                @endunless
                <span class="fs-meta">IP {{ $submission->ip ?: '—' }}</span>
                @if($submission->page)
                    <a href="{{ $submission->page }}" target="_blank" rel="noopener" class="fs-meta fs-link">
                        {{ __('admin.forms.from_page') }}
                    </a>
                @endif

                <div class="ml-auto flex items-center gap-1.5">
                    <button type="button" class="fs-icon-btn"
                            title="{{ $submission->is_read ? __('admin.forms.mark_unread') : __('admin.forms.mark_read') }}"
                            onclick="toggleRead({{ $submission->id }}, this)">
                        <i class="fa-regular {{ $submission->is_read ? 'fa-envelope-open' : 'fa-envelope' }}"></i>
                    </button>

                    <form method="POST" action="{{ route('admin.forms.submissions.destroy', [$form, $submission]) }}"
                          onsubmit="return confirm(@js(__('admin.forms.confirm_delete_submission')))">
                        @csrf @method('DELETE')
                        <button type="submit" class="fs-icon-btn fs-icon-btn--danger" title="{{ __('admin.common.delete') }}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            <table class="fs-table">
                @foreach($submission->readableData() as $row)
                    <tr>
                        <th>{{ $row['label'] }}</th>
                        <td>{{ $row['value'] !== '' ? $row['value'] : '—' }}</td>
                    </tr>
                @endforeach
            </table>

            {{-- Вложения. Ссылка ведёт на маршрут под auth+admin: сами файлы
                 лежат на приватном диске и по прямому адресу недоступны. --}}
            @foreach((array) $submission->data as $name => $value)
                @if(is_array($value) && ! empty($value['path']))
                    <a href="{{ route('admin.forms.submissions.download', [$form, $submission, $name]) }}" class="fs-file">
                        <i class="fas fa-paperclip"></i> {{ $value['name'] ?? $name }}
                    </a>
                @endif
            @endforeach
        </div>
    @empty
        <div class="admin-card p-8 text-center">
            <span class="admin-icon-badge mx-auto mb-3"><i class="fa-regular fa-envelope-open"></i></span>
            <p class="text-sm text-gray-500">{{ __('admin.forms.submissions_empty') }}</p>
        </div>
    @endforelse

    <div class="mt-6">
        {{ $submissions->withQueryString()->links() }}
    </div>
</div>
@endsection

@push('styles')
<style>
    .fs-btn { display:inline-flex; align-items:center; gap:8px; padding:8px 16px; font-size:.82rem;
              font-weight:600; color:#4b5563; background:#fff; border:1px solid #d1d5db }
    .fs-btn:hover { background:#f3f4f6 }
    .fs-new { border-left:3px solid #4f46e5 }
    .fs-date { font-size:.82rem; font-weight:600; color:#111827 }
    .fs-badge { padding:1px 8px; font-size:.68rem; color:#fff; background:#4f46e5 }
    .fs-meta { font-size:.72rem; color:#9ca3af }
    .fs-link { color:#4338ca; text-decoration:underline }
    .fs-icon-btn { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px;
                   font-size:12px; color:#4b5563; background:#fff; border:1px solid #e5e7eb; cursor:pointer }
    .fs-icon-btn:hover { background:#f3f4f6; color:#111827 }
    .fs-icon-btn--danger:hover { background:#dc2626; border-color:#dc2626; color:#fff }
    .fs-table { width:100%; border-collapse:collapse; font-size:.84rem }
    .fs-table th { width:30%; padding:6px 10px; text-align:left; font-weight:600; color:#4b5563;
                   background:#f9fafb; border:1px solid #f3f4f6; vertical-align:top }
    .fs-table td { padding:6px 10px; color:#111827; border:1px solid #f3f4f6; vertical-align:top;
                   word-break:break-word }
    .fs-file { display:inline-flex; align-items:center; gap:7px; margin-top:8px; padding:5px 10px;
               font-size:.78rem; color:#4338ca; background:#eef2ff }
</style>
@endpush

@push('scripts')
<script>
    function toggleRead(id, button) {
        fetch(@js(route('admin.forms.submissions', $form)) + '/' + id, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then((response) => response.json())
        .then((data) => {
            if (!data.success) {
                throw new Error();
            }

            // Перезагружаем: от состояния зависит и полоса слева у карточки,
            // и счётчик новых на странице форм.
            window.location.reload();
        })
        .catch(() => alert(@js(__('admin.forms.action_failed'))));
    }
</script>
@endpush
