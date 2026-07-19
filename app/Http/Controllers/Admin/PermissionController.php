<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('admin.permissions.index', compact('permissions'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug',
            'module' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $permission = Permission::create($validated);

        \App\Models\ActivityLog::log('permission_created', $permission, "Создано право: {$permission->name}");

        return response()->json([
            'success' => true,
            'message' => 'Право создано успешно',
            'permission' => $permission,
        ]);
    }

    public function update(Request $request, Permission $permission): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug,' . $permission->id,
            'module' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $oldData = $permission->toArray();
        $permission->update($validated);

        \App\Models\ActivityLog::log('permission_updated', $permission, "Обновлено право: {$permission->name}", [
            'old' => $oldData,
            'new' => $permission->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Право обновлено успешно',
            'permission' => $permission,
        ]);
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $permissionName = $permission->name;
        $permission->delete();

        \App\Models\ActivityLog::log('permission_deleted', null, "Удалено право: {$permissionName}");

        return response()->json([
            'success' => true,
            'message' => 'Право удалено успешно',
        ]);
    }
}

