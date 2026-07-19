<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\BackupDatabase;
use App\Jobs\BackupFiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

/**
 * 💾 BackupController - Управление бэкапами через админ-панель
 */
class BackupController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * 📋 Список бэкапов
     */
    public function index()
    {
        $databaseBackups = $this->getBackups('database');
        $filesBackups = $this->getBackups('files');

        return view('admin.backups.index', compact('databaseBackups', 'filesBackups'));
    }

    /**
     * 🔄 Создать бэкап БД
     */
    public function createDatabase()
    {
        BackupDatabase::dispatch();
        
        return back()->with('success', 'Бэкап базы данных запущен в фоне');
    }

    /**
     * 🔄 Создать бэкап файлов
     */
    public function createFiles()
    {
        BackupFiles::dispatch();
        
        return back()->with('success', 'Бэкап файлов запущен в фоне');
    }

    /**
     * 📥 Скачать бэкап
     */
    public function download(Request $request)
    {
        $type = $request->input('type'); // database или files
        $filename = $request->input('file');

        $backupPath = storage_path("app/backups/{$type}/{$filename}");

        if (!file_exists($backupPath)) {
            return back()->withErrors(['error' => 'Файл бэкапа не найден']);
        }

        return response()->download($backupPath);
    }

    /**
     * 🗑️ Удалить бэкап
     */
    public function delete(Request $request)
    {
        $type = $request->input('type');
        $filename = $request->input('file');

        $backupPath = storage_path("app/backups/{$type}/{$filename}");

        if (file_exists($backupPath)) {
            unlink($backupPath);
            return back()->with('success', 'Бэкап удален');
        }

        return back()->withErrors(['error' => 'Файл не найден']);
    }

    /**
     * 📋 Получить список бэкапов
     */
    private function getBackups(string $type): array
    {
        $backupDir = storage_path("app/backups/{$type}");
        
        if (!is_dir($backupDir)) {
            return [];
        }

        $files = glob($backupDir . '/*');
        $backups = [];

        foreach ($files as $file) {
            if (is_file($file)) {
                $backups[] = [
                    'name' => basename($file),
                    'size' => filesize($file),
                    'size_human' => $this->formatBytes(filesize($file)),
                    'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                ];
            }
        }

        // Сортировка по дате (новые первые)
        usort($backups, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return $backups;
    }

    /**
     * 📊 Форматирование размера файла
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

