<?php

namespace Modules\System\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\System\Models\Module;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Illuminate\Support\Facades\Response;

class ModuleController extends Controller
{
    /**
     * 📦 Отображение списка всех модулей
     */
    public function index(): View
    {
        $modules = Module::orderBy('priority')->get()->map(function ($module) {
            $module->is_installed = is_dir(base_path("modules/{$module->name}"));
            return $module;
        });

        return view('admin.modules', compact('modules'));
    }

    /**
     * 🔁 Переключение активности модуля (вкл/выкл)
     */
    public function toggle($id)
    {
        $module = Module::findOrFail($id);
        $module->active = !$module->active;
        $module->save();

        $status = $module->active ? 'включён' : 'отключён';
        return redirect()->route('admin.modules.index')->with('success', "Модуль «{$module->title}» {$status}.");
    }

    /**
     * 📥 Установка нового модуля из ZIP-архива
     */
    public function install(Request $request)
    {
        $request->validate([
            'module' => 'required|mimes:zip|max:10000',
        ]);

        $file = $request->file('module');
        $filename = $file->getClientOriginalName();
        $moduleName = pathinfo($filename, PATHINFO_FILENAME);

        $zipPath = storage_path("app/temp/$filename");
        $file->move(storage_path('app/temp'), $filename);

        $extractPath = base_path("modules/$moduleName");
        $zip = new ZipArchive;

        if ($zip->open($zipPath) === true) {
            $zip->extractTo($extractPath);
            $zip->close();
            File::delete($zipPath);
        } else {
            return back()->withErrors(['module' => 'Ошибка распаковки архива']);
        }

        $configPath = "$extractPath/module.json";
        if (!File::exists($configPath)) {
            return back()->withErrors(['module' => 'Файл module.json не найден']);
        }

        $data = json_decode(File::get($configPath), true);
        if (!$data || !isset($data['name'], $data['version'])) {
            return back()->withErrors(['module' => 'Некорректный формат module.json']);
        }

        $module = Module::updateOrCreate(
            ['name' => $data['name']],
            [
                'title'    => $data['title'] ?? $data['name'],
                'version'  => $data['version'],
                'priority' => $data['priority'] ?? Module::max('priority') + 1,
                'active'   => $data['active'] ?? false,
            ]
        );

        return redirect()->route('admin.modules.index')->with('success', "Модуль «{$module->title}» успешно установлен!");
    }

    /**
     * 🗑 Удаление модуля
     */
    public function destroy($id)
    {
        $module = Module::findOrFail($id);
        $moduleDir = base_path("modules/{$module->name}");

        if (File::exists($moduleDir)) {
            File::deleteDirectory($moduleDir);
        }

        $module->delete();

        return redirect()->route('admin.modules.index')->with('success', "Модуль «{$module->title}» был удалён.");
    }

    /**
     * 📦 Архивация модуля
     */
    public function archive($id)
    {
        $module = Module::findOrFail($id);
        $moduleDir = base_path("modules/{$module->name}");

        if (!File::exists($moduleDir)) {
            return back()->with('error', 'Модуль не найден в файловой системе.');
        }

        $archiveDir = base_path('modules/archives');
        if (!File::exists($archiveDir)) {
            File::makeDirectory($archiveDir, 0755, true);
        }

        $zipPath = "{$archiveDir}/{$module->name}.zip";

        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($moduleDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($moduleDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }

            $zip->close();

            return back()->with('success', "Архив модуля «{$module->title}» создан в /modules/archives.");
        }

        return back()->with('error', 'Не удалось создать архив.');
    }

    /**
     * ⬇️ Скачать архив модуля
     */
    public function downloadArchive($name)
    {
        $archivePath = base_path("modules/archives/{$name}.zip");

        if (!File::exists($archivePath)) {
            abort(404, 'Архив не найден.');
        }

        return response()->download($archivePath, "{$name}.zip");
    }

    /**
     * 🔢 Drag-and-drop сортировка приоритетов
     */
    public function reorder(Request $request)
    {
        foreach ($request->input('order') as $item) {
            Module::where('id', $item['id'])->update(['priority' => $item['priority']]);
        }

        return response()->json(['status' => 'ok']);
    }
}
