@extends('layouts.admin')

@section('title', 'Резервное копирование')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">💾 Резервное копирование</h1>
        <div class="space-x-2">
            <form action="{{ route('admin.backups.create-database') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    📦 Создать бэкап БД
                </button>
            </form>
            <form action="{{ route('admin.backups.create-files') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                    📁 Создать бэкап файлов
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid md:grid-cols-2 gap-6">
        <!-- Бэкапы БД -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">📦 Бэкапы базы данных</h2>
            
            @if(empty($databaseBackups))
                <p class="text-gray-500">Бэкапы БД отсутствуют</p>
            @else
                <div class="space-y-2">
                    @foreach($databaseBackups as $backup)
                        <div class="border rounded p-3 flex justify-between items-center">
                            <div>
                                <p class="font-medium">{{ $backup['name'] }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ $backup['size_human'] }} • {{ $backup['created_at'] }}
                                </p>
                            </div>
                            <div class="space-x-2">
                                <a href="{{ route('admin.backups.download', ['type' => 'database', 'file' => $backup['name']]) }}" 
                                   class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                                    📥
                                </a>
                                <form action="{{ route('admin.backups.delete') }}" method="POST" class="inline" 
                                      onsubmit="return confirm('Удалить бэкап?')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="type" value="database">
                                    <input type="hidden" name="file" value="{{ $backup['name'] }}">
                                    <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Бэкапы файлов -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">📁 Бэкапы файлов</h2>
            
            @if(empty($filesBackups))
                <p class="text-gray-500">Бэкапы файлов отсутствуют</p>
            @else
                <div class="space-y-2">
                    @foreach($filesBackups as $backup)
                        <div class="border rounded p-3 flex justify-between items-center">
                            <div>
                                <p class="font-medium">{{ $backup['name'] }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ $backup['size_human'] }} • {{ $backup['created_at'] }}
                                </p>
                            </div>
                            <div class="space-x-2">
                                <a href="{{ route('admin.backups.download', ['type' => 'files', 'file' => $backup['name']]) }}" 
                                   class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">
                                    📥
                                </a>
                                <form action="{{ route('admin.backups.delete') }}" method="POST" class="inline"
                                      onsubmit="return confirm('Удалить бэкап?')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="type" value="files">
                                    <input type="hidden" name="file" value="{{ $backup['name'] }}">
                                    <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold mb-2">ℹ️ Информация</h3>
        <ul class="text-sm text-gray-700 space-y-1">
            <li>• Бэкапы БД создаются автоматически каждый день в 02:00</li>
            <li>• Бэкапы файлов создаются автоматически каждую неделю в 03:00</li>
            <li>• Старые бэкапы автоматически удаляются через 30 дней</li>
            <li>• Настройки можно изменить в <code>config/backup.php</code></li>
        </ul>
    </div>
</div>
@endsection

