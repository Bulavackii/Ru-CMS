<?php

namespace Modules\System\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\System\Services\ModuleDistributionService;
use Illuminate\Http\Request;

class ModuleDistributionController extends Controller
{
    protected $distributionService;

    public function __construct(ModuleDistributionService $distributionService)
    {
        $this->distributionService = $distributionService;
    }

    /**
     * Список доступных модулей из репозиториев
     */
    public function available(Request $request)
    {
        $repository = $request->input('repository', 'official');
        $modules = $this->distributionService->getAvailableModules($repository);

        return view('admin.modules.available', [
            'modules' => $modules,
            'repository' => $repository,
            'repositories' => $this->distributionService->getRepositories(),
        ]);
    }

    /**
     * Установка модуля из URL
     */
    public function installFromUrl(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'signature' => 'nullable|string',
        ]);

        $result = $this->distributionService->installFromUrl(
            $request->url,
            $request->signature
        );

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Установка из GitHub
     */
    public function installFromGitHub(Request $request)
    {
        $request->validate([
            'repo' => 'required|string',
            'branch' => 'nullable|string',
        ]);

        $result = $this->distributionService->installFromGitHub(
            $request->repo,
            $request->branch ?? 'main'
        );

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Экспорт модуля
     */
    public function export($id)
    {
        $module = \Modules\System\Models\Module::findOrFail($id);

        // Получаем ключ из запроса или из конфига
        $privateKey = request('key');

        $result = $this->distributionService->exportModule($module->name, $privateKey);

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return response()->download($result['path']);
    }

    /**
     * Проверка обновлений
     */
    public function checkUpdates($id)
    {
        $module = \Modules\System\Models\Module::findOrFail($id);

        $result = $this->distributionService->checkUpdates($module->name);

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return back()->with('update_info', [
            'module' => $module->name,
            'current' => $result['current'],
            'available' => $result['available'],
            'has_update' => $result['has_update'],
        ]);
    }

    /**
     * Добавить репозиторий
     */
    public function addRepository(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:module_repositories',
            'url' => 'required|url',
        ]);

        $repositories = $this->distributionService->addRepository(
            $request->name,
            $request->url
        );

        return back()->with('success', "Репозиторий {$request->name} добавлен");
    }
}
