<?php

namespace Modules\System\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\System\Models\Module;
use Illuminate\View\View;

class ModuleController extends Controller
{
    // Список модулей
    public function index(): View
    {
        $modules = Module::all();
        return view('System::admin.modules', compact('modules'));
    }
}
