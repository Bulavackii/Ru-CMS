<?php

namespace Modules\Users\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;

/**
 * 👥 Контроллер управления пользователями (админка)
 */
class UserController extends Controller
{
    /**
     * 📄 Список пользователей с фильтрацией по роли и поиском
     */
    public function index(Request $request)
    {
        $currentRole = $request->get('role'); // admin / user / role_id
        $search = $request->get('search', '');
        $roleFilter = $request->get('role_filter');

        $users = User::with('roles')
            ->when($currentRole === 'admin', fn($query) => $query->where('is_admin', 1))
            ->when($currentRole === 'user', fn($query) => $query->where('is_admin', 0))
            ->when($roleFilter, fn($query) => 
                $query->whereHas('roles', fn($q) => $q->where('roles.id', $roleFilter))
            )
            ->when($search, fn($query) =>
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->appends($request->only(['search', 'role', 'role_filter']));

        $roles = Role::orderBy('priority')->get();

        return view('users::admin.index', compact('users', 'currentRole', 'search', 'roles', 'roleFilter'));
    }

    /**
     * 📊 Просмотр истории входов пользователя
     */
    public function loginHistory(User $user)
    {
        $history = $user->loginHistory()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('users::admin.login-history', compact('user', 'history'));
    }

    /**
     * 🔄 Массовые операции с пользователями
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate,assign_role',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = $request->input('user_ids');
        $users = User::whereIn('id', $userIds)->get();

        switch ($request->input('action')) {
            case 'delete':
                // Не удаляем админов
                $deleted = $users->filter(fn($u) => !$u->is_admin)->each->delete();
                $count = $deleted->count();
                return back()->with('success', "Удалено пользователей: {$count}");

            case 'assign_role':
                $request->validate(['role_id' => 'required|exists:roles,id']);
                $role = Role::findOrFail($request->input('role_id'));
                $users->each(fn($u) => $u->roles()->syncWithoutDetaching([$role->id]));
                return back()->with('success', "Роль '{$role->name}' назначена {$users->count()} пользователям");

            default:
                return back()->with('error', 'Неизвестное действие');
        }
    }

    /**
     * 🧾 Форма создания нового пользователя
     */
    public function create()
    {
        $roles = Role::orderBy('priority')->get();
        return view('users::admin.create', compact('roles'));
    }

    /**
     * 💾 Сохранение нового пользователя
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => ['nullable', new \App\Rules\RussianPhone()],
            'postal_code' => 'nullable|string|max:10|regex:/^\d{6}$/',
            'region' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => $request->has('is_admin') ? 1 : 0,
            'phone' => $validated['phone'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'region' => $validated['region'] ?? null,
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'] ?? null,
            'locale' => $request->input('locale'),
            'country_code' => $request->input('country_code'),
        ]);

        // Назначение ролей (только если не админ)
        if (!$user->is_admin && $request->has('roles')) {
            $user->roles()->sync($request->input('roles'));
        }

        \App\Models\ActivityLog::log('user_created', $user, "Создан пользователь: {$user->name}");

        return redirect()->route('admin.users.index')->with('success', 'Пользователь успешно создан!');
    }

    /**
     * ✏️ Форма редактирования пользователя
     */
    public function edit(User $user)
    {
        $roles = Role::orderBy('priority')->get();
        return view('users::admin.edit', compact('user', 'roles'));
    }

    /**
     * 💾 Обновление пользователя
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => ['nullable', new \App\Rules\RussianPhone()],
            'postal_code' => 'nullable|string|max:10|regex:/^\d{6}$/',
            'region' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'locale' => 'nullable|string|max:10',
            'country_code' => 'nullable|string|max:2',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_admin' => $request->has('is_admin') ? 1 : 0,
            'phone' => $validated['phone'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'region' => $validated['region'] ?? null,
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'] ?? null,
            'locale' => $validated['locale'] ?? null,
            'country_code' => $validated['country_code'] ?? null,
        ]);

        // Назначение ролей (только если не админ)
        if (!$user->is_admin && $request->has('roles')) {
            $user->roles()->sync($request->input('roles'));
        }

        \App\Models\ActivityLog::log('user_updated', $user, "Обновлен пользователь: {$user->name}");

        return redirect()->route('admin.users.index')->with('success', 'Пользователь успешно обновлен!');
    }

    /**
     * 🔄 Переключение роли пользователя (админ/пользователь)
     */
    public function toggleRole($id)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect()->route('admin.users.index')->with('error', 'Пользователь не найден');
        }

        $user->is_admin = !$user->is_admin;
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Роль пользователя изменена');
    }

    /**
     * 🔐 Форма смены пароля
     */
    public function editPassword($id)
    {
        $user = User::findOrFail($id);
        return view('users::admin.password', compact('user'));
    }

    /**
     * 📝 Обновление пароля пользователя
     */
    public function updatePassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::findOrFail($id);
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Пароль обновлён');
    }

    /**
     * 🗑️ Удаление пользователя (кроме админов)
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return redirect()->route('admin.users.index')->with('error', 'Пользователь не найден');
        }

        if ($user->is_admin) {
            return redirect()->route('admin.users.index')->with('error', 'Невозможно удалить администратора');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Пользователь удалён');
    }

    // 🔍 AJAX-поиск пользователей
    public function ajaxSearch(Request $request)
    {
        $query = $request->input('q');

        $users = User::query()
            ->where('name', 'like', "%$query%")
            ->orWhere('email', 'like', "%$query%")
            ->limit(10)
            ->get(['id', 'name', 'email', 'is_admin']);

        return Response::json($users);
    }

    /**
     * 🔐 Назначить роли пользователю
     */
    public function assignRoles(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user->roles()->sync($request->input('roles', []));

        \App\Models\ActivityLog::log('user_roles_updated', $user, "Обновлены роли пользователя: {$user->name}");

        return response()->json([
            'success' => true,
            'message' => 'Роли обновлены успешно',
        ]);
    }
}
