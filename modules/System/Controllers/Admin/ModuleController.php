<?php

namespace Modules\System\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\System\Models\Module;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use Carbon\Carbon;

class ModuleController extends Controller
{
    public function index(): View
    {
        $modules = Module::orderByDesc('priority')->get();
        return view('admin.modules', compact('modules'));
    }

    public function toggle($id)
    {
        $module = Module::findOrFail($id);
        $module->active = !$module->active;
        $module->save();

        return response()->json(['success' => true, 'status' => $module->active]);
    }

    public function install(Request $request)
    {
        $request->validate([
            'module' => 'required|mimes:zip|max:10000',
        ]);

        $file = $request->file('module');
        $filename = $file->getClientOriginalName();
        $moduleName = pathinfo($filename, PATHINFO_FILENAME);

        $tempPath = storage_path("app/temp");
        File::ensureDirectoryExists($tempPath);
        $zipPath = $tempPath . '/' . $filename;
        $file->move($tempPath, $filename);

        $extractPath = $tempPath . '/' . $moduleName;
        File::deleteDirectory($extractPath);
        File::makeDirectory($extractPath);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            return back()->with('error', 'Ошибка распаковки архива.');
        }
        $zip->extractTo($extractPath);
        $zip->close();
        File::delete($zipPath);

        $configPath = $extractPath . '/module.json';
        $actualRoot = $extractPath;

        if (!File::exists($configPath)) {
            $dirs = File::directories($extractPath);
            if (count($dirs) === 1 && File::exists($dirs[0] . '/module.json')) {
                $actualRoot = $dirs[0];
                $configPath = $actualRoot . '/module.json';
            } else {
                return redirect()->route('admin.modules.index')->with('error', 'Файл module.json не найден.');
            }
        }

        $data = json_decode(File::get($configPath), true);
        if (!isset($data['name'], $data['version'])) {
            return redirect()->route('admin.modules.index')->with('error', 'Некорректный module.json.');
        }

        $finalPath = base_path("modules/{$data['name']}");

        if (File::exists($finalPath)) {
            File::deleteDirectory($finalPath);
        }

        File::moveDirectory($actualRoot, $finalPath);
        File::deleteDirectory($extractPath);

        // Убедимся, что значение для installed_at всегда корректно
        $installedAt = $this->validateInstalledAt($data['installed_at'] ?? null);

        Module::updateOrCreate(
            ['name' => $data['name']],
            [
                'version'      => $data['version'],
                'active'       => $data['active'] ?? false,
                'installed_at' => $installedAt,
            ]
        );

        Log::info("Модуль {$data['name']} установлен или обновлён", $data);
        File::deleteDirectory($tempPath);

        return redirect()->route('admin.modules.index')->with('success', 'Модуль установлен или обновлён.');
    }

    public function destroy($id)
    {
        $module = Module::findOrFail($id);
        $path = base_path("modules/{$module->name}");

        if (File::exists($path)) {
            File::deleteDirectory($path);
        }

        $module->delete();

        return redirect()->route('admin.modules.index')->with('success', 'Модуль удалён.');
    }

    /**
     * Валидация и установка правильной даты для installed_at
     *
     * @param mixed $installedAt
     * @return \Carbon\Carbon
     */
    protected function validateInstalledAt($installedAt)
    {
        // Если установлена некорректная дата или пустое значение, устанавливаем текущую дату
        if (empty($installedAt) || !Carbon::hasFormat($installedAt, 'Y-m-d H:i:s')) {
            return Carbon::now();
        }

        // Если это строка, то пытаемся преобразовать в объект Carbon
        try {
            return Carbon::parse($installedAt);
        } catch (\Exception $e) {
            return Carbon::now(); // если не удалось парсить, ставим текущую дату
        }
    }
}
