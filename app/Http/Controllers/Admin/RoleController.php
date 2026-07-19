<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::with(['permissions', 'users'])->orderBy('priority')->get();
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'description' => 'nullable|string',
            'priority' => 'nullable|integer|min:0',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'] ?? 0,
        ]);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        \App\Models\ActivityLog::log('role_created', $role, "Создана роль: {$role->name}");

        return response()->json([
            'success' => true,
            'message' => 'Роль создана успешно',
            'role' => $role->load('permissions'),
        ]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Системные роли нельзя редактировать',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug,' . $role->id,
            'description' => 'nullable|string',
            'priority' => 'nullable|integer|min:0',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $oldData = $role->toArray();
        $role->update($validated);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        \App\Models\ActivityLog::log('role_updated', $role, "Обновлена роль: {$role->name}", [
            'old' => $oldData,
            'new' => $role->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Роль обновлена успешно',
            'role' => $role->load('permissions'),
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Системные роли нельзя удалять',
            ], 403);
        }

        $roleName = $role->name;
        $role->delete();

        \App\Models\ActivityLog::log('role_deleted', null, "Удалена роль: {$roleName}");

        return response()->json([
            'success' => true,
            'message' => 'Роль удалена успешно',
        ]);
    }
}

