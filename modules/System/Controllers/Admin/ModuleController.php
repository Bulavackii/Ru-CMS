<?php

namespace Modules\System\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\System\Models\Module;
use Illuminate\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ModuleController extends Controller
{
    // Список модулей
    public function index(): View
    {
        $modules = Module::all();
        return view('admin.modules', compact('modules'));
    }

    public function toggle($id)
    {
        $module = Module::findOrFail($id);
        $module->active = !$module->active;
        $module->save();

        return redirect()->route('admin.modules.index');
    }

    public function install(Request $request)
    {
        $request->validate([
            'module' => 'required|mimes:zip|max:10000',
        ]);

        $file = $request->file('module');
        $filename = $file->getClientOriginalName();
        $moduleName = pathinfo($filename, PATHINFO_FILENAME);

        // Временный путь
        $zipPath = storage_path("app/temp/$filename");
        $file->move(storage_path('app/temp'), $filename);

        // Распаковка
        $extractPath = base_path("modules/$moduleName");
        $zip = new ZipArchive;

        if ($zip->open($zipPath) === true) {
            $zip->extractTo($extractPath);
            $zip->close();
            File::delete($zipPath);
        } else {
            return back()->withErrors(['module' => 'Ошибка распаковки архива']);
        }

        // Чтение module.json
        $configPath = "$extractPath/module.json";
        if (!File::exists($configPath)) {
            return back()->withErrors(['module' => 'module.json не найден']);
        }

        $data = json_decode(File::get($configPath), true);
        if (!$data || !isset($data['name'], $data['version'])) {
            return back()->withErrors(['module' => 'Некорректный module.json']);
        }

        // Регистрация модуля
        \Modules\System\Models\Module::updateOrCreate(
            ['name' => $data['name']],
            [
                'version' => $data['version'],
                'active' => $data['active'] ?? false,
            ]
        );

        return redirect()->route('admin.modules.index')->with('success', 'Модуль установлен!');
    }
}
