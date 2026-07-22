@extends('layouts.admin')

@section('title', 'Локализация')

@section('content')
<div class="container-fluid py-4">
    <!-- Заголовок -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">🌍 Управление локализацией</h1>
            <p class="text-muted mb-0">Настройка стран, валют, форматов даты и времени</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.localization.translations.index') }}" class="btn btn-dark">
                <i class="fas fa-language"></i> Переводы интерфейса
            </a>
            <a href="{{ route('admin.localization.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Добавить страну
            </a>
            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fas fa-download"></i> Импорт
            </button>
            <form action="{{ route('admin.localization.clear.cache') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="fas fa-sync"></i> Очистить кеш
                </button>
            </form>
        </div>
    </div>

    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Всего стран</h6>
                            <h3 class="mb-0">{{ $stats['total_countries'] }}</h3>
                        </div>
                        <i class="fas fa-globe fa-2x opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Активные</h6>
                            <h3 class="mb-0">{{ $stats['active_countries'] }}</h3>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Всего настроек</h6>
                            <h3 class="mb-0">{{ $stats['total_settings'] }}</h3>
                        </div>
                        <i class="fas fa-cog fa-2x opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Системные</h6>
                            <h3 class="mb-0">{{ $stats['system_settings'] }}</h3>
                        </div>
                        <i class="fas fa-shield-alt fa-2x opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Список стран -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Список стран</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Флаг</th>
                            <th>Код</th>
                            <th>Название</th>
                            <th>Валюта</th>
                            <th>Локаль</th>
                            <th>Настроек</th>
                            <th>Статус</th>
                            <th class="text-end">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($countries as $country)
                        <tr>
                            <td style="font-size: 1.5rem;">{{ $country->flag ?? '🏳️' }}</td>
                            <td><code>{{ $country->code }}</code></td>
                            <td>
                                <strong>{{ $country->name }}</strong>
                                @if($country->native_name)
                                    <br><small class="text-muted">{{ $country->native_name }}</small>
                                @endif
                            </td>
                            <td>
                                {{ $country->currency_code }}
                                @if($country->currency_symbol)
                                    ({{ $country->currency_symbol }})
                                @endif
                            </td>
                            <td><code>{{ $country->locale }}</code></td>
                            <td>
                                <span class="badge bg-secondary">{{ $country->settings_count }}</span>
                            </td>
                            <td>
                                @if($country->active)
                                    <span class="badge bg-success">Активна</span>
                                @else
                                    <span class="badge bg-secondary">Неактивна</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.localization.edit', $country->code) }}"
                                       class="btn btn-outline-primary" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('admin.localization.settings', $country->code) }}"
                                       class="btn btn-outline-secondary" title="Настройки">
                                        <i class="fas fa-cog"></i>
                                    </a>
                                    <form action="{{ route('admin.localization.destroy', $country->code) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Удалить страну {{ $country->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Удалить">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-globe fa-3x mb-3 d-block"></i>
                                Страны не найдены. Добавьте первую страну или импортируйте предустановки.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Модальное окно импорта -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Импорт предустановленных стран</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Выберите страны для импорта:</p>
                    @php
                        $presets = config('localization.preset_countries', []);
                    @endphp
                    <form action="{{ route('admin.localization.import.presets') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            @foreach($presets as $code => $data)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="countries[]"
                                       value="{{ $code }}" id="preset_{{ $code }}"
                                       {{ \Modules\Localization\Models\Country::where('code', $code)->exists() ? 'disabled' : '' }}>
                                <label class="form-check-label" for="preset_{{ $code }}">
                                    {{ $data['flag'] ?? '🏳️' }} {{ $data['name'] }} ({{ $code }})
                                    @if(\Modules\Localization\Models\Country::where('code', $code)->exists())
                                        <span class="text-success ms-2"><i class="fas fa-check"></i> Уже импортирована</span>
                                    @endif
                                </label>
                            </div>
                            @endforeach
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" class="btn btn-primary">Импортировать</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Автообновление статистики
    setInterval(async () => {
        try {
            const response = await fetch('{{ route("admin.localization.api.stats") }}');
            const data = await response.json();

            document.querySelector('.card.bg-primary h3').textContent = data.total_countries;
            document.querySelector('.card.bg-success h3').textContent = data.active_countries;
            document.querySelector('.card.bg-info h3').textContent = data.total_settings;
            document.querySelector('.card.bg-warning h3').textContent = data.system_settings;
        } catch (e) {
            console.error('Ошибка обновления статистики:', e);
        }
    }, 30000); // Каждые 30 секунд
</script>
@endpush
