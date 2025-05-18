<?php

namespace Modules\System\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\System\Models\Module;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ModuleController extends Controller
{
    /**
     * 📦 Отображение списка всех модулей
     */
    public function index(): View
    {
        $modules = Module::all();
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

        return redirect()->route('admin.modules.index');
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

        // 📁 Сохраняем ZIP-файл во временную директорию
        $zipPath = storage_path("app/temp/$filename");
        $file->move(storage_path('app/temp'), $filename);

        // 📂 Путь для распаковки
        $extractPath = base_path("modules/$moduleName");
        $zip = new ZipArchive;

        // 🔓 Попытка распаковать архив
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($extractPath);
            $zip->close();
            File::delete($zipPath);
        } else {
            return back()->withErrors(['module' => 'Ошибка распаковки архива']);
        }

        // 📄 Проверка наличия module.json
        $configPath = "$extractPath/module.json";
        if (!File::exists($configPath)) {
            return back()->withErrors(['module' => 'Файл module.json не найден']);
        }

        // 📚 Чтение и проверка содержимого module.json
        $data = json_decode(File::get($configPath), true);
        if (!$data || !isset($data['name'], $data['version'])) {
            return back()->withErrors(['module' => 'Некорректный формат файла module.json']);
        }

        // 📝 Регистрация или обновление модуля в базе
        Module::updateOrCreate(
            ['name' => $data['name']],
            [
                'version' => $data['version'],
                'active' => $data['active'] ?? false,
            ]
        );

        return redirect()->route('admin.modules.index')->with('success', 'Модуль установлен!');
    }
}
