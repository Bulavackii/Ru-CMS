<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 🔄 UpdateController - Управление обновлениями
 */
class UpdateController extends Controller
{
    private UpdateService $updateService;

    public function __construct(UpdateService $updateService)
    {
        $this->middleware('admin');
        $this->updateService = $updateService;
    }

    /**
     * 📋 Страница проверки обновлений
     */
    public function index()
    {
        $updateInfo = $this->updateService->checkForUpdates();
        
        return view('admin.updates.index', compact('updateInfo'));
    }

    /**
     * 🔍 Проверка обновлений (AJAX)
     */
    public function check()
    {
        $updateInfo = $this->updateService->checkForUpdates();
        
        return response()->json($updateInfo);
    }

    /**
     * 📥 Загрузка и установка обновления
     */
    public function install(Request $request)
    {
        $request->validate([
            'version' => 'required|string',
        ]);

        try {
            $version = $request->input('version');
            
            // Загрузка
            $updatePath = $this->updateService->downloadUpdate($version);
            
            if (!$updatePath) {
                return back()->withErrors(['update' => 'Не удалось загрузить обновление']);
            }

            // Установка
            $success = $this->updateService->installUpdate($updatePath);
            
            if ($success) {
                return redirect()->route('admin.updates.index')
                    ->with('success', 'Обновление успешно установлено!');
            } else {
                return back()->withErrors(['update' => 'Ошибка при установке обновления']);
            }
        } catch (\Exception $e) {
            Log::error('Update installation failed', ['error' => $e->getMessage()]);
            return back()->withErrors(['update' => $e->getMessage()]);
        }
    }
}

