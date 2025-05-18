<?php

namespace Modules\Users\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $currentRole = $request->get('role'); // admin / user
        $search = $request->get('search', '');

        $users = User::query()
            ->when($currentRole, fn($query) =>
            $query->where('is_admin', $currentRole === 'admin' ? 1 : 0))
            ->when($search, fn($query) =>
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(5)
            ->appends($request->only(['search', 'role'])); // сохранение фильтров при пагинации

        return view('users::admin.index', compact('users', 'currentRole', 'search'));
    }

    /**
     * 🧾 Форма создания нового пользователя
     */
    public function create()
    {
        return view('users::admin.create');
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
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'is_admin' => $request->has('is_admin') ? 1 : 0,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Пользователь успешно создан!');
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
}
