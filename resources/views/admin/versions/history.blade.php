@extends('layouts.admin')

@section('title', 'История версий')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">🔄 История версий</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ class_basename($model) }} #{{ $model->id }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">Версия</th>
                        <th class="px-4 py-3 text-left">Автор</th>
                        <th class="px-4 py-3 text-left">Изменения</th>
                        <th class="px-4 py-3 text-left">Дата</th>
                        <th class="px-4 py-3 text-left">Статус</th>
                        <th class="px-4 py-3 text-left">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($versions as $version)
                        <tr>
                            <td class="px-4 py-3 font-mono">{{ $version->version_number }}</td>
                            <td class="px-4 py-3">
                                {{ $version->user?->name ?? 'Система' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm">{{ Str::limit($version->changes ?? 'Обновление', 50) }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ $version->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                @if($version->is_current)
                                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Текущая</span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs">Архив</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    @if(!$version->is_current)
                                        <button onclick="restoreVersion({{ $version->id }})" 
                                            class="text-blue-600 hover:text-blue-800" title="Восстановить">
                                            🔄
                                        </button>
                                    @endif
                                    @if($loop->index > 0)
                                        <button onclick="compareVersions({{ $versions[$loop->index - 1]->id }}, {{ $version->id }})" 
                                            class="text-purple-600 hover:text-purple-800" title="Сравнить">
                                            🔍
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Модальное окно сравнения --}}
<div id="compareModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <h2 class="text-2xl font-bold mb-4">Сравнение версий</h2>
        <div id="compareContent"></div>
        <button onclick="closeCompareModal()" class="mt-4 px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg">
            Закрыть
        </button>
    </div>
</div>

<script>
function restoreVersion(versionId) {
    if (!confirm('Восстановить эту версию? Текущая версия будет сохранена.')) return;
    
    fetch(`/admin/versions/${versionId}/restore`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Версия восстановлена');
            location.reload();
        } else {
            alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
        }
    });
}

function compareVersions(version1Id, version2Id) {
    fetch('/admin/versions/compare', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            version1_id: version1Id,
            version2_id: version2Id
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showCompareModal(data.diff);
        } else {
            alert('Ошибка сравнения');
        }
    });
}

function showCompareModal(diff) {
    let html = '<div class="space-y-4">';
    
    if (diff.added && Object.keys(diff.added).length > 0) {
        html += '<div><h3 class="font-bold text-green-600">Добавлено:</h3><pre class="bg-green-50 p-2 rounded">' + JSON.stringify(diff.added, null, 2) + '</pre></div>';
    }
    
    if (diff.changed && Object.keys(diff.changed).length > 0) {
        html += '<div><h3 class="font-bold text-yellow-600">Изменено:</h3>';
        for (let key in diff.changed) {
            html += `<div class="mb-2"><strong>${key}:</strong><br>`;
            html += `<span class="text-red-600">- ${diff.changed[key].old}</span><br>`;
            html += `<span class="text-green-600">+ ${diff.changed[key].new}</span></div>`;
        }
        html += '</div>';
    }
    
    if (diff.removed && Object.keys(diff.removed).length > 0) {
        html += '<div><h3 class="font-bold text-red-600">Удалено:</h3><pre class="bg-red-50 p-2 rounded">' + JSON.stringify(diff.removed, null, 2) + '</pre></div>';
    }
    
    html += '</div>';
    
    document.getElementById('compareContent').innerHTML = html;
    document.getElementById('compareModal').classList.remove('hidden');
}

function closeCompareModal() {
    document.getElementById('compareModal').classList.add('hidden');
}
</script>
@endsection

